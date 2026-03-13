<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * CategoryController
 *
 * Handles CRUD for categories.
 * Each response includes `itemCount` so the JS frontend Store can display it.
 *
 * Response shape:
 *   { id, name, description, createdAt, itemCount }
 */
class CategoryController extends Controller
{
    // ── Helpers ────────────────────────────────────────────────

    /**
     * Format a single Category model into the shape the JS frontend expects.
     */
    private function format(Category $cat): array
    {
        return [
            'id'          => $cat->id,
            'name'        => $cat->name,
            'description' => $cat->description ?? '',
            'createdAt'   => $cat->created_at?->toDateString(),
            'itemCount'   => (int) ($cat->items_count ?? $cat->items()->count()),
        ];
    }

    private function ok(mixed $data): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data]);
    }

    private function msg(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message], $status);
    }

    private function err(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    // ── GET /api/v1/inventory/categories ──────────────────────

    public function index(): JsonResponse
    {
        $categories = Category::withCount('items')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => $this->format($c));
        // dd($categories);
        return $this->ok($categories);
    }

    // ── POST /api/v1/inventory/categories ─────────────────────

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name'        => 'required|string|max:100|unique:categories,name',
            'description' => 'nullable|string|max:500',
        ]);

        if ($v->fails()) {
            return $this->err($v->errors()->first());
        }

        $category = Category::create([
            'name'        => $request->name,
            'description' => $request->description,
        ]);

        // Return the newly created record so the JS Store can push it
        $category->loadCount('items');
        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data'    => $this->format($category),
        ], 201);
    }

    // ── PUT /api/v1/inventory/categories/{category} ──────────

    public function update(Request $request, Category $category): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name'        => "required|string|max:100|unique:categories,name,{$category->id}",
            'description' => 'nullable|string|max:500',
        ]);

        if ($v->fails()) {
            return $this->err($v->errors()->first());
        }

        $category->update([
            'name'        => $request->name,
            'description' => $request->description,
        ]);

        $category->loadCount('items');
        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data'    => $this->format($category),
        ]);
    }

    // ── DELETE /api/v1/inventory/categories/{category} ───────

    public function destroy(Category $category): JsonResponse
    {
        // Guard: prevent deletion if items exist
        if ($category->items()->exists()) {
            return $this->err(
                "Cannot delete \"{$category->name}\" — items are linked to it. Reassign them first.",
                409
            );
        }

        $category->delete();

        return $this->msg('Category deleted successfully.');
    }
}