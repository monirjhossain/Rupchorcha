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
use App\Http\Controllers\Admin\ShippingController as AdminShippingController;
use App\Http\Controllers\Admin\SliderController as AdminSliderController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\AttributeController as AdminAttributeController;
use App\Http\Controllers\Admin\BundleController as AdminBundleController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;

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
        Route::delete('/images/{id}', [AdminProductController::class, 'deleteImage'])->name('delete-image');
        
        // Bulk Import Routes
        Route::prefix('import')->name('import.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\BulkProductImportController::class, 'index'])->name('index');
            Route::get('/template', [\App\Http\Controllers\Admin\BulkProductImportController::class, 'downloadTemplate'])->name('template');
            Route::post('/upload', [\App\Http\Controllers\Admin\BulkProductImportController::class, 'upload'])->name('upload');
            Route::get('/{id}/process', [\App\Http\Controllers\Admin\BulkProductImportController::class, 'process'])->name('process');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\BulkProductImportController::class, 'destroy'])->name('destroy');
        });
        
        // Quick Update Routes
        Route::prefix('quick-update')->name('quick-update.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\QuickProductUpdateController::class, 'index'])->name('index');
            Route::get('/template', [\App\Http\Controllers\Admin\QuickProductUpdateController::class, 'downloadTemplate'])->name('template');
            Route::post('/upload', [\App\Http\Controllers\Admin\QuickProductUpdateController::class, 'upload'])->name('upload');
            Route::get('/{id}/process', [\App\Http\Controllers\Admin\QuickProductUpdateController::class, 'process'])->name('process');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\QuickProductUpdateController::class, 'destroy'])->name('destroy');
        });
    });
    
    // Categories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [AdminCategoryController::class, 'index'])->name('index');
        Route::get('/create', [AdminCategoryController::class, 'create'])->name('create');
        Route::post('/', [AdminCategoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AdminCategoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminCategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminCategoryController::class, 'destroy'])->name('destroy');
        Route::post('/mass-destroy', [AdminCategoryController::class, 'massDestroy'])->name('mass-destroy');
        Route::delete('/{id}/image', [AdminCategoryController::class, 'deleteImage'])->name('delete-image');
        Route::post('/reorder', [AdminCategoryController::class, 'reorder'])->name('reorder');
        
        // Bulk Import Routes
        Route::prefix('import')->name('import.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\BulkCategoryImportController::class, 'index'])->name('index');
            Route::get('/template', [\App\Http\Controllers\Admin\BulkCategoryImportController::class, 'downloadTemplate'])->name('download-template');
            Route::post('/upload', [\App\Http\Controllers\Admin\BulkCategoryImportController::class, 'upload'])->name('upload');
            Route::get('/{id}/process', [\App\Http\Controllers\Admin\BulkCategoryImportController::class, 'process'])->name('process');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\BulkCategoryImportController::class, 'destroy'])->name('destroy');
        });
    });
    
    // Orders
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [AdminOrderController::class, 'index'])->name('index');
        Route::get('/{id}', [AdminOrderController::class, 'show'])->name('show');
        Route::post('/{id}/status', [AdminOrderController::class, 'updateStatus'])->name('update-status');
        Route::get('/{id}/invoice/download', [AdminOrderController::class, 'downloadInvoice'])->name('invoice.download');
        Route::get('/{id}/packing-slip/download', [AdminOrderController::class, 'downloadPackingSlip'])->name('packing-slip.download');
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
    
    // Sliders
    Route::prefix('sliders')->name('sliders.')->group(function () {
        Route::get('/', [AdminSliderController::class, 'index'])->name('index');
        Route::get('/create', [AdminSliderController::class, 'create'])->name('create');
        Route::post('/', [AdminSliderController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AdminSliderController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminSliderController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminSliderController::class, 'destroy'])->name('destroy');
        Route::post('/mass-destroy', [AdminSliderController::class, 'massDestroy'])->name('mass-destroy');
        Route::delete('/{id}/image', [AdminSliderController::class, 'deleteImage'])->name('delete-image');
        Route::post('/reorder', [AdminSliderController::class, 'reorder'])->name('reorder');
    });
    
    // Brands
    Route::prefix('brands')->name('brands.')->group(function () {
        Route::get('/', [AdminBrandController::class, 'index'])->name('index');
        Route::get('/create', [AdminBrandController::class, 'create'])->name('create');
        Route::post('/', [AdminBrandController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AdminBrandController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminBrandController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminBrandController::class, 'destroy'])->name('destroy');
        Route::post('/mass-destroy', [AdminBrandController::class, 'massDestroy'])->name('mass-destroy');
        Route::delete('/{id}/logo', [AdminBrandController::class, 'deleteLogo'])->name('delete-logo');
    });
    
    // Tags
    Route::prefix('tags')->name('tags.')->group(function () {
        Route::get('/', [AdminTagController::class, 'index'])->name('index');
        Route::get('/create', [AdminTagController::class, 'create'])->name('create');
        Route::post('/', [AdminTagController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AdminTagController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminTagController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminTagController::class, 'destroy'])->name('destroy');
        Route::post('/mass-destroy', [AdminTagController::class, 'massDestroy'])->name('mass-destroy');
    });
    
    // Attributes
    Route::prefix('attributes')->name('attributes.')->group(function () {
        Route::get('/', [AdminAttributeController::class, 'index'])->name('index');
        Route::get('/create', [AdminAttributeController::class, 'create'])->name('create');
        Route::post('/', [AdminAttributeController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AdminAttributeController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminAttributeController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminAttributeController::class, 'destroy'])->name('destroy');
        Route::post('/mass-destroy', [AdminAttributeController::class, 'massDestroy'])->name('mass-destroy');
    });
    
    // Frequently Bought Together (Bundles)
    Route::prefix('bundles')->name('bundles.')->group(function () {
        Route::get('/', [AdminBundleController::class, 'index'])->name('index');
        Route::get('/create', [AdminBundleController::class, 'create'])->name('create');
        Route::post('/', [AdminBundleController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AdminBundleController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminBundleController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminBundleController::class, 'destroy'])->name('destroy');
        Route::post('/mass-destroy', [AdminBundleController::class, 'massDestroy'])->name('mass-destroy');
        Route::get('/search-products', [AdminBundleController::class, 'searchProducts'])->name('search-products');
    });
    
    // Product Reviews
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/', [AdminReviewController::class, 'index'])->name('index');
        Route::get('/{id}', [AdminReviewController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [AdminReviewController::class, 'approve'])->name('approve');
        Route::post('/{id}/decline', [AdminReviewController::class, 'decline'])->name('decline');
        Route::delete('/{id}', [AdminReviewController::class, 'destroy'])->name('destroy');
        Route::post('/mass-destroy', [AdminReviewController::class, 'massDestroy'])->name('mass-destroy');
        Route::post('/mass-approve', [AdminReviewController::class, 'massApprove'])->name('mass-approve');
        Route::post('/mass-decline', [AdminReviewController::class, 'massDecline'])->name('mass-decline');
    });
    
    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/general', [AdminSettingsController::class, 'general'])->name('general');
        Route::post('/update', [AdminSettingsController::class, 'update'])->name('update');
    });

    // Shipping Settings
    Route::prefix('shipping')->name('shipping.')->group(function () {
        Route::get('/', [AdminShippingController::class, 'index'])->name('index');
        Route::put('/methods/{id}', [AdminShippingController::class, 'update'])->name('update');
        Route::post('/general', [AdminShippingController::class, 'updateGeneral'])->name('updateGeneral');
        Route::post('/methods/{id}/toggle', [AdminShippingController::class, 'toggleStatus'])->name('toggleStatus');
    });
});

/*
|--------------------------------------------------------------------------
| Web Routes - Custom Frontend
|--------------------------------------------------------------------------
*/

// Redirect root to admin login
Route::get('/', function() {
    return redirect()->route('admin.session.create');
});

// Redirect any installer route to admin login
Route::any('/admin/installer{any?}', function() {
    return redirect()->route('admin.session.create');
})->where('any', '.*');

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
| Custom Admin Authentication (using Bagisto's auth only)
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => ['web'], 'prefix' => 'admin'], function () {
    // Admin Login
    Route::get('login', function() {
        if (auth()->guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.auth.login');
    })->name('admin.session.create');

    Route::post('login', function(\Illuminate\Http\Request $request) {
        $credentials = $request->only('email', 'password');
        
        if (auth()->guard('admin')->attempt($credentials)) {
            return redirect()->route('admin.dashboard');
        }
        
        return back()->withErrors(['email' => 'Invalid credentials']);
    })->name('admin.session.store');

    Route::post('logout', function() {
        auth()->guard('admin')->logout();
        return redirect()->route('admin.session.create');
    })->name('admin.session.destroy');
});
