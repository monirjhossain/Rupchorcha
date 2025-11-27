<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Http\Controllers\Frontend\CustomerController;

// Admin Controllers
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;

/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Products
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [AdminProductController::class, 'index'])->name('index');
        Route::get('/create', [AdminProductController::class, 'create'])->name('create');
        Route::post('/', [AdminProductController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AdminProductController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminProductController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminProductController::class, 'destroy'])->name('destroy');
        Route::post('/mass-destroy', [AdminProductController::class, 'massDestroy'])->name('mass-destroy');
    });
    
    // Categories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [AdminCategoryController::class, 'index'])->name('index');
        Route::get('/create', [AdminCategoryController::class, 'create'])->name('create');
        Route::post('/', [AdminCategoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AdminCategoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminCategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminCategoryController::class, 'destroy'])->name('destroy');
    });
    
    // Orders
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [AdminOrderController::class, 'index'])->name('index');
        Route::get('/{id}', [AdminOrderController::class, 'show'])->name('show');
        Route::post('/{id}/status', [AdminOrderController::class, 'updateStatus'])->name('update-status');
        Route::post('/{id}/invoice', [AdminOrderController::class, 'createInvoice'])->name('create-invoice');
        Route::post('/{id}/shipment', [AdminOrderController::class, 'createShipment'])->name('create-shipment');
        Route::post('/{id}/cancel', [AdminOrderController::class, 'cancel'])->name('cancel');
    });
    
    // Customers
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [AdminCustomerController::class, 'index'])->name('index');
        Route::get('/create', [AdminCustomerController::class, 'create'])->name('create');
        Route::post('/', [AdminCustomerController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AdminCustomerController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminCustomerController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminCustomerController::class, 'destroy'])->name('destroy');
        Route::post('/mass-destroy', [AdminCustomerController::class, 'massDestroy'])->name('mass-destroy');
    });
    
    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/general', [AdminSettingsController::class, 'general'])->name('general');
        Route::post('/update', [AdminSettingsController::class, 'update'])->name('update');
    });
});

/*
|--------------------------------------------------------------------------
| Web Routes - Custom Frontend
|--------------------------------------------------------------------------
*/

// Home
Route::get('/', [HomeController::class, 'index'])->name('home');

// Products
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('products.index');
    Route::get('/search', [ProductController::class, 'search'])->name('products.search');
    Route::get('/{slug}', [ProductController::class, 'show'])->name('products.show');
    Route::post('/{id}/review', [ProductController::class, 'storeReview'])->name('products.review');
});

// Categories
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/{slug}', [CategoryController::class, 'show'])->name('categories.show');
});

// Cart
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('cart.index');
    Route::post('/store', [CartController::class, 'store'])->name('cart.store');
    Route::put('/update', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/{id}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::delete('/', [CartController::class, 'empty'])->name('cart.empty');
});

// Checkout
Route::prefix('checkout')->middleware('auth:customer')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/save-address', [CheckoutController::class, 'saveAddress'])->name('checkout.save-address');
    Route::post('/save-shipping', [CheckoutController::class, 'saveShipping'])->name('checkout.save-shipping');
    Route::post('/save-payment', [CheckoutController::class, 'savePayment'])->name('checkout.save-payment');
    Route::post('/place-order', [CheckoutController::class, 'placeOrder'])->name('checkout.place-order');
    Route::get('/success/{orderId}', [CheckoutController::class, 'success'])->name('checkout.success');
});

// Customer Dashboard
Route::prefix('customer')->middleware('auth:customer')->group(function () {
    Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('customer.dashboard');
    Route::get('/profile', [CustomerController::class, 'profile'])->name('customer.profile');
    Route::put('/profile', [CustomerController::class, 'updateProfile'])->name('customer.update-profile');
    Route::get('/addresses', [CustomerController::class, 'addresses'])->name('customer.addresses');
    Route::post('/addresses', [CustomerController::class, 'storeAddress'])->name('customer.store-address');
    Route::get('/orders', [CustomerController::class, 'orders'])->name('customer.orders');
    Route::get('/orders/{id}', [CustomerController::class, 'orderDetails'])->name('customer.order-details');
});

/*
|--------------------------------------------------------------------------
| Bagisto Default Routes (Admin & Authentication)
|--------------------------------------------------------------------------
*/

// Load Bagisto routes for admin panel and authentication
Route::group(['middleware' => ['web']], function () {
    // Bagisto route loader
    $bagistoRoutes = base_path('packages/Webkul/Shop/src/Http/routes.php');
    if (file_exists($bagistoRoutes)) {
        require $bagistoRoutes;
    }
});
