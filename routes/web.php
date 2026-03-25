<?php

use Illuminate\Support\Facades\Route;
Route::get('/clear-cache', function () {

    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    Artisan::call('cache:clear');
    Artisan::call('optimize:clear');
    return response()->json([
        'success' => true,
        'message' => 'All caches cleared successfully'
    ]);

});
Route::middleware(['auth'])->get('/run-sales-migration', function () {
    Artisan::call('migrate', [
        '--path' => 'database/migrations/2026_03_24_120000_create_sales_table.php',
        '--force' => true
    ]);

    return 'Migration executed!';
});
Route::middleware(['auth'])->get('/run-sales-items-migration', function () {
    Artisan::call('migrate', [
        '--path' => 'database/migrations/2026_03_24_120001_create_sale_items_table.php',
        '--force' => true
    ]);

    return 'Migration executed!';
});
Route::get('/', [App\Http\Controllers\Web\WebController::class, 'login'])->name('home');
Route::get('/dashboard', [App\Http\Controllers\Web\WebController::class, 'dashboard'])->name('dashboard');
Route::get('/login', [App\Http\Controllers\Web\WebController::class, 'login'])->name('login');
Route::post('/login', [App\Http\Controllers\Web\WebController::class, 'post_login'])->name('login.submit');