<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use App\Models\Delivery;
use App\Classes\AccessToken;
use App\Models\OrderTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryController extends Controller
{
    // public static function pushDeliveryToSfa()
    // {
    //     $client = new Client();
    //     $acc = new AccessToken();
    //     $accessToken = $acc->getTokenFromSFA();
    //     $delivery = Delivery::where('status', 0)->where('item_list', '<>', '')->orderBy('created_at', 'desc')->first();
    //     if (!is_null($delivery)) {
    //         $headers = [
    //             'Authorization' => 'Bearer ' . $accessToken,
    //             'Accept' => 'application/json',
    //             'Content-Type' => 'application/json'
    //         ];

    //         $response = $client->request('POST', env('SFA_BASE_URL') . '/api/v1/sap/sap-delivery', [
    //             'headers' => $headers,
    //             'json' => [
    //                 'delivery_id' => $delivery->delivery_id,
    //                 //'transaction_id' => $delivery->transaction_id,
    //                 'transaction_id' => substr($delivery->transaction_id, 3),
    //                 'invoice_number' => $delivery->invoice_number,
    //                 'delivery_date' => $delivery->delivery_date,
    //                 'vehicle_details' => $delivery->vehicle_details,
    //                 'driver_details' => $delivery->driver_details,
    //                 'delivery_status' => $delivery->delivery_status,
    //                 'notes' => $delivery->notes,
    //                 'item_list' => json_decode($delivery->item_list, true),
    //                 'customer_code' => $delivery->customer_code
    //             ]
    //         ]);


    //         if ($response->getStatusCode() == 200) {

    //             $updatedelivery = Delivery::find($delivery->id);
    //             $updatedelivery->status = 1;
    //             $updatedelivery->updated_at = Carbon::now();
    //             $updatedelivery->save();

    //             return json_encode(['Message' => 'Order Posted Successfully']);
    //         }
    //         else
    //             Log::error('Unable to Post to Solutech');
    //         //log error in file or db table
    //     }
    //     return json_encode(['Message' => 'No Delivery items to send to SFA']);
    // }
    // public function getDelivery(Request $request)
    // {
    //     $region = $request->input('region');
    //     $response = [];
    //     $transaction_id = $request->input('transaction_id');

    //     $delivery = Delivery::where('transaction_id', $transaction_id)->where('delivery_status', 'Delivered')->first();
    //     if (!is_null($delivery)) {
    //         $response = [
    //             'delivery_id' => $delivery->delivery_id,
    //             'transaction_id' => $delivery->transaction_id,
    //             // 'transaction_id' => substr($delivery->transaction_id, 2),
    //             'invoice_number' => $delivery->invoice_number,
    //             'delivery_date' => $delivery->delivery_date,
    //             'vehicle_details' => $delivery->vehicle_details,
    //             'driver_details' => $delivery->driver_details,
    //             'delivery_status' => $delivery->delivery_status,
    //             'notes' => $delivery->notes,
    //             'item_list' => json_decode($delivery->item_list, true),
    //             'customer_code' => $delivery->customer_code
    //         ];
    //     } else {
    //         $response = [
    //             'transaction_id' => $transaction_id,
    //             'region' => $region,
    //             'status' => 'Ok',
    //             'message' => 'No record found for transaction_id and region passed'
    //         ];
    //     }
    //     return json_encode($response);
    // }
    public static function selectDeliveryFromSage()
    {
        $transactions = DB::select('SELECT i.OrderNum,i. ExtOrderNum,i.Description,i.InvNum_dModifiedDate,i.AutoIndex,i.InvDate,i.InvNumber,i.DocState, c.Account FROM ' . env("SAGE_HOST_DB_NAME") . 'InvNum i inner join ' . env("SAGE_HOST_DB_NAME") . 'Client c on i.AccountID=c.DCLink
        where OrderNum in (SELECT transaction_id FROM SFAOrders) and ExtOrderNum in (SELECT transaction_id FROM SFAOrders)');
        if (!is_null($transactions)) {
            foreach ($transactions as $trans) {
                // $trackedDelivery = Delivery::where('transaction_id', $trans->ExtOrderNum)->first();

                // if (!is_null($trackedDelivery)) {
                //     if ($trackedDelivery->sage_modify_time != $trans->InvNum_dModifiedDate && $trans->DocState == 4) {
                //         $query = DB::update("update SFADelivery set invoice_number=$trans->InvNumber,delivery_status='$status',item_list='$encoded_items',delivery_date='$trans->InvDate',sage_modify_time='$trans->InvNum_dModifiedDate', status=0,updated_at='$date'  where delivery_id = ?", [$trans->AutoIndex]);
                //         if ($query > 0)
                //             Log::info("Delivery Records Updated");
                //     }
                // } else {
                //     $query = DB::insert(
                //         'insert into SFADelivery (transaction_id,delivery_id,invoice_number,delivery_status,item_list,delivery_date,sage_modify_time, created_at,updated_at, vehicle_details, customer_code) values (?,?,?,?,?,?,?,?,?,?,?)',
                //         [$trans->ExtOrderNum, $trans->AutoIndex, $trans->InvNumber, $status, $encoded_items, $trans->InvDate, $trans->InvNum_dModifiedDate, $date, $date, $trans->Account, $trans->Account]
                //     );
                //     if ($query)
                //         Log::info("Delivery Records inserted");
                // }
                // Order tracking controller
                $status = '';
                $items = [];
                if ($trans->DocState == 4)
                    $status = "Delivered";
                else
                    $status = "Not Delivered";
                $date = Carbon::now();
                $orderTrackerStatus = OrderTracking::where('transaction_id', $trans->ExtOrderNum)->first();
                $items = DB::select("SELECT s.Code item_code,s.Pack uom_code,cast(round(l.fQuantity,2) as numeric(36,2)) item_quantity,cast(round(l.fUnitPriceExcl,2) as numeric(36,2)) item_price,'" . $status . "' as item_status from " . env('SAGE_HOST_DB_NAME') . "_btblInvoiceLines l
                inner join " . env('SAGE_HOST_DB_NAME') . "StkItem s on s.StockLink=l.iStockCodeID
                where iInvoiceID= $trans->AutoIndex");
                $encoded_items = json_encode($items);
                $trackerStatus = '';
                if ($trans->DocState == 0 || $trans->DocState == 1)
                    $trackerStatus = "Order";
                elseif ($trans->DocState == 2)
                    $trackerStatus = "Quote";
                elseif ($trans->DocState == 3)
                    $trackerStatus == "Partially Invoiced";
                elseif ($trans->DocState == 4)
                    $trackerStatus = "Invoiced";
                else
                    $trackerStatus = "Cancelled Order";

                if (!is_null($orderTrackerStatus)) {
                    if ($orderTrackerStatus->sage_modify_time != $trans->InvNum_dModifiedDate) {
                        $query = DB::update("update PevOrderTracking set status='$trackerStatus',item_list='$encoded_items',date='$trans->InvDate',sage_modify_time='$trans->InvNum_dModifiedDate', updated_at='$date', updateFlag=0  where doc_num = ?", [$trans->AutoIndex]);
                        if ($query > 0)
                            Log::info("Order tracker Records Updated");
                    }
                } else {
                    $query = DB::insert(
                        'insert into PevOrderTracking (transaction_id,doc_num,status,item_list,sage_modify_time, created_at,updated_at, customer_code,date) values (?,?,?,?,?,?,?,?,?)',
                        [$trans->ExtOrderNum, $trans->AutoIndex, $trackerStatus, $encoded_items, $trans->InvNum_dModifiedDate, $date, $date,  $trans->Account, $trans->InvDate]
                    );
                    if ($query)
                        Log::info("Order tracker records inserted");
                }
            }
        } else
            return 'No transactions existing';
    }
}
