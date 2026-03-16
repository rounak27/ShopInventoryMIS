<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/dashboard', [App\Http\Controllers\Web\WebController::class, 'dashboard'])->name('dashboard');
Route::get('/login', [App\Http\Controllers\Web\WebController::class, 'login'])->name('login');
Route::post('/login', [App\Http\Controllers\Web\WebController::class, 'post_login'])->name('login.submit');