<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
    private const VARIANT_IMAGE_DIR = 'uploads/variants';

    // ── Helpers ────────────────────────────────────────────────

    private function toImageUrl(?string $imagePath): ?string
    {
        if (empty($imagePath)) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $imagePath) === 1) {
            return $imagePath;
        }

        $normalized = ltrim(str_replace('\\', '/', $imagePath), '/');
        if (str_starts_with($normalized, 'uploads/')) {
            return asset($normalized);
        }

        // Legacy support for old storage/app/public paths.
        return asset('storage/' . $normalized);
    }

    private function storeVariantImage(UploadedFile $image): string
    {
        $uploadDir = public_path(self::VARIANT_IMAGE_DIR);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower($image->getClientOriginalExtension() ?: 'jpg');
        $filename = now()->format('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $image->move($uploadDir, $filename);

        return self::VARIANT_IMAGE_DIR . '/' . $filename;
    }

    private function deleteImageFile(?string $imagePath): void
    {
        if (empty($imagePath)) {
            return;
        }

        $normalized = ltrim(str_replace('\\', '/', $imagePath), '/');

        $publicFile = public_path($normalized);
        if (is_file($publicFile)) {
            @unlink($publicFile);
            return;
        }

        $legacyFile = storage_path('app/public/' . $normalized);
        if (is_file($legacyFile)) {
            @unlink($legacyFile);
        }
    }

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
            'sku'          => $v->sku ?? $v->item?->sku ?? '',
            'variantSku'   => $v->sku ?? $v->item?->sku ?? '',
            'itemSku'      => $v->item?->sku ?? '',
            'size'         => $v->size,
            'color'        => $v->color,
            'variantKey'   => $v->size . '-' . $v->color,   // matches JS variantKey format
            'stock'        => (int) $v->current_stock,
            'reorderLevel' => (int) $v->reorder_level,
            'price'        => (float) ($v->selling_price ?? $v->item?->selling_price ?? 0),
            'imagePath'    => $v->image_path,
            'imageUrl'     => $this->toImageUrl($v->image_path),
            'costPrice'    => (float) ($v->item?->cost_price    ?? 0),
            'sellingPrice' => (float) ($v->item?->selling_price ?? 0),
            'categoryId'   => $v->item?->category_id ?? null,
            'categoryName' => $v->item?->category?->name ?? '',
            'status'       => $status,
            'barcode'      => $v->barcode ?? '',
            'barCode'      => $v->barcode ?? '',
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
        // dd($request->all());
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
                                $q->where('barcode', 'like', "%{$request->search}%")
                                    ->orWhere('sku', 'like', "%{$request->search}%")
                  ->orWhereHas('item', fn ($iq) =>
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

    public function showByBarcode(string $barcode): JsonResponse
    {
        $variant = ItemVariant::with(['item.category'])
            ->where('barcode', $barcode)
            ->first();

        if (!$variant) {
            return $this->err('Variant not found for this barcode.', 404);
        }

        return $this->ok($this->format($variant));
    }

    // ── POST /api/v1/inventory/variants ───────────────────────

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'itemId'       => 'required|exists:items,id',
            'size'         => 'required_without:variants|string|max:20',
            'color'        => 'required_without:variants|string|max:50',
            'sku'          => 'nullable|string|max:80|unique:item_variants,sku',
            'price'        => 'required_without:variants|numeric|min:0',
            'stock'        => 'required_without:variants|integer|min:0',
            'reorderLevel' => 'nullable|integer|min:0',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',

            'variants'                 => 'nullable|array|min:1',
            'variants.*.size'          => 'required|string|max:20',
            'variants.*.color'         => 'required|string|max:50',
            'variants.*.sku'           => 'nullable|string|max:80|distinct|unique:item_variants,sku',
            'variants.*.price'         => 'required|numeric|min:0',
            'variants.*.stock'         => 'required|integer|min:0',
            'variants.*.reorderLevel'  => 'nullable|integer|min:0',
            'variants.*.image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        if ($v->fails()) {
            return $this->err($v->errors()->first());
        }

        $rows = $this->payloadRows($request);

        $created = DB::transaction(function () use ($request, $rows) {
            $created = collect();

            foreach ($rows as $index => $row) {
                $image = $request->file("variants.{$index}.image");
                if (!$image instanceof UploadedFile && count($rows) === 1) {
                    $image = $request->file('image');
                }

                $variant = ItemVariant::create([
                    'item_id'       => (int) $request->itemId,
                    'sku'           => $this->resolveSku((int) $request->itemId, $row['sku'] ?? null),
                    'size'          => $row['size'],
                    'color'         => $row['color'],
                    'current_stock' => (int) ($row['stock'] ?? 0),
                    'reorder_level' => (int) ($row['reorderLevel'] ?? 10),
                    'selling_price' => (float) ($row['price'] ?? 0),
                    'image_path'    => $image ? $this->storeVariantImage($image) : null,
                ]);

                $created->push($variant);
            }

            return $created;
        });

        $created->load(['item.category']);

        return response()->json([
            'success' => true,
            'message' => count($rows) > 1
                ? count($rows) . ' variants created successfully.'
                : 'Variant created successfully.',
            'data'    => $created->map(fn (ItemVariant $variant) => $this->format($variant))->values(),
        ], 201);
    }

    // ── PUT /api/v1/inventory/variants/{variant} ──────────────

    public function update(Request $request, ItemVariant $variant): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'size'         => 'required|string|max:20',
            'color'        => 'required|string|max:50',
            'sku'          => 'nullable|string|max:80|unique:item_variants,sku,' . $variant->id,
            'price'        => 'nullable|numeric|min:0',
            'reorderLevel' => 'nullable|integer|min:0',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'removeImage'  => 'nullable|boolean',
        ]);

        if ($v->fails()) {
            return $this->err($v->errors()->first());
        }

        $imagePath = $variant->image_path;
        if ($request->boolean('removeImage') && $imagePath) {
            $this->deleteImageFile($imagePath);
            $imagePath = null;
        }

        if ($request->file('image') instanceof UploadedFile) {
            if ($imagePath) {
                $this->deleteImageFile($imagePath);
            }
            $imagePath = $this->storeVariantImage($request->file('image'));
        }

        $variant->update([
            'size'          => $request->size,
            'color'         => $request->color,
            'sku'           => $this->resolveSku((int) $variant->item_id, $request->sku ?? $variant->sku),
            'reorder_level' => (int) ($request->reorderLevel ?? $variant->reorder_level),
            'selling_price' => (float) ($request->price ?? $variant->selling_price ?? 0),
            'image_path'    => $imagePath,
        ]);

        $variant->load(['item.category']);

        return response()->json([
            'success' => true,
            'message' => 'Variant updated successfully.',
            'data'    => $this->format($variant),
        ]);
    }

    private function payloadRows(Request $request): array
    {
        if (is_array($request->variants) && count($request->variants) > 0) {
            return $request->variants;
        }

        return [[
            'size' => $request->size,
            'color' => $request->color,
            'sku' => $request->sku,
            'price' => $request->price,
            'stock' => $request->stock,
            'reorderLevel' => $request->reorderLevel,
        ]];
    }

    private function resolveSku(int $itemId, ?string $requestedSku = null): string
    {
        if (!empty($requestedSku)) {
            return strtoupper(trim($requestedSku));
        }

        $itemSku = Item::whereKey($itemId)->value('sku') ?? ('ITEM' . $itemId);
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $itemSku));
        if ($prefix === '') {
            $prefix = 'ITEM' . $itemId;
        }

        do {
            $candidate = $prefix . '-D' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while (ItemVariant::where('sku', $candidate)->exists());

        return $candidate;
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

        $this->deleteImageFile($variant->image_path);

        $variant->delete();

        return response()->json(['success' => true, 'message' => 'Variant deleted successfully.']);
    }
}