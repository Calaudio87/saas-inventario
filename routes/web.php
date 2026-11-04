<?php

use App\Http\Controllers\InventoryDashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\TenantUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [InventoryDashboardController::class, 'index'])->name('dashboard');

    // Movimientos de Stock (Admin + Staff)
    Route::post('/stock-movements', [StockMovementController::class, 'store'])->name('stock-movements.store');

    // Gestión de Catálogo (Solo Admin validado por Gate::authorize('manage-catalog'))
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Gestión de Usuarios del Tenant (Solo Admin validado por Gate::authorize('manage-users'))
    Route::post('/tenant-users', [TenantUserController::class, 'store'])->name('tenant-users.store');

    // Perfil de Breeze
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
