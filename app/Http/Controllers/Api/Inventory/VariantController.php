<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ItemVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * VariantController
 *
 * Manages Size/Color variants for items.
 * Route: /api/v1/inventory/variants (apiResource)
 *
 * Response shape:
 * { id, itemId, size, color, stock, reorderLevel, status }
 *
 * `status` is derived from current_stock vs reorder_level so the
 * frontend can display the correct badge without extra logic.
 */
class VariantController extends Controller
{
    // ── Helpers ────────────────────────────────────────────────

    private function format(ItemVariant $v): array
    {
        $status = 'in_stock';
        if ($v->current_stock === 0) {
            $status = 'out_of_stock';
        } elseif ($v->current_stock <= $v->reorder_level) {
            $status = 'low_stock';
        }

        return [
            'id'           => $v->id,
            'itemId'       => $v->item_id,
            'itemName'     => $v->item?->name ?? '',
            'sku'          => $v->item?->sku  ?? '',
            'size'         => $v->size,
            'color'        => $v->color,
            'variantKey'   => $v->size . '-' . $v->color,   // matches JS variantKey format
            'stock'        => (int) $v->current_stock,
            'reorderLevel' => (int) $v->reorder_level,
            'costPrice'    => (float) ($v->item?->cost_price    ?? 0),
            'sellingPrice' => (float) ($v->item?->selling_price ?? 0),
            'categoryId'   => $v->item?->category_id ?? null,
            'categoryName' => $v->item?->category?->name ?? '',
            'status'       => $status,
        ];
    }

    private function ok(mixed $data): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data]);
    }

    private function err(string $msg, int $status = 422): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $msg], $status);
    }

    // ── GET /api/v1/inventory/variants ────────────────────────
    // Query params: item_id, category_id, status (in_stock|low_stock|out_of_stock), search

    public function index(Request $request): JsonResponse
    {
        $query = ItemVariant::with(['item.category'])
            ->when($request->item_id, fn ($q) =>
                $q->where('item_id', $request->item_id)
            )
            ->when($request->category_id, fn ($q) =>
                $q->whereHas('item', fn ($iq) =>
                    $iq->where('category_id', $request->category_id)
                )
            )
            ->when($request->search, fn ($q) =>
                $q->whereHas('item', fn ($iq) =>
                    $iq->where('name', 'like', "%{$request->search}%")
                       ->orWhere('sku',  'like', "%{$request->search}%")
                )
            )
            ->when($request->status === 'in_stock',    fn ($q) => $q->where('current_stock', '>', 0)->whereColumn('current_stock', '>', 'reorder_level'))
            ->when($request->status === 'low_stock',   fn ($q) => $q->where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'reorder_level'))
            ->when($request->status === 'out_of_stock',fn ($q) => $q->where('current_stock', 0));

        $variants = $query->orderBy('id')->paginate((int) ($request->per_page ?? 50));

        return response()->json([
            'success' => true,
            'data'    => $variants->getCollection()->map(fn ($v) => $this->format($v))->values(),
            'meta'    => [
                'total'       => $variants->total(),
                'currentPage' => $variants->currentPage(),
                'lastPage'    => $variants->lastPage(),
            ],
        ]);
    }

    // ── GET /api/v1/inventory/variants/{variant} ──────────────

    public function show(ItemVariant $variant): JsonResponse
    {
        $variant->load(['item.category']);
        return $this->ok($this->format($variant));
    }

    // ── POST /api/v1/inventory/variants ───────────────────────

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'itemId'       => 'required|exists:items,id',
            'size'         => 'required|string|max:20',
            'color'        => 'required|string|max:50',
            'stock'        => 'required|integer|min:0',
            'reorderLevel' => 'nullable|integer|min:0',
        ]);

        if ($v->fails()) {
            return $this->err($v->errors()->first());
        }

        // Prevent duplicate size+color for same item
        $exists = ItemVariant::where('item_id', $request->itemId)
            ->where('size',  $request->size)
            ->where('color', $request->color)
            ->exists();

        if ($exists) {
            return $this->err("Variant {$request->size}/{$request->color} already exists for this item.", 409);
        }

        $variant = ItemVariant::create([
            'item_id'       => $request->itemId,
            'size'          => $request->size,
            'color'         => $request->color,
            'current_stock' => (int) $request->stock,
            'reorder_level' => (int) ($request->reorderLevel ?? 10),
        ]);

        $variant->load(['item.category']);

        return response()->json([
            'success' => true,
            'message' => 'Variant created successfully.',
            'data'    => $this->format($variant),
        ], 201);
    }

    // ── PUT /api/v1/inventory/variants/{variant} ──────────────

    public function update(Request $request, ItemVariant $variant): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'size'         => 'required|string|max:20',
            'color'        => 'required|string|max:50',
            'reorderLevel' => 'nullable|integer|min:0',
        ]);

        if ($v->fails()) {
            return $this->err($v->errors()->first());
        }

        // Check duplicate (exclude self)
        $duplicate = ItemVariant::where('item_id', $variant->item_id)
            ->where('size',  $request->size)
            ->where('color', $request->color)
            ->where('id', '!=', $variant->id)
            ->exists();

        if ($duplicate) {
            return $this->err("A variant with size {$request->size} / color {$request->color} already exists.", 409);
        }

        $variant->update([
            'size'          => $request->size,
            'color'         => $request->color,
            'reorder_level' => (int) ($request->reorderLevel ?? $variant->reorder_level),
        ]);

        $variant->load(['item.category']);

        return response()->json([
            'success' => true,
            'message' => 'Variant updated successfully.',
            'data'    => $this->format($variant),
        ]);
    }

    // ── DELETE /api/v1/inventory/variants/{variant} ───────────

    public function destroy(ItemVariant $variant): JsonResponse
    {
        if ($variant->stockLedgers()->exists()) {
            return $this->err(
                'Cannot delete variant — stock ledger entries reference it. Archive the item instead.',
                409
            );
        }

        $variant->delete();

        return response()->json(['success' => true, 'message' => 'Variant deleted successfully.']);
    }
}