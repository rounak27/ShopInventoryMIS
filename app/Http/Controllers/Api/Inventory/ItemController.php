<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * ItemController
 *
 * Full CRUD for items.  Every response includes nested `variants` so
 * the JS Store can hydrate the complete product object in one call.
 *
 * Response shape per item:
 * {
 *   id, name, sku, categoryId, brand,
 *   costPrice, sellingPrice, description, emoji,
 *   variants: [ { size, color, stock } ]
 * }
 *
 * The `emoji` field is mapped from the item's category name for display
 * purposes (the DB does not store emoji; it is derived in the API layer).
 */
class ItemController extends Controller
{
    // ── Category → emoji map (mirrors frontend Store) ─────────
    private const CATEGORY_EMOJI = [
        "men's wear"   => '👔',
        "women's wear" => '👗',
        'kids'         => '👕',
        'ethnic'       => '🥻',
        'accessories'  => '🪢',
        'footwear'     => '👟',
    ];

    // ── Helpers ────────────────────────────────────────────────

    private function format(Item $item): array
    {
        $catName = strtolower($item->category?->name ?? '');
        $emoji   = self::CATEGORY_EMOJI[$catName] ?? '📦';

        return [
            'id'           => $item->id,
            'name'         => $item->name,
            'sku'          => $item->sku,
            'categoryId'   => $item->category_id,
            'brand'        => $item->brand ?? '',
            'costPrice'    => (float) $item->cost_price,
            'sellingPrice' => (float) $item->selling_price,
            'description'  => $item->description ?? '',
            'category'     => $item->category?->name ?? '',
            'emoji'        => $emoji,
            'variants'     => $item->variants->map(fn ($v) => [
                'id'    => $v->id,
                'size'  => $v->size,
                'color' => $v->color,
                'stock' => (int) $v->current_stock,
            ])->values()->toArray(),
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

    // ── GET /api/v1/inventory/items ───────────────────────────
    // Query params: search, category_id, page, per_page

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) ($request->per_page ?? 50);

        $items = Item::with(['category', 'variants'])
            ->when($request->search, fn ($q) =>
                $q->where('name',  'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%")
                  ->orWhere('brand','like',"%{$request->search}%")
            )
            ->when($request->category_id, fn ($q) =>
                $q->where('category_id', $request->category_id)
            )
            ->orderBy('name')
            ->paginate($perPage);
        // dd($items->first());
        return response()->json([
            'success' => true,
            'data'    => $items->getCollection()->map(fn ($i) => $this->format($i))->values(),
            'meta'    => [
                'total'       => $items->total(),
                'perPage'     => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'lastPage'    => $items->lastPage(),
            ],
        ]);
    }

    // ── POST /api/v1/inventory/items ──────────────────────────
    // Body: { name, sku, categoryId, brand, costPrice, sellingPrice,
    //         description, variants: [{size,color,stock,reorderLevel?}] }

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name'              => 'required|string|max:150',
            'sku'               => 'required|string|max:50|unique:items,sku',
            'categoryId'        => 'required|exists:categories,id',
            'brand'             => 'nullable|string|max:100',
            'costPrice'         => 'required|numeric|min:0',
            'sellingPrice'      => 'required|numeric|min:0',
            'description'       => 'nullable|string|max:1000',
            'variants'          => 'nullable|array',
            'variants.*.size'   => 'required|string|max:20',
            'variants.*.color'  => 'required|string|max:50',
            'variants.*.stock'  => 'required|integer|min:0',
        ]);

        if ($v->fails()) {
            return $this->err($v->errors()->first());
        }

        try {
            $item = DB::transaction(function () use ($request) {
                $item = Item::create([
                    'name'          => $request->name,
                    'sku'           => $request->sku,
                    'category_id'   => $request->categoryId,
                    'brand'         => $request->brand,
                    'cost_price'    => $request->costPrice,
                    'selling_price' => $request->sellingPrice,
                    'description'   => $request->description,
                ]);

                foreach ((array) $request->variants as $row) {
                    ItemVariant::create([
                        'item_id'       => $item->id,
                        'size'          => $row['size'],
                        'color'         => $row['color'] ?? 'N/A',
                        'current_stock' => (int) ($row['stock'] ?? 0),
                        'reorder_level' => (int) ($row['reorderLevel'] ?? 10),
                    ]);
                }

                return $item->load(['category', 'variants']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Item created successfully.',
                'data'    => $this->format($item),
            ], 201);

        } catch (\Throwable $e) {
            return $this->err('Failed to create item: ' . $e->getMessage(), 500);
        }
    }

    // ── PUT /api/v1/inventory/items/{item} ────────────────────

    public function update(Request $request, Item $item): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name'              => 'required|string|max:150',
            'sku'               => "required|string|max:50|unique:items,sku,{$item->id}",
            'categoryId'        => 'required|exists:categories,id',
            'brand'             => 'nullable|string|max:100',
            'costPrice'         => 'required|numeric|min:0',
            'sellingPrice'      => 'required|numeric|min:0',
            'description'       => 'nullable|string|max:1000',
            'variants'          => 'nullable|array',
            'variants.*.size'   => 'required|string|max:20',
            'variants.*.color'  => 'required|string|max:50',
        ]);

        if ($v->fails()) {
            return $this->err($v->errors()->first());
        }

        try {
            DB::transaction(function () use ($request, $item) {
                $item->update([
                    'name'          => $request->name,
                    'sku'           => $request->sku,
                    'category_id'   => $request->categoryId,
                    'brand'         => $request->brand,
                    'cost_price'    => $request->costPrice,
                    'selling_price' => $request->sellingPrice,
                    'description'   => $request->description,
                ]);

                // Sync variants only if explicitly sent
                if ($request->has('variants')) {
                    foreach ((array) $request->variants as $row) {
                        // Match by size+color; upsert so existing stock is preserved
                        $variant = ItemVariant::firstOrNew([
                            'item_id' => $item->id,
                            'size'    => $row['size'],
                            'color'   => $row['color'] ?? 'N/A',
                        ]);

                        if (!$variant->exists) {
                            $variant->current_stock = (int) ($row['stock'] ?? 0);
                        }

                        $variant->reorder_level = (int) ($row['reorderLevel'] ?? $variant->reorder_level ?? 10);
                        $variant->save();
                    }
                }
            });

            $item->load(['category', 'variants']);

            return response()->json([
                'success' => true,
                'message' => 'Item updated successfully.',
                'data'    => $this->format($item),
            ]);

        } catch (\Throwable $e) {
            return $this->err('Failed to update item: ' . $e->getMessage(), 500);
        }
    }

    // ── DELETE /api/v1/inventory/items/{item} ─────────────────

    public function destroy(Item $item): JsonResponse
    {
        try {
            DB::transaction(function () use ($item) {
                // Cascade: ledger entries and variants deleted by DB cascade
                if ($item->image_path) {
                    Storage::disk('public')->delete($item->image_path);
                }
                $item->delete();
            });

            return response()->json(['success' => true, 'message' => 'Item deleted successfully.']);

        } catch (\Throwable $e) {
            return $this->err('Failed to delete item: ' . $e->getMessage(), 500);
        }
    }
}