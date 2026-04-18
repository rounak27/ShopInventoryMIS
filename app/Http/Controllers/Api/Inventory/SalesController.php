<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Helpers\BarcodeHelper;
use App\Helpers\FiscalYearHelper;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockLedger;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SalesController extends Controller
{
    // ── Constants ───────────────────────────────────
    private const VAT_RATE = 0.13; // 13% VAT for Nepal
    private const LOW_STOCK_THRESHOLD = 5;

    // ── Helpers ─────────────────────────────────────

    private function ok(mixed $data): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data]);
    }

    private function err(string $msg, int $status = 422): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $msg], $status);
    }

    /**
     * Find variant by barcode or ID
     */
    private function resolveVariant(Request $request): ?ItemVariant
    {
        if ($request->barcode) {
            return ItemVariant::with('item')
                ->where('barcode', $request->barcode)
                ->where('is_active', true)
                ->first();
        }

        if ($request->variantId) {
            return ItemVariant::with('item')
                ->find($request->variantId);
        }

        return null;
    }

    /**
     * Format sale for API response
     */
    private function formatSale(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'billNumber' => $sale->bill_number,
            'fiscalYear' => $sale->fiscal_year,
            'customerName' => $sale->customer_name,
            'customerPan' => $sale->customer_pan,
            'paymentMethod' => $sale->payment_method,
            'subTotal' => (float) $sale->sub_total,
            'discountAmount' => (float) $sale->discount_amount,
            'taxableAmount' => (float) $sale->taxable_amount,
            'vat' => (float) $sale->vat,
            'grandTotal' => (float) $sale->grand_total,
            'status' => $sale->status,
            'saleDate' => Carbon::parse($sale->sale_date)->format('Y-m-d'),
            'printedAt' => $sale->printed_at?->format('Y-m-d H:i:s'),
            'createdBy' => $sale->creator?->name,
            'createdAt' => $sale->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Format sale item for API response
     */
    private function formatSaleItem(SaleItem $item): array
    {
        $returnedQuantity = (int) ($item->returned_quantity ?? 0);

        return [
            'id' => $item->id,
            'variant' => [
                'id' => $item->variant->id,
                'size' => $item->variant->size,
                'color' => $item->variant->color,
                'barcode' => $item->variant->barcode,
                'itemName' => $item->variant->item->name,
                'itemSku' => $item->variant->item->sku,
            ],
            'quantity' => (int) $item->quantity,
            'returnedQuantity' => $returnedQuantity,
            'availableReturnQuantity' => max((int) $item->quantity - $returnedQuantity, 0),
            'pricePerUnit' => (float) $item->price_per_unit,
            'costPrice' => (float) $item->cost_price,
            'profit' => (float) $item->profit,
            'discountAmount' => (float) $item->discount_amount,
            'totalPrice' => (float) $item->total_price,
        ];
    }

    /**
     * Build the standard sale payload shape used by checkout and exchange flows.
     */
    private function buildSalePayload(Sale $sale): array
    {
        return [
            'sale' => $this->formatSale($sale),
            'items' => $sale->items->map(fn ($item) => $this->formatSaleItem($item))->toArray(),
            'invoice' => $this->formatInvoice($sale),
        ];
    }

    /**
     * Create a sale from resolved cart item payloads.
     *
     * Each item payload must contain variantId or barcode, quantity, and optional priceOverride.
     */
    private function createSaleFromItems(array $itemPayloads, array $saleData, User $user, ?string $fiscalYear = null): Sale
    {
        $fiscalYear = $fiscalYear ?: FiscalYearHelper::getCurrentFiscalYear();

        $resolvedItems = [];
        $subTotal = 0;

        foreach ($itemPayloads as $itemData) {
            $variant = ItemVariant::with('item')
                ->where(function ($query) use ($itemData) {
                    if (!empty($itemData['barcode'])) {
                        $query->where('barcode', $itemData['barcode']);
                    } elseif (!empty($itemData['variantId'])) {
                        $query->where('id', $itemData['variantId']);
                    }
                })
                ->lockForUpdate()
                ->first();

            /** @var ItemVariant|null $variant */

            if (!$variant) {
                throw new \Exception('Variant not found for: ' . json_encode($itemData));
            }

            $quantity = (int) ($itemData['quantity'] ?? 0);
            if ($quantity < 1) {
                throw new \Exception('Invalid item quantity for ' . $variant->item->name);
            }

            if ($variant->current_stock < $quantity) {
                throw new \Exception(
                    "Insufficient stock for {$variant->item->name} ({$variant->size}/{$variant->color}). " .
                    "Available: {$variant->current_stock}, Requested: {$quantity}"
                );
            }

            $unitPrice = isset($itemData['priceOverride'])
                ? (float) $itemData['priceOverride']
                : (float) $variant->item->selling_price;

            $itemTotal = $unitPrice * $quantity;
            $subTotal += $itemTotal;

            $costPrice = (float) $variant->item->cost_price;
            $profit = ($unitPrice - $costPrice) * $quantity;

            $resolvedItems[] = [
                'variant' => $variant,
                'quantity' => $quantity,
                'unitPrice' => $unitPrice,
                'costPrice' => $costPrice,
                'profit' => $profit,
                'total' => $itemTotal,
            ];
        }

        $discountAmount = (float) ($saleData['discountAmount'] ?? 0);
        $taxableAmount = $subTotal - $discountAmount;
        $vat = round($taxableAmount * self::VAT_RATE, 2);
        $grandTotal = $taxableAmount + $vat;

        $billNo = FiscalYearHelper::getNextBillNumber($fiscalYear);
        $billNumber = FiscalYearHelper::formatBillNumber($fiscalYear, $billNo);

        $sale = Sale::create([
            'customer_name' => $saleData['customerName'] ?? null,
            'customer_pan' => $saleData['customerPan'] ?? null,
            'bill_number' => $billNumber,
            'fiscal_year' => $fiscalYear,
            'created_by' => $user->id,
            'sub_total' => $subTotal,
            'discount_amount' => $discountAmount,
            'taxable_amount' => $taxableAmount,
            'vat' => $vat,
            'grand_total' => $grandTotal,
            'payment_method' => $saleData['paymentMethod'] ?? 'cash',
            'status' => 'completed',
            'sale_date' => Carbon::now()->toDateString(),
        ]);

        foreach ($resolvedItems as $itemData) {
            $variant = $itemData['variant'];
            $oldStock = (int) $variant->current_stock;
            $newStock = $oldStock - $itemData['quantity'];

            SaleItem::create([
                'sale_id' => $sale->id,
                'variant_id' => $variant->id,
                'quantity' => $itemData['quantity'],
                'returned_quantity' => 0,
                'price_per_unit' => $itemData['unitPrice'],
                'cost_price' => $itemData['costPrice'],
                'profit' => $itemData['profit'],
                'discount_amount' => 0,
                'total_price' => $itemData['total'],
            ]);

            $variant->update(['current_stock' => $newStock]);

            StockLedger::create([
                'variant_id' => $variant->id,
                'user_id' => $user->id,
                'action_type' => 'sale',
                'quantity_change' => -$itemData['quantity'],
                'stock_before' => $oldStock,
                'stock_after' => $newStock,
                'reference_no' => $sale->bill_number,
                'notes' => "Sale: {$sale->bill_number}",
                'transaction_date' => now(),
            ]);
        }

        return $sale->load(['items.variant.item', 'creator']);
    }

    /**
     * Format invoice for printing
     */
    private function formatInvoice(Sale $sale): array
    {
        return [
            'billNumber' => $sale->bill_number,
            'fiscalYear' => $sale->fiscal_year,
            'saleDate' => Carbon::parse($sale->sale_date)->format('Y-m-d'),
            'saleTime' => $sale->created_at->format('H:i:s'),
            'customerName' => $sale->customer_name ?? 'Walk-in Customer',
            'customerPan' => $sale->customer_pan,
            'paymentMethod' => ucfirst($sale->payment_method),
            'items' => $sale->items->map(function ($item) {
                return [
                    'itemName' => $item->variant->item->name,
                    'variant' => "{$item->variant->size}/{$item->variant->color}",
                    'barcode' => $item->variant->barcode,
                    'quantity' => (int) $item->quantity,
                    'unitPrice' => (float) $item->price_per_unit,
                    'discountAmount' => (float) $item->discount_amount,
                    'lineTotal' => (float) $item->total_price,
                ];
            })->toArray(),
            'subTotal' => (float) $sale->sub_total,
            'totalDiscount' => (float) $sale->discount_amount,
            'taxableAmount' => (float) $sale->taxable_amount,
            'vatRate' => (int) (self::VAT_RATE * 100) . '%',
            'vatAmount' => (float) $sale->vat,
            'grandTotal' => (float) $sale->grand_total,
        ];
    }

    // ── POST /api/v1/inventory/sales ────────────────

    /**
     * Create a new sale (POS checkout)
     *
     * Request body:
     * {
     *   customerName?: string,
     *   customerPan?: string,
     *   paymentMethod: 'cash' | 'card' | 'fonepay' | 'esewa',
     *   discountAmount?: number (default 0),
     *   items: [
     *     {
     *       barcode || variantId: string | int,
     *       quantity: int,
     *       priceOverride?: number
     *     }
     *   ]
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customerName' => 'nullable|string|max:100',
            'customerPan' => 'nullable|regex:/^[0-9]{9}PAN[0-9]{3}$/|max:20',
            'paymentMethod' => 'required|in:cash,card,fonepay,esewa',
            'discountAmount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.barcode' => 'nullable|string|max:50',
            'items.*.variantId' => 'nullable|integer|exists:item_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.priceOverride' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->err($validator->errors()->first());
        }

        try {
            $sale = DB::transaction(function () use ($request) {
                $user = Auth::guard('api')->user();
                return $this->createSaleFromItems(
                    $request->items,
                    [
                        'customerName' => $request->customerName,
                        'customerPan' => $request->customerPan,
                        'paymentMethod' => $request->paymentMethod,
                        'discountAmount' => $request->discountAmount,
                    ],
                    $user
                );
            });

            return $this->ok($this->buildSalePayload($sale));
        } catch (\Exception $e) {
            return $this->err($e->getMessage());
        }
    }

    // ── GET /api/v1/inventory/sales ─────────────────

    /**
     * List sales with filters
     *
     * Query params:
     * - from_date: YYYY-MM-DD
     * - to_date: YYYY-MM-DD
     * - bill_number: string
     * - customer_name: string
     * - fiscal_year: YYYY/YY
     * - status: completed|returned|cancelled
     * - page: int
     * - per_page: int
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) ($request->per_page ?? 50);

        $query = Sale::with(['items.variant.item', 'creator']);

        // Date range filter
        if ($request->from_date) {
            $query->whereDate('sale_date', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('sale_date', '<=', $request->to_date);
        }

        // Bill number filter
        if ($request->bill_number) {
            $query->where('bill_number', 'like', "%{$request->bill_number}%");
        }

        // Customer filter
        if ($request->customer_name) {
            $query->where('customer_name', 'like', "%{$request->customer_name}%");
        }

        // Fiscal year filter
        if ($request->fiscal_year) {
            $query->where('fiscal_year', $request->fiscal_year);
        }

        // Status filter
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $sales = $query->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $sales->getCollection()->map(fn ($s) => $this->formatSale($s))->values(),
            'meta' => [
                'total' => $sales->total(),
                'perPage' => $sales->perPage(),
                'currentPage' => $sales->currentPage(),
                'lastPage' => $sales->lastPage(),
            ],
        ]);
    }

    // ── GET /api/v1/inventory/sales/statement ──────

    /**
     * Get datewise and userwise daily statement summary.
     *
     * Query params:
     * - days: int (default 30)
     * - user_id: int (optional)
     */
    public function statement(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:365',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->err($validator->errors()->first());
        }

        $days = (int) ($request->input('days', 30));
        $days = max(1, min($days, 365));
        $userId = $request->filled('user_id') ? (int) $request->input('user_id') : null;

        $toDate = Carbon::parse($request->input('to_date', now()))->endOfDay();
        $fromDate = $request->filled('from_date')
            ? Carbon::parse($request->input('from_date'))->startOfDay()
            : (clone $toDate)->subDays($days - 1)->startOfDay();

        $baseQuery = Sale::with('creator')
            ->whereBetween('sale_date', [$fromDate->toDateString(), $toDate->toDateString()]);

        if ($userId) {
            $baseQuery->where('created_by', $userId);
        }

        $overall = (clone $baseQuery)
            ->selectRaw('
                COUNT(*) as bills,
                COALESCE(SUM(sub_total), 0) as sub_total,
                COALESCE(SUM(discount_amount), 0) as discount_amount,
                COALESCE(SUM(taxable_amount), 0) as taxable_amount,
                COALESCE(SUM(vat), 0) as vat,
                COALESCE(SUM(grand_total), 0) as grand_total
            ')
            ->first();

        $datewise = (clone $baseQuery)
            ->selectRaw('
                sale_date,
                COUNT(*) as bills,
                COALESCE(SUM(sub_total), 0) as sub_total,
                COALESCE(SUM(discount_amount), 0) as discount_amount,
                COALESCE(SUM(taxable_amount), 0) as taxable_amount,
                COALESCE(SUM(vat), 0) as vat,
                COALESCE(SUM(grand_total), 0) as grand_total
            ')
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get()
            ->map(fn ($row) => [
                'date' => Carbon::parse($row->sale_date)->format('Y-m-d'),
                'bills' => (int) $row->bills,
                'subTotal' => (float) $row->sub_total,
                'discountAmount' => (float) $row->discount_amount,
                'taxableAmount' => (float) $row->taxable_amount,
                'vat' => (float) $row->vat,
                'grandTotal' => (float) $row->grand_total,
            ])
            ->values();

        $userwiseQuery = Sale::query()
            ->leftJoin('users', 'sales.created_by', '=', 'users.id')
            ->whereBetween('sales.sale_date', [$fromDate->toDateString(), $toDate->toDateString()]);

        if ($userId) {
            $userwiseQuery->where('sales.created_by', $userId);
        }

        $userwise = $userwiseQuery
            ->selectRaw('
                sales.created_by as user_id,
                COALESCE(users.name, "Unknown") as user_name,
                COALESCE(users.username, "") as username,
                COUNT(*) as bills,
                COALESCE(SUM(sales.sub_total), 0) as sub_total,
                COALESCE(SUM(sales.discount_amount), 0) as discount_amount,
                COALESCE(SUM(sales.taxable_amount), 0) as taxable_amount,
                COALESCE(SUM(sales.vat), 0) as vat,
                COALESCE(SUM(sales.grand_total), 0) as grand_total
            ')
            ->groupBy('sales.created_by', 'users.name', 'users.username')
            ->orderBy('users.name')
            ->get()
            ->map(fn ($row) => [
                'userId' => $row->user_id ? (int) $row->user_id : null,
                'userName' => $row->user_name,
                'username' => $row->username,
                'bills' => (int) $row->bills,
                'subTotal' => (float) $row->sub_total,
                'discountAmount' => (float) $row->discount_amount,
                'taxableAmount' => (float) $row->taxable_amount,
                'vat' => (float) $row->vat,
                'grandTotal' => (float) $row->grand_total,
            ])
            ->values();

        $bills = (clone $baseQuery)
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Sale $sale) => [
                'id' => (int) $sale->id,
                'billNumber' => $sale->bill_number,
                'saleDate' => Carbon::parse($sale->sale_date)->format('Y-m-d'),
                'createdAt' => $sale->created_at->format('Y-m-d H:i:s'),
                'userId' => $sale->created_by ? (int) $sale->created_by : null,
                'userName' => $sale->creator?->name ?? 'Unknown',
                'username' => $sale->creator?->username,
                'customerName' => $sale->customer_name ?? 'Walk-in Customer',
                'paymentMethod' => ucfirst($sale->payment_method),
                'status' => $sale->status,
                'subTotal' => (float) $sale->sub_total,
                'discountAmount' => (float) $sale->discount_amount,
                'vat' => (float) $sale->vat,
                'grandTotal' => (float) $sale->grand_total,
            ])
            ->values();

        $users = User::query()
            ->select(['id', 'name', 'username'])
            ->orderBy('name')
            ->get()
            ->map(fn ($user) => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'username' => $user->username,
            ])
            ->values();

        return $this->ok([
            'filters' => [
                'days' => $days,
                'userId' => $userId,
                'fromDate' => $fromDate->toDateString(),
                'toDate' => $toDate->toDateString(),
            ],
            'users' => $users,
            'summary' => [
                'bills' => (int) ($overall->bills ?? 0),
                'subTotal' => (float) ($overall->sub_total ?? 0),
                'discountAmount' => (float) ($overall->discount_amount ?? 0),
                'taxableAmount' => (float) ($overall->taxable_amount ?? 0),
                'vat' => (float) ($overall->vat ?? 0),
                'grandTotal' => (float) ($overall->grand_total ?? 0),
            ],
            'datewise' => $datewise,
            'userwise' => $userwise,
            'bills' => $bills,
        ]);
    }

    // ── GET /api/v1/inventory/sales/{id} ────────────

    /**
     * Get full invoice details including all items
     */
    public function show(int $id): JsonResponse
    {
        $sale = Sale::with(['items.variant.item', 'creator'])
            ->find($id);

        if (!$sale) {
            return $this->err('Sale not found', 404);
        }

        return $this->ok([
            'sale' => $this->formatSale($sale),
            'items' => $sale->items->map(fn ($i) => $this->formatSaleItem($i))->toArray(),
            'invoice' => $this->formatInvoice($sale),
        ]);
    }

    // ── POST /api/v1/inventory/sales/{id}/return ────

    /**
     * Return a sale and restore stock
     *
     * Request body:
     * {
     *   reason?: string,
     *   restoreFully: boolean (default true)
     * }
     */
    public function return(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mode' => 'nullable|in:return,exchange',
            'reason' => 'nullable|string|max:255',
            'returnItems' => 'required|array|min:1',
            'returnItems.*.saleItemId' => 'required|integer|exists:sale_items,id',
            'returnItems.*.quantity' => 'required|integer|min:1',
            'exchangeItems' => 'nullable|array|min:1',
            'exchangeItems.*.barcode' => 'nullable|string|max:50',
            'exchangeItems.*.variantId' => 'nullable|integer|exists:item_variants,id',
            'exchangeItems.*.quantity' => 'required_with:exchangeItems|integer|min:1',
            'exchangeItems.*.priceOverride' => 'nullable|numeric|min:0',
            'paymentMethod' => 'required_if:mode,exchange|nullable|in:cash,card,fonepay,esewa',
            'discountAmount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->err($validator->errors()->first());
        }

        $sale = Sale::with(['items.variant.item', 'creator'])->find($id);

        if (!$sale) {
            return $this->err('Sale not found', 404);
        }

        if ($sale->status !== 'completed') {
            return $this->err("Cannot return a {$sale->status} sale");
        }

        $mode = $request->input('mode', 'return');
        $returnItems = collect($request->input('returnItems', []))
            ->groupBy('saleItemId')
            ->map(fn ($group) => (int) $group->sum('quantity'))
            ->all();

        if (count($returnItems) === 0) {
            return $this->err('Select at least one sale item to return');
        }

        $saleItemsById = $sale->items->keyBy('id');
        foreach ($returnItems as $saleItemId => $quantity) {
            $saleItem = $saleItemsById->get((int) $saleItemId);

            if (!$saleItem) {
                return $this->err('One or more return items do not belong to this sale');
            }

            $availableToReturn = (int) $saleItem->quantity - (int) $saleItem->returned_quantity;
            if ($quantity > $availableToReturn) {
                return $this->err(
                    "Return quantity for {$saleItem->variant->item->name} ({$saleItem->variant->size}/{$saleItem->variant->color}) exceeds the available return quantity ({$availableToReturn})."
                );
            }
        }

        if ($mode === 'exchange' && empty($request->input('exchangeItems', []))) {
            return $this->err('Add at least one replacement item for an exchange');
        }

        try {
            $exchangeSale = null;

            DB::transaction(function () use ($request, $sale, $mode, $returnItems, &$exchangeSale) {
                $user = Auth::guard('api')->user();

                $sale = Sale::with('items.variant.item')->whereKey($sale->id)->lockForUpdate()->first();

                if (!$sale || $sale->status !== 'completed') {
                    throw new \Exception('Sale can no longer be returned');
                }

                $allItemsFullyReturned = true;

                foreach ($returnItems as $saleItemId => $quantity) {
                    $item = $sale->items->firstWhere('id', (int) $saleItemId);

                    if (!$item) {
                        throw new \Exception('Return item not found for this sale');
                    }

                    /** @var ItemVariant|null $variant */
                    $variant = ItemVariant::whereKey($item->variant_id)->lockForUpdate()->first();
                    if (!$variant) {
                        throw new \Exception('Variant not found for returned item');
                    }

                    $oldReturnedQuantity = (int) $item->returned_quantity;
                    $newReturnedQuantity = $oldReturnedQuantity + (int) $quantity;
                    $oldStock = (int) $variant->current_stock;
                    $newStock = $oldStock + (int) $quantity;

                    $item->update(['returned_quantity' => $newReturnedQuantity]);
                    $variant->update(['current_stock' => $newStock]);

                    if ($newReturnedQuantity < (int) $item->quantity) {
                        $allItemsFullyReturned = false;
                    }

                    StockLedger::create([
                        'variant_id' => $variant->id,
                        'user_id' => $user->id,
                        'action_type' => 'return',
                        'quantity_change' => (int) $quantity,
                        'stock_before' => $oldStock,
                        'stock_after' => $newStock,
                        'reference_no' => $sale->bill_number,
                        'notes' => sprintf(
                            '%s: %s. Reason: %s',
                            $mode === 'exchange' ? 'Exchange return' : 'Return',
                            $sale->bill_number,
                            $request->reason ?? 'Not specified'
                        ),
                        'transaction_date' => now(),
                    ]);
                }

                if ($mode === 'exchange') {
                    $exchangeSale = $this->createSaleFromItems(
                        $request->input('exchangeItems', []),
                        [
                            'customerName' => $sale->customer_name,
                            'customerPan' => $sale->customer_pan,
                            'paymentMethod' => $request->input('paymentMethod', $sale->payment_method),
                            'discountAmount' => $request->input('discountAmount', 0),
                        ],
                        $user
                    );
                }

                if ($allItemsFullyReturned) {
                    $sale->update(['status' => 'returned']);
                }
            });

            return $this->ok([
                'sale' => $this->formatSale($sale->refresh()->load('creator')),
                'exchangeSale' => $exchangeSale ? $this->buildSalePayload($exchangeSale) : null,
                'message' => $mode === 'exchange'
                    ? 'Exchange processed successfully. Returned items restored and replacement sale created.'
                    : 'Return processed successfully. Stock restored.',
            ]);
        } catch (\Exception $e) {
            return $this->err($e->getMessage());
        }
    }

    // ── POST /api/v1/inventory/sales/{id}/print ─────

    /**
     * Mark sale as printed
     */
    public function markPrinted(int $id): JsonResponse
    {
        $sale = Sale::find($id);

        if (!$sale) {
            return $this->err('Sale not found', 404);
        }

        $sale->update(['printed_at' => now()]);

        return $this->ok(['sale' => $this->formatSale($sale)]);
    }
}
