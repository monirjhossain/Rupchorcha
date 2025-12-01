<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
|--------------------------------------------------------------------------
| Custom API Routes for React Frontend
|--------------------------------------------------------------------------
*/

// Sliders API
Route::get('/sliders', 'App\Http\Controllers\API\SliderController@index');

// Products API
Route::prefix('products')->group(function () {
    Route::get('/search', 'App\Http\Controllers\API\ProductController@search');
    Route::get('/', 'App\Http\Controllers\API\ProductController@index');
    Route::get('/{id}', 'App\Http\Controllers\API\ProductController@show');
});

// Categories API
Route::prefix('categories')->group(function () {
    Route::get('/', 'App\Http\Controllers\API\CategoryController@index');
    Route::get('/{id}', 'App\Http\Controllers\API\CategoryController@show');
    Route::get('/{id}/products', 'App\Http\Controllers\API\CategoryController@products');
});

// Brands API
Route::prefix('brands')->group(function () {
    Route::get('/', 'App\Http\Controllers\API\BrandController@index');
    Route::get('/{id}', 'App\Http\Controllers\API\BrandController@show');
    Route::get('/{id}/products', 'App\Http\Controllers\API\BrandController@products');
});

// Tags API
Route::prefix('tags')->group(function () {
    Route::get('/', 'App\Http\Controllers\API\TagController@index');
    Route::get('/{id}', 'App\Http\Controllers\API\TagController@show');
    Route::get('/{id}/products', 'App\Http\Controllers\API\TagController@products');
});

// Cart API
Route::prefix('cart')->group(function () {
    Route::get('/', 'App\Http\Controllers\API\CartController@index');
    Route::post('/add', 'App\Http\Controllers\API\CartController@store');
    Route::put('/update', 'App\Http\Controllers\API\CartController@update');
    Route::delete('/{id}', 'App\Http\Controllers\API\CartController@destroy');
});

// Shipping API
Route::prefix('shipping')->group(function () {
    Route::get('/methods', 'App\Http\Controllers\API\ShippingController@getMethods');
    Route::post('/calculate', 'App\Http\Controllers\API\ShippingController@calculateShipping');
});

// Shipping Settings API (Admin)
Route::prefix('admin/shipping-settings')->group(function () {
    Route::get('/', 'App\Http\Controllers\API\ShippingSettingsController@index');
    Route::get('/active', 'App\Http\Controllers\API\ShippingSettingsController@getActiveMethods');
    Route::post('/methods', 'App\Http\Controllers\API\ShippingSettingsController@createMethod');
    Route::put('/methods/{id}', 'App\Http\Controllers\API\ShippingSettingsController@updateMethod');
    Route::delete('/methods/{id}', 'App\Http\Controllers\API\ShippingSettingsController@deleteMethod');
    Route::patch('/methods/{id}/toggle', 'App\Http\Controllers\API\ShippingSettingsController@toggleActive');
    Route::put('/general', 'App\Http\Controllers\API\ShippingSettingsController@updateGeneralSettings');
});

// Orders API
Route::prefix('orders')->group(function () {
    Route::get('/', 'App\Http\Controllers\API\OrderController@index');
    Route::post('/', 'App\Http\Controllers\API\OrderController@store');
    Route::get('/{id}', 'App\Http\Controllers\API\OrderController@show');
});