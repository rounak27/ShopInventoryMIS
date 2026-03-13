<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LedgerController
 *
 * Returns stock_ledger entries shaped exactly as the JS frontend expects:
 * {
 *   id, date, itemId, variantKey, type, qty, ref, user, note
 * }
 *
 * variantKey = size + "-" + color  e.g. "M-White"
 */
class LedgerController extends Controller
{
    // ── GET /api/v1/inventory/ledger ─────────────────────────
    // Query params: search, type, item_id, date_from, date_to, per_page

    public function index(Request $request): JsonResponse
    {
        $entries = StockLedger::with(['variant.item', 'user'])
            ->when($request->type, fn ($q) =>
                $q->where('action_type', $request->type)
            )
            ->when($request->item_id, fn ($q) =>
                $q->whereHas('variant', fn ($vq) =>
                    $vq->where('item_id', $request->item_id)
                )
            )
            ->when($request->search, fn ($q) =>
                $q->whereHas('variant.item', fn ($iq) =>
                    $iq->where('name', 'like', "%{$request->search}%")
                       ->orWhere('sku',  'like', "%{$request->search}%")
                )
                ->orWhere('reference_no', 'like', "%{$request->search}%")
                ->orWhere('notes',        'like', "%{$request->search}%")
            )
            ->when($request->date_from, fn ($q) =>
                $q->whereDate('transaction_date', '>=', $request->date_from)
            )
            ->when($request->date_to, fn ($q) =>
                $q->whereDate('transaction_date', '<=', $request->date_to)
            )
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate((int) ($request->per_page ?? 50));

        $data = $entries->getCollection()->map(fn ($entry) => [
            'id'         => $entry->id,

            // date → YYYY-MM-DD string the JS Store expects
            'date'       => $entry->transaction_date instanceof \Carbon\Carbon
                ? $entry->transaction_date->toDateString()
                : (string) $entry->transaction_date,

            // itemId — JS uses this to look up the item in Store.items
            'itemId'     => $entry->variant?->item_id,

            // variantKey = "M-White" format — JS uses this as the lookup key
            'variantKey' => $entry->variant
                ? $entry->variant->size . '-' . $entry->variant->color
                : '',

            // type maps to action_type (purchase|sale|adjustment|return|damage|opening)
            'type'       => $entry->action_type,

            // qty — signed: positive = stock in, negative = stock out
            'qty'        => (int) $entry->quantity_change,

            // ref — PO reference or adjustment ref
            'ref'        => $entry->reference_no ?? '',

            // user — name of the user who performed the action
            'user'       => $entry->user?->name ?? 'System',

            // note — free text
            'note'       => $entry->notes ?? '',

            // --- extra fields for richer display (JS can safely ignore if unused) ---
            'stockBefore' => (int) $entry->stock_before,
            'stockAfter'  => (int) $entry->stock_after,
            'itemName'    => $entry->variant?->item?->name ?? '',
            'sku'         => $entry->variant?->item?->sku  ?? '',
            'variantSize' => $entry->variant?->size  ?? '',
            'variantColor'=> $entry->variant?->color ?? '',
        ])->values();

        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'total'       => $entries->total(),
                'currentPage' => $entries->currentPage(),
                'lastPage'    => $entries->lastPage(),
                'perPage'     => $entries->perPage(),
            ],
        ]);
    }
}
