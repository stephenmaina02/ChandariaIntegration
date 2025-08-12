<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use App\Classes\AccessToken;
use App\Models\OrderTracking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Order;


class OrderTrackingController extends Controller
{
    public function getOrderTrackerFromSage()
    {
        $transactions = DB::select('SELECT i.OrderNum,i. ExtOrderNum,i.Description,i.InvNum_dModifiedDate,i.AutoIndex,i.InvDate,i.InvNumber,i.DocState, c.Account FROM ' . env("SAGE_HOST_DB_NAME") . 'InvNum i inner join ' . env("SAGE_HOST_DB_NAME") . 'Client c on i.AccountID=c.DCLink
        where ExtOrderNum in (SELECT transaction_id FROM SFAOrders) AND DocState>1');
        if (!is_null($transactions)) {
            foreach ($transactions as $trans) {
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
                    if (strtotime($orderTrackerStatus->sage_modify_time) != strtotime($trans->InvNum_dModifiedDate) && $orderTrackerStatus->status != 'Invoiced') {
                        $query = OrderTracking::where('transaction_id', $trans->ExtOrderNum)->first()->update([
                            'status' => $trackerStatus, 'item_list' => $encoded_items,
                            'date' => $trans->InvDate, 'sage_modify_time' => $trans->InvNum_dModifiedDate, 'updated_at' => $date, 'updateFlag' => 0, 'doc_num' => $trans->InvNumber
                        ]);
                        Log::info("Order tracker Records Updated $trans->ExtOrderNum");
                    // $query = DB::update("update PevOrderTracking set status='$trackerStatus',item_list='$encoded_items',date='$trans->InvDate',sage_modify_time='$trans->InvNum_dModifiedDate', updated_at='$date', updateFlag=0, doc_num='$trans->InvNumber' where transaction_id = ?", ["'".$trans->ExtOrderNum."'"]);

                    }
                }
                else {
                    $query = DB::insert(
                        'insert into PevOrderTracking (transaction_id,doc_num,status,item_list,sage_modify_time, created_at,updated_at, customer_code,date) values (?,?,?,?,?,?,?,?,?)',
                    [$trans->ExtOrderNum, $trans->InvNumber, $trackerStatus, $encoded_items, $trans->InvNum_dModifiedDate, $date, $date, $trans->Account, $trans->InvDate]
                    );
                    if ($query)
                        Log::info("Order tracker records inserted");
                }
            }
        }
        else
            Log::info('All Orders/Invoices already synced from Sage');
    }
    public function pushOrderStatus()
    {
        $this->getOrderTrackerFromSage();
        $client = new Client(['verify' => false]);
        $acc = new AccessToken();
        $accessToken = $acc->getTokenFromSFA();
        $orderStatus = DB::selectOne("select * from [PevOrderTracking] where status<>'Order' AND [insertFlag] = 0 AND [updateFlag] = 0 OR [insertFlag] = 1 AND [updateFlag] = 0  order by created_at desc");
        if (!is_null($orderStatus)) {
            $headers = [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];
            //Log::info($orderStatus->transaction_id.':'. substr($orderStatus->transaction_id, 0, 3));
            //dd($headers);
            $response = $client->request('POST', env('SFA_BASE_URL') . '/api/v1/sap/sap-track-order', [
                'headers' => $headers,
                'json' => [
                    'transaction_id' => substr($orderStatus->transaction_id, 0, 3)=='CHA' ?  substr($orderStatus->transaction_id, 3) : substr($orderStatus->transaction_id, 6),
                    'doc_num' => $orderStatus->doc_num,
                    'item_list' => json_decode($orderStatus->item_list, true),
                    'date' => $orderStatus->date,
                    'status' => $orderStatus->status,
                    'customer_code' => $orderStatus->customer_code
                ]
            ]);

            if ($response->getStatusCode() == 200) {

                $updateOrderTracking = OrderTracking::find($orderStatus->id);
                $updateOrderTracking->insertFlag = 1;
                $updateOrderTracking->updateFlag = 1;
                $updateOrderTracking->updated_at = Carbon::now();
                $updateOrderTracking->save();
            }
            //log error in file or db table
            Log::info('Order Posted to SFA');
        }
        else
            Log::info('No Order Status to send to SFA');
    }

    public function pushResponseStatus()
    {
        $client = new Client(['verify' => false]);
        $acc = new AccessToken();
        $accessToken = $acc->getTokenFromSFA();
        $orderResponse = DB::selectOne("select * from sfaorders where sat_sync<1 and status>0 order by created_at asc");
        if (!is_null($orderResponse)) {
            $headers = [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];
            //dd($headers);
            $response = $client->request('POST', env('SFA_BASE_URL') . '/api/v1/erp/order-response', [
                'headers' => $headers,
                'json' => [
                    'erp_reference' => substr($orderResponse->sage_ref, 0, 3) == 'IOF' ? $orderResponse->sage_ref : '0',
                    'message' => substr($orderResponse->sage_ref, 0, 3) != 'IOF' ? $orderResponse->sage_ref : 'SUCCESS',
                    'code' => substr($orderResponse->sage_ref, 0, 3) != 'IOF' ? -1 : 0,
                    'transaction_id' => $orderResponse->id,
                    'createdate' => $orderResponse->created_at,
                ]
            ]);

            if ($response->getStatusCode() == 200) {

                if (substr($orderResponse->sage_ref, 0, 3) == 'IOF') {
                    $updateeOrder = Order::find($orderResponse->id);
                    $updateeOrder->sat_sync = 1;
                    $updateeOrder->updated_at = Carbon::now();
                    $updateeOrder->save();
                }
                else{
                    Order::find($orderResponse->id)->delete();
                }
            }
            //log error in file or db table
            Log::info('Order Response Posted to SFA');
        }
    //    Log::info('No Order Response Status to send to SFA');
    }
}
