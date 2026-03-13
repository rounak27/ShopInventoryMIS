<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ItemVariant;
use App\Models\StockLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * StockController
 *
 * Handles manual stock mutations outside of purchases.
 *
 * POST /api/v1/inventory/stock/adjust
 *
 * Three operation modes controlled by the `operation` field:
 *
 *   "in"         — add stock (e.g. manual receive, return from customer)
 *   "out"        — remove stock (e.g. manual removal, damage write-off)
 *   "adjustment" — set stock to a specific physical count
 *                  (diff is calculated automatically and logged)
 *
 * Request body:
 * {
 *   variantId:  int,          required
 *   operation:  string,       required  — "in" | "out" | "adjustment"
 *   quantity:   int,          required  — qty to add/remove  (operation: in|out)
 *   actualQty:  int,          required  — physical count      (operation: adjustment)
 *   reason:     string,       required for "out" and "adjustment"
 *   date:       "YYYY-MM-DD", optional  — defaults to today
 *   note:       string,       optional
 * }
 *
 * Every mutation is:
 *   1. Wrapped in DB::transaction
 *   2. Row-locked with lockForUpdate() to prevent race conditions
 *   3. Recorded in stock_ledger with stock_before + stock_after snapshot
 */
class StockController extends Controller
{
    // ── POST /api/v1/inventory/stock/adjust ───────────────────

