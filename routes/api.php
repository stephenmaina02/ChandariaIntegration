<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\MasterFileController;
use App\Http\Controllers\BranchTrackingController;
use App\Http\Controllers\OrderControllerRefactored;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::get('pev/stock-balance', [MasterFileController::class, 'getPriceList']);
Route::group(['middleware' => 'client'],  function () {
    // Route::get('pev/price-list', [MasterFileController::class, 'getPriceList']);
    Route::get('pev/stock-balance', [MasterFileController::class, 'getStockBalance']);
    Route::post('pev/credit-limit', [MasterFileController::class, 'getCustomerCreditLimit']);
    // Route::post('pev/order', [OrderController::class, 'postOrders']);
	Route::post('pev/order', [OrderControllerRefactored::class, 'postOrdersToStagingAndSage']);
    Route::post('pev/sale', [OrderControllerRefactored::class, 'postInvoiceToStaging']);
    Route::get('pev/customers', [MasterFileController::class, 'getCustomers']);
	Route::post('pev/products', [MasterFileController::class, 'getFilteredProduct']);
    Route::post('pev/payments', [PaymentController::class, 'saveToStaging']);

});
Route::post('login', [AuthController::class, 'login']);
Route::get('pev/order/{transaction_id}', [OrderController::class, 'postBranchOrders'])->name('branchOrders');
Route::get('pev/delivery', [MasterFileController::class, 'getDelivery']);
Route::get('pev/track-order', [MasterFileController::class, 'trackSapOrder']);
Route::post('pev/order-tracking/{branch}', [BranchTrackingController::class, 'trackBranchOrder']);
Route::post('pev/delivery-tracking/{branch}', [BranchTrackingController::class, 'trackBranchDelivery']);
Route::get('pev/price-list', [MasterFileController::class, 'getPriceList']);
Route::get('pricelists', [MasterFileController::class, 'getPriceListFromSage']);


// Route::get('/try-product', [ProductController::pushProductToSFA()]);
// Route::post('/test', function(){
//     dd(request()->all());
// });
