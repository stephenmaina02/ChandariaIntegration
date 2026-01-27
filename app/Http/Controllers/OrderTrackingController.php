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
        $rows = DB::select('SELECT * FROM vw_OrderTrackingWithItems');

        if (empty($rows)) {
            Log::info('All Orders/Invoices already synced from Sage');
            return;
        }

        $finalStatuses = ['Invoiced', 'Cancelled Order', 'credit_note'];
        $grouped = collect($rows)->groupBy('AutoIndex');

        foreach ($grouped as $autoIndex => $group) {
            $trans = $group->first();
            $trackerStatus = $this->resolveTrackerStatus($trans);

            $itemStatus = $trans->DocState == 4 ? "Delivered" : "Not Delivered";
            $items = $group->map(function ($row) use ($itemStatus) {
                return [
                    'item_code' => $row->item_code,
                    'uom_code' => $row->uom_code,
                    'item_quantity' => $row->item_quantity,
                    'item_price' => $row->item_price,
                    'item_status' => $itemStatus,
                ];
            })->values()->toArray();

            $encodedItems = json_encode($items);
            $now = Carbon::now();

            // Check existence using either transaction_id OR doc_num
            $orderTrackerStatus = OrderTracking::where('transaction_id', $trans->ExtOrderNum)
                ->orWhere('doc_num', $trans->InvNumber)
                ->first();

            if (!is_null($orderTrackerStatus)) {
                // Skip records already in a final status
                if (in_array($orderTrackerStatus->status, $finalStatuses)) {
                    continue;
                }

                // Only update if status has changed
                if ($orderTrackerStatus->status !== $trackerStatus) {
                    $orderTrackerStatus->update([
                        'status' => $trackerStatus,
                        'item_list' => $encodedItems,
                        'date' => $trans->InvDate,
                        'sage_modify_time' => $trans->InvNum_dModifiedDate,
                        'updated_at' => $now,
                        'updateFlag' => 0,
                        'doc_num' => $trans->InvNumber,
                        'sales_rep' => $trans->sales_rep
                    ]);
                    Log::info("Order tracker updated: {$trans->ExtOrderNum}");
                }
            } else {
                DB::insert(
                    'INSERT INTO PevOrderTracking (transaction_id, doc_num, status, item_list, sage_modify_time, created_at, updated_at, customer_code, date, sales_rep) VALUES (?,?,?,?,?,?,?,?,?,?)',
                    [$trans->ExtOrderNum, $trans->InvNumber, $trackerStatus, $encodedItems, $trans->InvNum_dModifiedDate, $now, $now, $trans->Account, $trans->InvDate, $trans->sales_rep]
                );
                Log::info("Order tracker inserted: {$trans->ExtOrderNum}");
            }
        }
    }

    private function resolveTrackerStatus($trans): string
    {
        if ($trans->DocType == 1) {
            return 'credit_note';
        }

        if ($trans->DocState == 0 || $trans->DocState == 1) return 'Order';
        if ($trans->DocState == 2) return 'Quote';
        if ($trans->DocState == 3) return 'Partially Invoiced';
        if ($trans->DocState == 4) return 'Invoiced';

        return 'Cancelled Order';
    }
    public function pushOrderStatus()
    {
        // $this->getOrderTrackerFromSage();
        $client = new Client(['verify' => false]);
        $acc = new AccessToken();
        $accessToken = $acc->getTokenFromSFA();

        $orderStatus = DB::selectOne("select * from [PevOrderTracking] 
        where status<>'Order' AND [insertFlag] = 0 AND [updateFlag] = 0 
           OR [insertFlag] = 1 AND [updateFlag] = 0 
        order by created_at desc");

        if (!is_null($orderStatus)) {
            try {
                $headers = [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ];

                $endpoint = '/api/v1/sap/erp-invoices';
                if($orderStatus->status == 'credit_note'){
                    $endpoint = '/api/v1/sap/sap-credit';
                }

                $response = $client->request('POST', env('SFA_BASE_URL') . $endpoint, [
                    'headers' => $headers,
                    'json' => [
                        'transaction_id' => substr($orderStatus->transaction_id, 0, 6) == 'SATCHA'
                            ? substr($orderStatus->transaction_id, 6)
                            : null,
                        'doc_num'       => $orderStatus->doc_num,
                        'item_list'     => json_decode($orderStatus->item_list, true),
                        'date'          => $orderStatus->date,
                        'status'        => $orderStatus->status,
                        'customer_code' => $orderStatus->customer_code,
                        'user_code' => $orderStatus->sales_rep
                    ]
                ]);

                if ($response->getStatusCode() == 200) {
                    $updateOrderTracking = OrderTracking::find($orderStatus->id);
                    $updateOrderTracking->insertFlag = 1;
                    $updateOrderTracking->updateFlag = 1;
                    $updateOrderTracking->updated_at = Carbon::now();
                    $updateOrderTracking->save();
                }

                if ($response->getStatusCode() >= 400 && $response->getStatusCode() <= 500) {
                    $updateOrderTracking = OrderTracking::find($orderStatus->id);
                    $updateOrderTracking->insertFlag = 2;
                    $updateOrderTracking->updateFlag = 2;
                    $updateOrderTracking->updated_at = Carbon::now();
                    $updateOrderTracking->save();
                }

                Log::info('Order Posted to SFA');
            } catch (\Exception $e) {
                // handle unexpected errors (network issues, server errors, etc.)
                $updateOrderTracking = OrderTracking::find($orderStatus->id);
                if ($updateOrderTracking) {
                    $updateOrderTracking->insertFlag = 2;
                    $updateOrderTracking->updateFlag = 2;
                    $updateOrderTracking->updated_at = Carbon::now();
                    $updateOrderTracking->save();
                }

                Log::error("Error posting order to SFA: " . $e->getMessage());
            }
        } else {
            Log::info('No Order Status to send to SFA');
        }
    }

    public function pushOrderStatusOld()
    {
        $this->getOrderTrackerFromSage();
        $client = new Client(['verify' => false]);
        $acc = new AccessToken();
        $accessToken = $acc->getTokenFromSFA();
        $orderStatus = DB::selectOne("select * from [PevOrderTracking] where status<>'Order' AND [insertFlag] = 0 AND [updateFlag] = 0 OR [insertFlag] = 1 AND [updateFlag] = 0 order by created_at desc");
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
                    'transaction_id' => substr($orderStatus->transaction_id, 0, 6) == 'SATCHA' ?  substr($orderStatus->transaction_id, 6) : null,
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
            if ($response->getStatusCode() >= 400 && $response->getStatusCode() <= 500) {
                $updateOrderTracking->insertFlag = 2;
                $updateOrderTracking->updateFlag = 2;
                $updateOrderTracking->updated_at = Carbon::now();
                $updateOrderTracking->save();
            }
            //log error in file or db table
            Log::info('Order Posted to SFA');
        } else
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
                } else {
                    Order::find($orderResponse->id)->delete();
                }
            }
            //log error in file or db table
            Log::info('Order Response Posted to SFA');
        }
        //    Log::info('No Order Response Status to send to SFA');
    }
}
