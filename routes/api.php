<?php
use App\Http\Controllers\Api\Inventory\CategoryController;
use App\Http\Controllers\Api\Inventory\ItemController;
use App\Http\Controllers\Api\Inventory\LedgerController;
use App\Http\Controllers\Api\Inventory\PurchaseController;
use App\Http\Controllers\Api\Inventory\StockController;
use App\Http\Controllers\Api\Inventory\SupplierController;
use App\Http\Controllers\Api\Inventory\VariantController;
use Illuminate\Support\Facades\Route;
Route::prefix('v1/inventory')->group(function () {

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