<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\Admin\TransactionsController;

Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Authentication routes
Route::get('/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
Route::get('/register', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'store']);

// Products
Route::get('/products', [ProductsController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductsController::class, 'show'])->name('products.show');

// Cart routes
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('cart.index');
    Route::get('/data', [CartController::class, 'getCartData'])->name('cart.data');
    Route::post('/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/remove/{product}', [CartController::class, 'remove'])->name('cart.remove');
    Route::patch('/update/{product}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/clear', [CartController::class, 'clear'])->name('cart.clear');
});

// Checkout routes
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::post('/checkout/wallet', [WalletController::class, 'checkout'])->name('checkout.wallet');

// Paymob payment routes
Route::post('/payment/paymob/start', [\App\Http\Controllers\Payment\PaymobController::class, 'startCheckout'])->name('payment.paymob.start');
Route::get('/payment/paymob/iframe/{order}', [\App\Http\Controllers\Payment\PaymobController::class, 'showIframe'])->name('payment.paymob.iframe');

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

// User orders (requires auth)
Route::middleware(['auth'])->prefix('orders')->group(function () {
    Route::get('/', [\App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
    Route::get('/{order}', [\App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
    Route::get('/{order}/invoice', [\App\Http\Controllers\OrderController::class, 'downloadInvoice'])->name('orders.invoice');
});

// Admin routes (requires admin)
Route::middleware(['auth', \App\Http\Middleware\EnsureUserIsAdmin::class])->prefix('admin')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');
    
    // Products
    Route::get('/products', [\App\Http\Controllers\Admin\ProductController::class, 'index'])->name('admin.products.index');
    Route::post('/products', [\App\Http\Controllers\Admin\ProductController::class, 'store'])->name('admin.products.store');
    Route::put('/products/{product}', [\App\Http\Controllers\Admin\ProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/products/{product}', [\App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('admin.products.destroy');
    
    // Orders
    Route::get('/orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('admin.orders.show');
    Route::get('/orders/{order}/invoice', [\App\Http\Controllers\Admin\OrderController::class, 'downloadInvoice'])->name('admin.orders.invoice');
    Route::put('/orders/{order}/status', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('admin.orders.update-status');
    Route::get('/orders/export/csv', [\App\Http\Controllers\Admin\OrderController::class, 'export'])->name('admin.orders.export');
    
    // Transactions
    Route::get('/transactions', [TransactionsController::class, 'index'])->name('admin.transactions.index');
    Route::post('/transactions/{transaction}/refund', [TransactionsController::class, 'refund'])->name('admin.transactions.refund');
    Route::post('/transactions/{transaction}/refund-to-wallet', [TransactionsController::class, 'refundToWallet'])->name('admin.transactions.refund-to-wallet');
    Route::post('/transactions/{transaction}/refund/paymob', [\App\Http\Controllers\Payment\PaymobController::class, 'refund'])->name('admin.transactions.refund.paymob');
});
