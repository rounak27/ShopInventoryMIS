<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Inventory\CategoryController;
use App\Http\Controllers\Api\Inventory\ItemController;
use App\Http\Controllers\Api\Inventory\LedgerController;
use App\Http\Controllers\Api\Inventory\PurchaseController;
use App\Http\Controllers\Api\Inventory\StockController;
use App\Http\Controllers\Api\Inventory\SupplierController;
use App\Http\Controllers\Api\Inventory\VariantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('jwt.test')->get('/test', function() {
        return response()->json(['ok' => true]);
    });   
    Route::post('/auth/login', [AuthController::class,'login']);
    Route::middleware('jwt.auth')->group(function () {
        Route::post('/auth/logout', [AuthController::class,'logout']);
        Route::prefix('inventory')->group(function () {

            Route::apiResource('categories', CategoryController::class);

            Route::apiResource('items', ItemController::class);

            Route::apiResource('variants', VariantController::class);

            Route::apiResource('suppliers', SupplierController::class);

            Route::get('ledger', [LedgerController::class,'index']);

            Route::post('stock/adjust', [StockController::class,'adjust']);

            Route::apiResource('purchases', PurchaseController::class)->only([
                'index','store'
            ]);

        });
    });
});