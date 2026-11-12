<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ErpGateController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------- catalog
Route::get('/', HomeController::class)->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/category/{category:slug}', [ShopController::class, 'category'])->name('category.show');
Route::get('/brand/{brand:slug}', [ShopController::class, 'brand'])->name('brand.show');
Route::get('/brands', [ShopController::class, 'brands'])->name('brands');
Route::get('/search', [ShopController::class, 'search'])->name('search');
Route::get('/search/suggest', [ShopController::class, 'suggest'])->name('search.suggest')->middleware('throttle:60,1');
Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/product/{product}/quick-view', [ProductController::class, 'quickView'])->name('product.quick');
Route::post('/product/{product}/review', [ProductController::class, 'review'])->name('product.review')->middleware('throttle:5,1');
Route::get('/recent-sales', [ProductController::class, 'recentSales'])->name('recent-sales');

// ---------------------------------------------------------------- cart & checkout
Route::controller(CartController::class)->group(function () {
    Route::get('/cart', 'index')->name('cart');
    Route::get('/cart/data', 'data')->name('cart.data');
    Route::post('/cart/add', 'add')->name('cart.add')->middleware('throttle:60,1');
    Route::post('/cart/update', 'update')->name('cart.update');
    Route::post('/cart/remove', 'remove')->name('cart.remove');
    Route::post('/cart/coupon', 'applyCoupon')->name('cart.coupon')->middleware('throttle:15,1');
    Route::delete('/cart/coupon', 'removeCoupon')->name('cart.coupon.remove');
});

Route::controller(CheckoutController::class)->group(function () {
    Route::get('/checkout', 'index')->name('checkout');
    Route::post('/checkout', 'store')->name('checkout.store')->middleware('throttle:10,1');
    Route::post('/checkout/summary', 'summary')->name('checkout.summary');
    Route::post('/checkout/capture', 'capture')->name('checkout.capture')->middleware('throttle:30,1');
    Route::get('/order/{order:order_number}/thank-you', 'success')->name('checkout.success');
});

Route::get('/track-order', TrackController::class)->name('track');

Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

// ---------------------------------------------------------------- customers
Route::middleware('guest')->controller(AuthController::class)->group(function () {
    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'login')->middleware('throttle:6,1');
    Route::get('/register', 'showRegister')->name('register');
    Route::post('/register', 'register')->middleware('throttle:6,1');
    Route::get('/forgot-password', 'showForgot')->name('password.request');
    Route::post('/forgot-password', 'sendResetLink')->name('password.email')->middleware('throttle:3,1');
    Route::get('/reset-password/{token}', 'showReset')->name('password.reset');
    Route::post('/reset-password', 'reset')->name('password.update');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->prefix('account')->name('account.')->controller(AccountController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/orders', 'orders')->name('orders');
    Route::get('/orders/{order:order_number}', 'order')->name('order');
    Route::get('/profile', 'profile')->name('profile');
    Route::put('/profile', 'updateProfile')->name('profile.update');
    Route::put('/password', 'updatePassword')->name('password.update');
});

// ---------------------------------------------------------------- content
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:5,1');
Route::post('/newsletter', [ContactController::class, 'subscribe'])->name('newsletter')->middleware('throttle:5,1');

// ---------------------------------------------------------------- SEO endpoints
Route::get('/sitemap.xml', [SeoController::class, 'sitemapIndex'])->name('sitemap');
Route::get('/sitemap-{type}.xml', [SeoController::class, 'sitemap'])->whereIn('type', ['pages', 'categories', 'brands', 'products', 'posts'])->name('sitemap.type');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/feed/products.xml', [SeoController::class, 'productFeed'])->name('feed.products');

// ---------------------------------------------------------------- admin helpers
Route::get('/admin-tools/invoice/{order}', InvoiceController::class)->middleware('auth')->name('admin.invoice');

// ---------------------------------------------------------------- CMS pages (keep last)
// Optional private-subdomain gate – 404 unless ERP_GATE_SECRET is set (see docs/subdomain-gate.md). Must stay above the CMS catch-all.
Route::get('/dhaka', ErpGateController::class)->name('erp.gate');

Route::get('/{page:slug}', [PageController::class, 'show'])->where('page', '[a-z0-9\-]+')->name('page.show');
