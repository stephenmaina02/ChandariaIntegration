<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Invoice;
use App\Service\OrderService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\StoreInvoiceRequest;

class OrderControllerRefactored extends Controller
{
    public function nonValidatedData()
    {
        return [
            'lpo_number' => request()->input('lpo_number'),
            'user_code' => request()->input('user_code'),
            'vehicle_code' => request()->input('vehicle_code'),
            'route' => request()->input('route'),
            'region' => request()->input('region'),
            'warehouse_code' => request()->input('warehouse_code'),
            'store_code' => request()->input('store_code'),
            'discount_rate' => request()->has('customer_discount_rate') ? request()->input('customer_discount_rate') : 0
        ];
    }
    // public function orderLines($order)
    // {
    //     $itemdata = [];
    //     foreach ($order['item_list'] as $item) {
    //         $itemdata[] = [
    //             'order_id' => $order['id'],
    //             'transaction_id' => $order['transaction_id'],
    //             'item_code' => $item['item_code'],
    //             'uom_code' => $item['uom_code'],
    //             'item_price' => $item['item_price'],
    //             'item_quantity' => $item['item_quantity'],
    //             'tax_code' => $item['tax_code'],
    //             'item_text' => $item['item_code'],
    //             'warehouse_code' => request()->input('warehouse_code'),
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now()
    //         ];
    //     }
    //     return $itemdata;
    // }
    public function orderLines($order)
    {
        $itemdata = [];
        foreach ($order['item_list'] as $item) {
            $ware_det= DB::selectOne("SELECT TOP 1 [IdWhseStk],[WHWhseID],[WHStockLink],[WHStockGroup],[WHQtyOnHand],m.Code warehouse_code, s.Code
                FROM ". env('SAGE_HOST_DB_NAME')."[WhseStk] JOIN ". env('SAGE_HOST_DB_NAME')."WhseMst m ON m.WhseLink = WhseStk.WHWhseID JOIN ". env('SAGE_HOST_DB_NAME')."StkItem s on s.StockLink=WHStockLink
                WHERE s.ItemGroup = WHStockGroup AND WHQtyOnHand >= 0 AND m.Address3='Y' AND s.Code='".$item['item_code']."' ORDER BY WHQtyOnHand DESC");
            $itemdata[] = [
                'order_id' => $order['id'],
                'transaction_id' => $order['transaction_id'],
                'item_code' => $item['item_code'],
                'uom_code' => $item['uom_code'],
                'item_price' => $item['item_price'],
                'item_quantity' => $item['item_quantity'],
                'tax_code' => $item['tax_code'],
                'item_text' => $item['item_code'],
                'warehouse_code' => !empty(request()->input('warehouse_code')) ?  request()->input('warehouse_code') : (!empty($ware_det) ? $ware_det->warehouse_code :  NULL),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
        }
        return $itemdata;
    }
    public function orderSaveResponse($order, $message, $code)
    {
        return ['transaction_reference' => $order['id'], 'transaction_number' => $order['id'], 'message' => $message, 'code' => $code, 'transaction_id' => substr($order['transaction_id'], 3), 'createdate' => Carbon::now()];
    }
    public function postOrdersToStagingAndSage(StoreOrderRequest $request)
    {
        ini_set('log_errors', 1);
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 600);
        $updated_trans_id_value = 'SATCHA' . $request->validated()['transaction_id'];
        unset($request->validated()['transaction_id']);
        $data = array_merge($request->validated(), ['transaction_id' => $updated_trans_id_value]);
        $orderData = array_merge($data, $this->nonValidatedData());
        $orderData['item_list']=$this->orderLines($orderData);
        //dd($orderData);
        $orderService = new OrderService();
        $orderHasBeenSaved = $orderService->saveData($orderData, $this->orderLines($orderData));
        if ($orderHasBeenSaved) {
            // $sageStatus = $orderService->saveToSage($orderData);
            // if ($sageStatus['status']) {
            //     DB::table('sfaorders')->where('transaction_id', $updated_trans_id_value)->update(['status' => 1, 'updated_at' => Carbon::now()]);
            //     // DB::update("update sfaorders set status = 1, updated_at='".Carbon::now()."' where transaction_id = ?", ["'".$updated_trans_id_value."'"]);
            //     return $this->orderSaveResponse($orderData, $sageStatus['message'], 0);
            // }
            // else {
            //     return $this->orderSaveResponse($orderData, $sageStatus['message'], 0);
            // }
            return $this->orderSaveResponse($orderData, 'SUCCESS', 0);
        }
        else {
            return $this->orderSaveResponse($orderData, 'FAILED', 1);
        }
    }

    public function postInvoiceToStaging(StoreInvoiceRequest $request)
    {
        ini_set('log_errors', 1);
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 600);
        $updated_trans_id_value = 'CHA' . $request->validated()['transaction_id'];
        unset($request->validated()['transaction_id']);
        $data = array_merge($request->validated(), ['transaction_id' => $updated_trans_id_value]);
        $orderData = array_merge($data,
        [
            'lpo_number' => request()->input('lpo_number'),
            'user_code' => request()->input('user_code'),
            'vehicle_code' => request()->input('vehicle_code'),
            'route' => request()->input('route'),
            'region' => request()->input('region'),
            'warehouse_code' => request()->input('warehouse_code'),
            'store_code' => request()->input('store_code'),
            'type' => 'Invoice',

        ]
        );
        //dd($orderData);
        $isInvoiceSaved = Invoice::create($orderData);
        if ($isInvoiceSaved) {
            return $this->orderSaveResponse($orderData,'SUCCESS', 0);
        }
        else {
            return $this->orderSaveResponse($orderData, 'FAILED', 1);
        }
    }
    public static function sageResync()
    {
        $orderToPost = Order::where('status', 0)->orderBy('created_at', 'asc')->get();
        if (count($orderToPost) > 0) {
            $orderService = new OrderService();
            foreach ($orderToPost as $order) {
                $sagePost = $orderService($order);
                if ($sagePost) {
                    $order->status = 1;
                    $order->updated_at = Carbon::now();
                    $order->save();
                }
                else {
                    Log::error('Unable to post ' . $orderToPost->transaction_id . ' to sage. Will retry again in few minutes');
                }
            }
        }
    }
}
