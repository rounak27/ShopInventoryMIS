<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ItemVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * PurchaseController
 *
 * Handles creating purchase orders with multiple line items.
 *
 * On store():
 *   1. Validate all fields (including each line item)
 *   2. Create purchase header
 *   3. For each line: create purchase_item record
 *   4. For each line: lock variant row, increase current_stock
 *   5. For each line: insert stock_ledger entry (action_type = 'purchase')
 *   6. All wrapped in a single DB transaction — all-or-nothing
 *
 * Request body for store():
 * {
 *   supplierName: string,
 *   supplierId:   int|null,
 *   purchaseDate: "YYYY-MM-DD",
 *   notes:        string|null,
 *   items: [
 *     { variantId, quantity, costPricePerUnit }
 *   ]
 * }
 */
class PurchaseController extends Controller
{
    // ── Helpers ────────────────────────────────────────────────

    private function formatPurchase(Purchase $po): array
    {
        return [
            'id'           => $po->id,
            'poReference'  => $po->po_reference,
            'supplierName' => $po->supplier_name,
            'supplierId'   => $po->supplier_id,
            'purchaseDate' => $po->purchase_date instanceof \Carbon\Carbon
                ? $po->purchase_date->toDateString()
                : $po->purchase_date,
            'totalCost'    => (float) $po->total_cost,
            'notes'        => $po->notes ?? '',
            'status'       => $po->status,
            'createdBy'    => $po->creator?->name ?? 'System',
            'lineCount'    => $po->purchaseItems?->count() ?? 0,
            'items'        => ($po->relationLoaded('purchaseItems')
                ? $po->purchaseItems->map(fn ($line) => $this->formatLine($line))->values()->toArray()
                : []),
        ];
    }

    private function formatLine(PurchaseItem $line): array
    {
        return [
            'id'               => $line->id,
            'variantId'        => $line->variant_id,
            'itemName'         => $line->variant?->item?->name ?? '',
            'sku'              => $line->variant?->item?->sku  ?? '',
            'size'             => $line->variant?->size  ?? '',
            'color'            => $line->variant?->color ?? '',
            'variantKey'       => ($line->variant ? $line->variant->size . '-' . $line->variant->color : ''),
            'quantity'         => (int)   $line->quantity,
            'costPricePerUnit' => (float) $line->cost_price_per_unit,
            'totalCost'        => (float) $line->total_cost,
        ];
    }

    private function generatePoRef(): string
    {
        // Format: PO-YYMMDD-XXXXX  (unique loop guard)
        do {
            $ref = 'PO-' . now()->format('ymd') . '-' . strtoupper(substr(uniqid(), -5));
        } while (Purchase::where('po_reference', $ref)->exists());

        return $ref;
    }

    // ── GET /api/v1/inventory/purchases ───────────────────────

    public function index(Request $request): JsonResponse
    {
        // dd($request->all());
        $purchases = Purchase::with(['items.variant.item', 'creator'])
            ->when($request->search, fn ($q) =>
                $q->where('supplier_name', 'like', "%{$request->search}%")
                  ->orWhere('po_reference', 'like', "%{$request->search}%")
            )
            ->when($request->date_from, fn ($q) =>
                $q->whereDate('purchase_date', '>=', $request->date_from)
            )
            ->when($request->date_to, fn ($q) =>
                $q->whereDate('purchase_date', '<=', $request->date_to)
            )
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate((int) ($request->per_page ?? 20));

        return response()->json([
            'success' => true,
            'data'    => $purchases->getCollection()->map(fn ($p) => $this->formatPurchase($p))->values(),
            'meta'    => [
                'total'       => $purchases->total(),
                'currentPage' => $purchases->currentPage(),
                'lastPage'    => $purchases->lastPage(),
            ],
        ]);
    }

    // ── POST /api/v1/inventory/purchases ──────────────────────

    public function store(Request $request): JsonResponse
    {
        // ── 1. Validate ────────────────────────────────────────
        $v = Validator::make($request->all(), [
            'supplier'     => 'nullable|string|max:150',
            'supplierId'   => 'nullable|exists:suppliers,id',
            'date'         => 'required|date',
            'notes'        => 'nullable|string|max:500',
            'items'        => 'required|array|min:1',
            'items.*.variantKey' => 'required|exists:item_variants,id',
            'items.*.qty'       => 'required|integer|min:1',
            'items.*.costPrice' => 'required|numeric|min:0',
        ], [
            'items.required' => 'At least one purchase line item is required.',
            'items.*.variantKey.required' => 'Each line must specify a variant.',
            'items.*.qty.min' => 'Quantity must be at least 1 for each line.',
            'items.*.costPrice.min' => 'Cost price cannot be negative.',
        ]);

        if ($v->fails()) {
            return response()->json([
                'success' => false,
                'message' => $v->errors()->first(),
                'errors'  => $v->errors()->toArray(),
            ], 422);
        }

        // ── 2. Transaction ────────────────────────────────────
        try {
            $purchase = DB::transaction(function () use ($request) {
                $poRef     = $this->generatePoRef();
                $totalCost = 0;

                // Determine supplier name
                $supplierName = $request->supplier ?? null;
                // dd($supplierName);
                if ($request->supplierId && !$supplierName) {
                    $supplierName = \App\Models\Supplier::find($request->supplierId)?->name;
                }
                // dd($supplierName);
                // Create purchase header
                $purchase = Purchase::create([
                    'supplier_id'   => $request->supplierId,
                    'supplier_name' => $supplierName,
                    'created_by'    => Auth::id()??1,
                    'po_reference'  => $poRef,
                    'purchase_date' => $request->date,
                    'total_cost'    => 0, // updated after lines
                    'notes'         => $request->notes,
                    'status'        => 'received',
                ]);

                // Process each line item
                foreach ($request->items as $line) {
                    $qty      = (int) $line['qty'];
                    $costUnit = (float) $line['costPrice'];
                    $lineCost = $qty * $costUnit;

                    // Lock variant row
                    $variant = ItemVariant::lockForUpdate()->findOrFail($line['variantKey']);

                    $stockBefore = $variant->current_stock;
                    $stockAfter  = $stockBefore + $qty;

                    // Create purchase item line
                    $purchaseItem = PurchaseItem::create([
                        'purchase_id'         => $purchase->id,
                        'variant_id'          => $variant->id,
                        'quantity'            => $qty,
                        'cost_price_per_unit' => $costUnit,
                        'total_cost'          => $lineCost,
                    ]);

                    // Update stock
                    $variant->update(['current_stock' => $stockAfter]);

                    // Record in ledger
                    StockLedger::create([
                        'variant_id'       => $variant->id,
                        'user_id'          => Auth::id()??1,
                        'purchase_item_id' => $purchaseItem->id,
                        'action_type'      => 'purchase',
                        'quantity_change'  => +$qty,
                        'stock_before'     => $stockBefore,
                        'stock_after'      => $stockAfter,
                        'reference_no'     => $poRef,
                        'notes'            => $request->notes ?? "Purchase from {$supplierName}",
                        'transaction_date' => $request->date,
                        'created_at'       => now(),
                    ]);

                    $totalCost += $lineCost;
                }

                // Update total cost
                $purchase->update(['total_cost' => $totalCost]);

                return $purchase->load(['purchaseItems.variant.item', 'creator']);
            });

            return response()->json([
                'success' => true,
                'message' => "Purchase {$purchase->po_reference} saved. Stock updated for "
                            . count($request->items) . ' variant(s).',
                'data'    => $this->formatPurchase($purchase),
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}