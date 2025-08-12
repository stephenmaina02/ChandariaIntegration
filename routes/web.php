<?php

use App\Service\SDK;
use App\Models\Product;
use App\Classes\AccessToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\OrderTrackingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');
Route::middleware(['auth:sanctum', 'verified'])->get('/orders', function () {
    return view('orders');
})->name('orders');
Route::middleware(['auth:sanctum', 'verified'])->get('/customers', function () {
    return view('customers');
})->name('customers');
Route::middleware(['auth:sanctum', 'verified'])->get('/products', function () {
    return view('products');
})->name('products');
Route::middleware(['auth:sanctum', 'verified'])->get('/users', function () {
    return view('users.index');
})->name('users.index');
 Route::get('/deliveries', function () {return view('delivery');
    })->name('delivery');
 Route::get('/track-orders', function () { return view('track-orders');
    })->name('track-orders');



Route::get('customer',  [CustomerController::class, 'pushToSfa']);
Route::get('product',  [ProductController::class, 'pushProductToSFA']);
Route::get('getToken', [CustomerController::class, 'getTokenFromSFA']);
Route::get('/sage/{code}', [OrderController::class, 'postToSage']);

Route::get('trackorder',  [OrderTrackingController::class,'pushOrderStatus']);
Route::get('trackdelivery',  [DeliveryController::class,'pushDeliveryToSfa']);
Route::get('syncdelivery',[DeliveryController::class, 'selectDeliveryFromSage']);
Route::get('syncproducts', [ProductController::class, 'getProducts']);
Route::get('synccustomers', [CustomerController::class, 'getCustomersFromSage']);
Route::get('syncpromotion', [PromotionController::class, 'getPromotionToStaging']);
Route::get('/promotion', [PromotionController::class, 'postPromotionToSFA']);

// Route::get('/test',[SDK::class, 'sdkConnect']);

// Route::get('/test', function(){
//     $product=Product::first();
//     dd(json_decode($product->uom_list, true));
// });
