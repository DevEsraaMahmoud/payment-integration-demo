<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\Admin\TransactionsController;

Route::get('/', function () {
    return redirect()->route('products.index');
});

// Products
Route::get('/products', [ProductsController::class, 'index'])->name('products.index');

// Cart routes
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('cart.index');
    Route::post('/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/remove/{product}', [CartController::class, 'remove'])->name('cart.remove');
    Route::patch('/update/{product}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/clear', [CartController::class, 'clear'])->name('cart.clear');
});

// Checkout routes
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::post('/checkout/wallet', [WalletController::class, 'checkout'])->name('checkout.wallet');

// Success page
Route::get('/success', function () {
    return \Inertia\Inertia::render('Success', [
        'order_id' => request('order'),
    ]);
})->name('checkout.success');

// Wallet routes (accessible to all users)
Route::prefix('wallet')->group(function () {
    Route::get('/', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/fund', [WalletController::class, 'fund'])->name('wallet.fund');
});

// Admin routes
Route::prefix('admin')->group(function () {
    Route::get('/transactions', [TransactionsController::class, 'index'])->name('admin.transactions.index');
    Route::post('/transactions/{transaction}/refund', [TransactionsController::class, 'refund'])->name('admin.transactions.refund');
});
