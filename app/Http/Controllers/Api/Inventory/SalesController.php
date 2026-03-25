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
            'saleDate' => $sale->sale_date->format('Y-m-d'),
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
            'pricePerUnit' => (float) $item->price_per_unit,
            'costPrice' => (float) $item->cost_price,
            'profit' => (float) $item->profit,
            'discountAmount' => (float) $item->discount_amount,
            'totalPrice' => (float) $item->total_price,
        ];
    }

    /**
     * Format invoice for printing
     */
    private function formatInvoice(Sale $sale): array
    {
        return [
            'billNumber' => $sale->bill_number,
            'fiscalYear' => $sale->fiscal_year,
            'saleDate' => $sale->sale_date->format('Y-m-d'),
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
                // Get or generate fiscal year
                $fiscalYear = FiscalYearHelper::getCurrentFiscalYear();

                // Get current user
                $user = Auth::guard('api')->user();

                // Process items
                $items = [];
                $subTotal = 0;
                $totalProfit = 0;

                foreach ($request->items as $itemData) {
                    // Resolve variant
                    $variant = ItemVariant::with('item')
                        ->where(function ($q) use ($itemData) {
                            if (!empty($itemData['barcode'])) {
                                $q->where('barcode', $itemData['barcode']);
                            } elseif (!empty($itemData['variantId'])) {
                                $q->where('id', $itemData['variantId']);
                            }
                        })
                        ->lockForUpdate()
                        ->first();

                    if (!$variant) {
                        throw new \Exception("Variant not found for: " . json_encode($itemData));
                    }

                    // Validate stock
                    if ($variant->current_stock < $itemData['quantity']) {
                        throw new \Exception(
                            "Insufficient stock for {$variant->item->name} ({$variant->size}/{$variant->color}). " .
                            "Available: {$variant->current_stock}, Requested: {$itemData['quantity']}"
                        );
                    }

                    // Get price (use override or selling price)
                    $unitPrice = $itemData['priceOverride'] ?? $variant->item->selling_price;

                    // Calculate item total
                    $itemTotal = $unitPrice * $itemData['quantity'];
                    $subTotal += $itemTotal;

                    // Calculate profit
                    $costPrice = $variant->item->cost_price;
                    $profit = ($unitPrice - $costPrice) * $itemData['quantity'];
                    $totalProfit += $profit;

                    $items[] = [
                        'variant' => $variant,
                        'quantity' => $itemData['quantity'],
                        'unitPrice' => $unitPrice,
                        'costPrice' => $costPrice,
                        'profit' => $profit,
                        'total' => $itemTotal,
                    ];
                }

                // Calculate totals
                $discountAmount = (float) ($request->discountAmount ?? 0);
                $taxableAmount = $subTotal - $discountAmount;
                $vat = round($taxableAmount * self::VAT_RATE, 2);
                $grandTotal = $taxableAmount + $vat;

                // Generate bill number
                $billNo = FiscalYearHelper::getNextBillNumber($fiscalYear);
                $billNumber = FiscalYearHelper::formatBillNumber($fiscalYear, $billNo);

                // Create sale
                $sale = Sale::create([
                    'customer_name' => $request->customerName,
                    'customer_pan' => $request->customerPan,
                    'bill_number' => $billNumber,
                    'fiscal_year' => $fiscalYear,
                    'created_by' => $user->id,
                    'sub_total' => $subTotal,
                    'discount_amount' => $discountAmount,
                    'taxable_amount' => $taxableAmount,
                    'vat' => $vat,
                    'grand_total' => $grandTotal,
                    'payment_method' => $request->paymentMethod,
                    'status' => 'completed',
                    'sale_date' => Carbon::now()->toDateString(),
                ]);

                // Insert sale items and create ledger entries
                foreach ($items as $itemData) {
                    $variant = $itemData['variant'];
                    $oldStock = $variant->current_stock;
                    $newStock = $oldStock - $itemData['quantity'];

                    // Create sale item
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'variant_id' => $variant->id,
                        'quantity' => $itemData['quantity'],
                        'price_per_unit' => $itemData['unitPrice'],
                        'cost_price' => $itemData['costPrice'],
                        'profit' => $itemData['profit'],
                        'discount_amount' => 0, // Line item discount not yet implemented
                        'total_price' => $itemData['total'],
                    ]);

                    // Deduct stock
                    $variant->update(['current_stock' => $newStock]);

                    // Create stock ledger entry
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

                return $sale;
            });

            return $this->ok([
                'sale' => $this->formatSale($sale),
                'items' => $sale->items->map(fn ($i) => $this->formatSaleItem($i))->toArray(),
                'invoice' => $this->formatInvoice($sale),
            ]);
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
        $sale = Sale::with('items.variant')->find($id);

        if (!$sale) {
            return $this->err('Sale not found', 404);
        }

        if ($sale->status !== 'completed') {
            return $this->err("Cannot return a {$sale->status} sale");
        }

        try {
            DB::transaction(function () use ($request, $sale) {
                $user = Auth::guard('api')->user();

                // Restore stock for each item
                foreach ($sale->items as $item) {
                    $variant = $item->variant;
                    $oldStock = $variant->current_stock;
                    $newStock = $oldStock + $item->quantity;

                    // Update variant stock
                    $variant->update(['current_stock' => $newStock]);

                    // Create reverse ledger entry
                    StockLedger::create([
                        'variant_id' => $variant->id,
                        'user_id' => $user->id,
                        'action_type' => 'return',
                        'quantity_change' => $item->quantity,
                        'stock_before' => $oldStock,
                        'stock_after' => $newStock,
                        'reference_no' => $sale->bill_number,
                        'notes' => "Return: {$sale->bill_number}. Reason: " . ($request->reason ?? 'Not specified'),
                        'transaction_date' => now(),
                    ]);
                }

                // Mark sale as returned
                $sale->update(['status' => 'returned']);
            });

            return $this->ok([
                'sale' => $this->formatSale($sale),
                'message' => 'Sale returned successfully. Stock restored.',
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