    public function adjust(Request $request): JsonResponse
    {
        // ── Determine which validation rules to apply ──────────
        $operation = $request->operation ?? 'adjustment';

        $baseRules = [
            'variantId' => 'required|exists:item_variants,id',
            'operation' => 'required|in:in,out,adjustment',
            'date'      => 'nullable|date',
            'note'      => 'nullable|string|max:500',
        ];

        $operationRules = match ($operation) {
            'in'  => [
                'quantity' => 'required|integer|min:1',
                'reason'   => 'nullable|string|max:250',
            ],
            'out' => [
                'quantity' => 'required|integer|min:1',
                'reason'   => 'required|string|max:250',
            ],
            'adjustment' => [
                'actualQty' => 'required|integer|min:0',
                'reason'    => 'required|string|max:250',
            ],
            default => [],
        };

        $v = Validator::make($request->all(), array_merge($baseRules, $operationRules), [
            'variantId.required' => 'Please select a variant.',
            'variantId.exists'   => 'The selected variant does not exist.',
            'quantity.required'  => 'Quantity is required.',
            'quantity.min'       => 'Quantity must be at least 1.',
            'actualQty.required' => 'Physical count is required for adjustment.',
            'actualQty.min'      => 'Physical count cannot be negative.',
            'reason.required'    => 'A reason is required for this operation.',
        ]);

        if ($v->fails()) {
            return response()->json([
                'success' => false,
                'message' => $v->errors()->first(),
                'errors'  => $v->errors()->toArray(),
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($request, $operation) {
                // Lock variant row — prevents concurrent stock mutations
                $variant = ItemVariant::lockForUpdate()->findOrFail($request->variantId);

                $date        = $request->date ?? today()->toDateString();
                $note        = $request->note ?? $request->reason ?? '';
                $stockBefore = $variant->current_stock;

                return match ($operation) {
                    'in'         => $this->doStockIn($variant, $request, $date, $note, $stockBefore),
                    'out'        => $this->doStockOut($variant, $request, $date, $note, $stockBefore),
                    'adjustment' => $this->doAdjustment($variant, $request, $date, $note, $stockBefore),
                };
            });

            return response()->json([
                'success'    => true,
                'message'    => $result['message'],
                'data'       => $result['data'],
            ]);

        } catch (\RuntimeException $e) {
            // Business logic error (e.g. insufficient stock)
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stock update failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── Private operation handlers ─────────────────────────────

    /**
     * Add stock to a variant (manual receive, return, opening stock, etc.)
     */
    private function doStockIn(
        ItemVariant $variant,
        Request     $request,
        string      $date,
        string      $note,
        int         $stockBefore
    ): array {
        $qty        = (int) $request->quantity;
        $stockAfter = $stockBefore + $qty;

        $variant->update(['current_stock' => $stockAfter]);

        $entry = $this->writeLedger(
            variant:     $variant,
            actionType:  'purchase',     // 'purchase' covers manual stock-in
            qtyChange:   +$qty,
            before:      $stockBefore,
            after:       $stockAfter,
            ref:         $this->generateRef('IN'),
            note:        $note ?: ($request->reason ?? 'Manual stock in'),
            date:        $date
        );

        return [
            'message' => "Stock increased by {$qty} unit(s). New stock: {$stockAfter}.",
            'data'    => $this->formatResult($variant, $entry),
        ];
    }

    /**
     * Remove stock from a variant (sale, damage, loss, etc.)
     */
    private function doStockOut(
        ItemVariant $variant,
        Request     $request,
        string      $date,
        string      $note,
        int         $stockBefore
    ): array {
        $qty = (int) $request->quantity;

        if ($qty > $stockBefore) {
            throw new \RuntimeException(
                "Insufficient stock. Available: {$stockBefore} unit(s), Requested: {$qty}."
            );
        }

        $stockAfter = $stockBefore - $qty;
        $variant->update(['current_stock' => $stockAfter]);

        // Map reason to action_type where possible
        $reason     = strtolower($request->reason ?? '');
        $actionType = match (true) {
            str_contains($reason, 'damage') || str_contains($reason, 'defect') => 'damage',
            str_contains($reason, 'return')  => 'return',
            str_contains($reason, 'sale')    => 'sale',
            default                          => 'sale',
        };

        $entry = $this->writeLedger(
            variant:    $variant,
            actionType: $actionType,
            qtyChange:  -$qty,
            before:     $stockBefore,
            after:      $stockAfter,
            ref:        $this->generateRef('OUT'),
            note:       $note ?: $request->reason,
            date:       $date
        );

        return [
            'message' => "Stock reduced by {$qty} unit(s). New stock: {$stockAfter}.",
            'data'    => $this->formatResult($variant, $entry),
        ];
    }

    /**
     * Physical count adjustment — set stock to actualQty, log the difference.
     * Returns null ledger entry (and info message) when no change is needed.
     */
    private function doAdjustment(
        ItemVariant $variant,
        Request     $request,
        string      $date,
        string      $note,
        int         $stockBefore
    ): array {
        $actualQty = (int) $request->actualQty;
        $diff      = $actualQty - $stockBefore;

        if ($diff === 0) {
            return [
                'message' => 'No change — system stock already matches the physical count.',
                'data'    => [
                    'variantId'   => $variant->id,
                    'stockBefore' => $stockBefore,
                    'stockAfter'  => $actualQty,
                    'diff'        => 0,
                    'ledgerEntry' => null,
                ],
            ];
        }

        $variant->update(['current_stock' => $actualQty]);

        $entry = $this->writeLedger(
            variant:    $variant,
            actionType: 'adjustment',
            qtyChange:  $diff,
            before:     $stockBefore,
            after:      $actualQty,
            ref:        $this->generateRef('ADJ'),
            note:       $note ?: $request->reason,
            date:       $date
        );

        $direction = $diff > 0 ? "+{$diff}" : "{$diff}";
        return [
            'message' => "Stock adjusted ({$direction} units). New stock: {$actualQty}.",
            'data'    => $this->formatResult($variant, $entry),
        ];
    }

    // ── Ledger writer ─────────────────────────────────────────

    /**
     * Insert a single row into stock_ledger.
     * Called by all three operation handlers — single source of truth for ledger writes.
     */
    private function writeLedger(
        ItemVariant $variant,
        string      $actionType,
        int         $qtyChange,
        int         $before,
        int         $after,
        string      $ref,
        string      $note,
        string      $date
    ): StockLedger {
        return StockLedger::create([
            'variant_id'      => $variant->id,
            'user_id'         => Auth::id(),
            'action_type'     => $actionType,
            'quantity_change' => $qtyChange,
            'stock_before'    => $before,
            'stock_after'     => $after,
            'reference_no'    => $ref,
            'notes'           => $note,
            'transaction_date'=> $date,
            'created_at'      => now(),
        ]);
    }

    // ── Response formatters ────────────────────────────────────

    /**
     * Format the response data — shaped so the JS Store can update
     * both the variant stock and push a new ledger entry in one shot.
     */
    private function formatResult(ItemVariant $variant, StockLedger $entry): array
    {
        $variant->load('item');

        return [
            // Updated variant
            'variant' => [
                'id'         => $variant->id,
                'itemId'     => $variant->item_id,
                'itemName'   => $variant->item?->name ?? '',
                'sku'        => $variant->item?->sku  ?? '',
                'size'       => $variant->size,
                'color'      => $variant->color,
                'variantKey' => $variant->size . '-' . $variant->color,
                'stock'      => (int) $variant->current_stock,
            ],

            // New ledger entry — shaped exactly like JS frontend expects
            'ledgerEntry' => [
                'id'          => $entry->id,
                'date'        => $entry->transaction_date instanceof \Carbon\Carbon
                    ? $entry->transaction_date->toDateString()
                    : (string) $entry->transaction_date,
                'itemId'      => $variant->item_id,
                'variantKey'  => $variant->size . '-' . $variant->color,
                'type'        => $entry->action_type,
                'qty'         => (int) $entry->quantity_change,
                'ref'         => $entry->reference_no ?? '',
                'user'        => Auth::user()?->name ?? 'System',
                'note'        => $entry->notes ?? '',
                'stockBefore' => (int) $entry->stock_before,
                'stockAfter'  => (int) $entry->stock_after,
            ],
        ];
    }

    // ── Helpers ────────────────────────────────────────────────

    private function generateRef(string $prefix): string
    {
        return $prefix . '-' . now()->format('ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }
}