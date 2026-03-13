<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * SupplierController
 *
 * Standard CRUD for suppliers.
 * Includes a `purchaseCount` so the UI can show how many POs
 * have been raised against each supplier.
 */
class SupplierController extends Controller
{
    // ── Helpers ────────────────────────────────────────────────

    private function format(Supplier $s): array
    {
        return [
            'id'            => $s->id,
            'name'          => $s->name,
            'contactPerson' => $s->contact_person ?? '',
            'phone'         => $s->phone          ?? '',
            'email'         => $s->email          ?? '',
            'address'       => $s->address        ?? '',
            'isActive'      => (bool) $s->is_active,
            'purchaseCount' => (int) ($s->purchases_count ?? 0),
            'createdAt'     => $s->created_at?->toDateString(),
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

    // ── GET /api/v1/inventory/suppliers ───────────────────────

    public function index(Request $request): JsonResponse
    {
        $suppliers = Supplier::withCount('purchases')
            ->when($request->search, fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
            )
            ->when($request->has('active'), fn ($q) =>
                $q->where('is_active', (bool) $request->active)
            )
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => $this->format($s));

        return $this->ok($suppliers);
    }

    // ── GET /api/v1/inventory/suppliers/{supplier} ────────────

    public function show(Supplier $supplier): JsonResponse
    {
        $supplier->loadCount('purchases');
        return $this->ok($this->format($supplier));
    }

    // ── POST /api/v1/inventory/suppliers ──────────────────────

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name'          => 'required|string|max:150',
            'contactPerson' => 'nullable|string|max:100',
            'phone'         => 'nullable|string|max:20',
            'email'         => 'nullable|email|max:150',
            'address'       => 'nullable|string|max:500',
        ]);

        if ($v->fails()) {
            return $this->err($v->errors()->first());
        }

        $supplier = Supplier::create([
            'name'           => $request->name,
            'contact_person' => $request->contactPerson,
            'phone'          => $request->phone,
            'email'          => $request->email,
            'address'        => $request->address,
            'is_active'      => true,
        ]);

        $supplier->loadCount('purchases');

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully.',
            'data'    => $this->format($supplier),
        ], 201);
    }

    // ── PUT /api/v1/inventory/suppliers/{supplier} ────────────

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name'          => 'required|string|max:150',
            'contactPerson' => 'nullable|string|max:100',
            'phone'         => 'nullable|string|max:20',
            'email'         => 'nullable|email|max:150',
            'address'       => 'nullable|string|max:500',
            'isActive'      => 'nullable|boolean',
        ]);

        if ($v->fails()) {
            return $this->err($v->errors()->first());
        }

        $supplier->update([
            'name'           => $request->name,
            'contact_person' => $request->contactPerson,
            'phone'          => $request->phone,
            'email'          => $request->email,
            'address'        => $request->address,
            'is_active'      => $request->has('isActive') ? (bool) $request->isActive : $supplier->is_active,
        ]);

        $supplier->loadCount('purchases');

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully.',
            'data'    => $this->format($supplier),
        ]);
    }

    // ── DELETE /api/v1/inventory/suppliers/{supplier} ─────────

    public function destroy(Supplier $supplier): JsonResponse
    {
        // Soft-delete via is_active flag instead of hard delete
        // to preserve purchase history integrity
        $supplier->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier deactivated successfully.',
        ]);
    }
}