<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');
Route::get('/search/popular', [SearchController::class, 'popular'])->name('search.popular');
Route::get('/compare', [CompareController::class, 'index'])->name('compare.index');
Route::post('/compare/products/{product}', [CompareController::class, 'toggle'])->name('compare.toggle');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('/products/{product}/reviews', [ProductController::class, 'storeReview'])->name('products.reviews.store');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/products/{product}', [CartController::class, 'store'])->name('cart.store');
    Route::post('/cart/products/{product}/buy-now', [CartController::class, 'buyNow'])->name('cart.buy_now');
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');

    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/products/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/items/{orderItem}/return', [OrderController::class, 'requestReturn'])->name('orders.items.return');

    Route::get('/account', [AccountController::class, 'dashboard'])->name('account.dashboard');
    Route::patch('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::post('/account/addresses', [AccountController::class, 'storeAddress'])->name('account.addresses.store');
    Route::patch('/account/addresses/{address}', [AccountController::class, 'updateAddress'])->name('account.addresses.update');
    Route::delete('/account/addresses/{address}', [AccountController::class, 'destroyAddress'])->name('account.addresses.destroy');
});

Route::prefix('seller')
    ->middleware(['auth', 'role:seller,admin,sub_admin'])
    ->name('seller.')
    ->group(function (): void {
        Route::get('/dashboard', [SellerController::class, 'dashboard'])->name('dashboard');
        Route::get('/products', [SellerController::class, 'productsIndex'])->name('products.index');
        Route::get('/products/{product}/edit', [SellerController::class, 'editProduct'])->name('products.edit');
        Route::post('/products', [SellerController::class, 'storeProduct'])->name('products.store');
        Route::put('/products/{product}', [SellerController::class, 'updateProduct'])->name('products.update');
        Route::delete('/products/{product}', [SellerController::class, 'destroyProduct'])->name('products.destroy');
        Route::get('/orders', [SellerController::class, 'ordersIndex'])->name('orders.index');
        Route::patch('/orders/items/{orderItem}', [SellerController::class, 'updateOrderItem'])->name('orders.update');
        Route::get('/payouts', [SellerController::class, 'payoutsIndex'])->name('payouts.index');
        Route::post('/payouts', [SellerController::class, 'storePayout'])->name('payouts.store');
    });

Route::prefix('admin')
    ->middleware(['auth', 'role:admin,sub_admin'])
    ->name('admin.')
    ->group(function (): void {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        Route::get('/categories', [AdminController::class, 'categoriesIndex'])->name('categories.index');
        Route::post('/categories', [AdminController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [AdminController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [AdminController::class, 'destroyCategory'])->name('categories.destroy');

        Route::get('/brands', [AdminController::class, 'brandsIndex'])->name('brands.index');
        Route::post('/brands', [AdminController::class, 'storeBrand'])->name('brands.store');
        Route::put('/brands/{brand}', [AdminController::class, 'updateBrand'])->name('brands.update');
        Route::delete('/brands/{brand}', [AdminController::class, 'destroyBrand'])->name('brands.destroy');

        Route::get('/products', [AdminController::class, 'productsIndex'])->name('products.index');
        Route::get('/products/{product}/edit', [AdminController::class, 'editProduct'])->name('products.edit');
        Route::post('/products', [AdminController::class, 'storeProduct'])->name('products.store');
        Route::put('/products/{product}', [AdminController::class, 'updateProduct'])->name('products.update');
        Route::delete('/products/{product}', [AdminController::class, 'destroyProduct'])->name('products.destroy');

        Route::get('/sellers', [AdminController::class, 'sellersIndex'])->name('sellers.index');
        Route::post('/sellers/{user}/approve', [AdminController::class, 'approveSeller'])->name('sellers.approve');

        Route::get('/orders', [AdminController::class, 'ordersIndex'])->name('orders.index');
        Route::patch('/orders/{order}', [AdminController::class, 'updateOrder'])->name('orders.update');

        Route::get('/banners', [AdminController::class, 'bannersIndex'])->name('banners.index');
        Route::post('/banners', [AdminController::class, 'storeBanner'])->name('banners.store');
        Route::put('/banners/{banner}', [AdminController::class, 'updateBanner'])->name('banners.update');

        Route::get('/coupons', [AdminController::class, 'couponsIndex'])->name('coupons.index');
        Route::post('/coupons', [AdminController::class, 'storeCoupon'])->name('coupons.store');
        Route::put('/coupons/{coupon}', [AdminController::class, 'updateCoupon'])->name('coupons.update');

        Route::get('/users', [AdminController::class, 'usersIndex'])->name('users.index');
        Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');

        Route::get('/reports', [AdminController::class, 'reportsIndex'])->name('reports.index');
    });
