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
        ini_set('memory_limit', '-1');
        $rows = DB::select("SELECT * FROM vw_OrderTrackingWithItems");
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
                $discount = $row->customer_discount ?? 0;
                $itemPrice = $discount > 0
                    ? round($row->item_price * (100 - $discount) / 100, 2)
                    : $row->item_price;

                return [
                    'item_code'     => $row->item_code,
                    'uom_code'      => $row->uom_code,
                    'item_quantity' => $row->item_quantity,
                    'base_price'    => $row->item_price,
                    'item_price'    => $itemPrice,
                    'item_status'   => $itemStatus,
                ];
            })->values()->toArray();

            $encodedItems = json_encode($items);
            $now = Carbon::now();

            // Check existence using either transaction_id OR doc_num
            // $orderTrackerStatus = OrderTracking::where('transaction_id', $trans->ExtOrderNum)
            //     ->orWhere('doc_num', $trans->InvNumber)
            //     ->first();
            $orderTrackerStatus = OrderTracking::where(function ($q) use ($trans) {
                $q->where('transaction_id', 'like', 'SATCHA%')
                ->where('transaction_id', $trans->ExtOrderNum);
            })
            ->orWhere('doc_num', $trans->InvNumber)
            ->first();


            if (!is_null($orderTrackerStatus)) {
                // Skip records already in a final status
                // Log::info("Order tracker updated: {$trans->ExtOrderNum} and status {$orderTrackerStatus->status}");
                if (in_array($orderTrackerStatus->status, $finalStatuses)) {
                    if ($orderTrackerStatus->doc_num !== $trans->InvNumber
                        && !OrderTracking::where('doc_num', $trans->InvNumber)->exists()) {
                        // Different document (e.g. credit note after invoice) — insert new record
                        DB::insert(
                            'INSERT INTO PevOrderTracking (transaction_id, doc_num, status, item_list, sage_modify_time, created_at, updated_at, customer_code, date, sales_rep, original_doc_num) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                            [$trans->ExtOrderNum, $trans->InvNumber, $trackerStatus, $encodedItems, $trans->InvNum_dModifiedDate, $now, $now, $trans->Account, $trans->InvDate, $trans->sales_rep, $trans->DeliveryNote]
                        );
                    }
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
                        'sales_rep' => $trans->sales_rep,
                        'original_doc_num' => $trans->DeliveryNote
                    ]);
                   //Log::info("Order tracker updated: {$trans->ExtOrderNum}");
                }
            } else {
                DB::insert(
                    'INSERT INTO PevOrderTracking (transaction_id, doc_num, status, item_list, sage_modify_time, created_at, updated_at, customer_code, date, sales_rep, original_doc_num) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                    [$trans->ExtOrderNum, $trans->InvNumber, $trackerStatus, $encodedItems, $trans->InvNum_dModifiedDate, $now, $now, $trans->Account, $trans->InvDate, $trans->sales_rep, $trans->DeliveryNote]
                );
                //Log::info("Order tracker inserted: {$trans->ExtOrderNum}");
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
        $batchSize = 20;
        $client = new Client(['verify' => false]);
        $acc = new AccessToken();
        $accessToken = $acc->getTokenFromSFA();

        if (!$accessToken) {
            Log::error("SFA PushJob: Failed to retrieve access token from SFA.");
            return;
        }

        DB::beginTransaction();
        
        try {
            // Lock multiple rows for update
            $orderStatuses = DB::table('PevOrderTracking')
                ->where(function($query) {
                    $query->where('status', '<>', 'Order')
                        ->where('insertFlag', 0)
                        ->where('updateFlag', 0);
                })
                ->orWhere(function($query) {
                    $query->where('insertFlag', 1)
                        ->where('updateFlag', 0);
                })
                ->orderBy('created_at', 'DESC')
                ->limit($batchSize)
                ->lockForUpdate()
                ->get();

            if ($orderStatuses->isEmpty()) {
                DB::commit();
                Log::info('SFA PushJob: No Order Status to send.');
                return;
            }

            $orderIds = $orderStatuses->pluck('id')->toArray();

            // Mark all as processing
            DB::table('PevOrderTracking')
                ->whereIn('id', $orderIds)
                ->update([
                    'insertFlag' => 3, // Processing
                    'updateFlag' => 3,
                    'updated_at' => Carbon::now()
                ]);
            
            DB::commit();
            
            // Log::info("SFA PushJob: Locked {$orderStatuses->count()} orders for processing", [
            //     'order_ids' => $orderIds
            // ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("SFA PushJob: Failed to lock orders for processing", [
                'error' => $e->getMessage()
            ]);
            return;
        }

        // Process each order
        $results = [
            'success' => 0,
            'failed' => 0,
            'total' => $orderStatuses->count()
        ];

        foreach ($orderStatuses as $orderStatus) {
            $success = $this->processOrder($orderStatus, $client, $accessToken);
            
            if ($success) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        // Log::info("SFA PushJob: Batch processing completed", $results);
        
        return $results;
    }

    /**
     * Process a single order
     */
    private function processOrder($orderStatus, Client $client, string $accessToken): bool
    {
        $finalInsertFlag = 2;
        $finalUpdateFlag = 2;
        $updateSuccess = false;

        try {
            $headers = [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json'
            ];

            $endpoint = $orderStatus->status === 'credit_note'
                ? '/api/v1/sap/erp-credit-notes'
                : '/api/v1/sap/erp-invoices';

            $payload = [
                'transaction_id' => substr($orderStatus->transaction_id, 0, 6) === 'SATCHA'
                    ? substr($orderStatus->transaction_id, 6)
                    : null,
                'doc_num'       => $orderStatus->doc_num,
                'item_list'     => json_decode($orderStatus->item_list, true),
                'date'          => $orderStatus->date,
                'status'        => $orderStatus->status,
                'customer_code' => $orderStatus->customer_code,
                'user_code'     => $orderStatus->sales_rep
            ];

            if ($orderStatus->status === 'credit_note') {
                $docNum = $orderStatus->original_doc_num;
                if (stripos($docNum, 'DEL') === 0) {
                    $docNum = substr($docNum, 3);
                }
                $payload['invoice_doc_num'] = $docNum;
            }

            $response = $client->post(env('SFA_BASE_URL') . $endpoint, [
                'headers' => $headers,
                'json'    => $payload,
                'timeout' => 30
            ]);

            $status = $response->getStatusCode();
            $body   = (string) $response->getBody();

            if ($status === 200) {
                $finalInsertFlag = 1;
                $finalUpdateFlag = 1;
                $updateSuccess = true;
                // Log::info("SFA PushJob: Order successfully posted", [
                //     'order_id' => $orderStatus->id,
                //     'doc_num' => $orderStatus->doc_num
                // ]);
            } else {
                Log::warning("SFA PushJob: Order rejected by SFA", [
                    'order_id' => $orderStatus->id,
                    'doc_num' => $orderStatus->doc_num,
                    'status_code' => $status,
                    'response' => $body
                ]);
            }

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error("SFA PushJob: HTTP request failed", [
                'order_id' => $orderStatus->id,
                'doc_num' => $orderStatus->doc_num,
                'error_message' => $e->getMessage(),
                'response' => $e->hasResponse() ? (string) $e->getResponse()->getBody() : null
            ]);
        } catch (\Exception $e) {
            Log::error("SFA PushJob: Exception during order processing", [
                'order_id' => $orderStatus->id,
                'doc_num' => $orderStatus->doc_num,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            // Always update flags
            try {
                DB::table('PevOrderTracking')
                    ->where('id', $orderStatus->id)
                    ->update([
                        'insertFlag' => $finalInsertFlag,
                        'updateFlag' => $finalUpdateFlag,
                        'updated_at' => Carbon::now()
                    ]);
            } catch (\Exception $e) {
                Log::critical("SFA PushJob: CRITICAL - Failed to update flags", [
                    'order_id' => $orderStatus->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $updateSuccess;
    }
	public function pushOrderStatusOldBImprove()
    {
        //Log::info("SFA PushJob: Starting pushOrderStatus()");

        $client = new Client(['verify' => false]);
        $acc = new AccessToken();
        $accessToken = $acc->getTokenFromSFA();

        if (!$accessToken) {
            Log::error("SFA PushJob: Failed to retrieve access token from SFA.");
            return;
        }

    // Log::info("SFA PushJob: Access token retrieved successfully.");

        $orderStatus = DB::selectOne("
            SELECT * FROM [PevOrderTracking]
            WHERE
                (status <> 'Order' AND [insertFlag] = 0 AND [updateFlag] = 0)
                OR 
                ([insertFlag] = 1 AND [updateFlag] = 0)
            ORDER BY created_at DESC
        ");

        if (is_null($orderStatus)) {
            Log::info('SFA PushJob: No Order Status to send.');
            return;
        }


        try {
            $headers = [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept'        => 'application/json',
                'Content-Type' => 'application/json'
            ];

            $endpoint = '/api/v1/sap/erp-invoices';
            if ($orderStatus->status === 'credit_note') {
                $endpoint = '/api/v1/sap/erp-credit-notes';
            }

            $payload = [
                'transaction_id' => substr($orderStatus->transaction_id, 0, 6) == 'SATCHA'
                    ? substr($orderStatus->transaction_id, 6)
                    : null,
                'doc_num'       => $orderStatus->doc_num,
                'item_list'     => json_decode($orderStatus->item_list, true),
                'date'          => $orderStatus->date,
                'status'        => $orderStatus->status,
                'customer_code' => $orderStatus->customer_code,
                'user_code'     => $orderStatus->sales_rep
            ];

            // Log::info("SFA PushJob: Sending payload", [
            //     'endpoint' => $endpoint,
            //     'payload'  => $payload
            // ]);

            $response = $client->post(env('SFA_BASE_URL') . $endpoint, [
                'headers' => $headers,
                'json'    => $payload
            ]);

            $status = $response->getStatusCode();
            $body   = (string) $response->getBody();

            // Log::info("SFA PushJob: Response received", [
            //     'status_code' => $status,
            //     'response_body' => $body
            // ]);

            $updateOrderTracking = OrderTracking::find($orderStatus->id);

            if ($status == 200) {
                $updateOrderTracking->insertFlag = 1;
                $updateOrderTracking->updateFlag = 1;
                //Log::info("SFA PushJob: Order successfully posted.");
            } elseif ($status >= 400 && $status <= 500) {
                $updateOrderTracking->insertFlag = 2;
                $updateOrderTracking->updateFlag = 2;
                Log::warning("SFA PushJob: Order rejected by SFA.", [
                    'status_code' => $status,
                    'response' => $body
                ]);
            }

            $updateOrderTracking->updated_at = Carbon::now();
            $updateOrderTracking->save();

        } catch (\Exception $e) {

            Log::error("SFA PushJob: Exception thrown while posting to SFA", [
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $updateOrderTracking = OrderTracking::find($orderStatus->id);

            if ($updateOrderTracking) {
                $updateOrderTracking->insertFlag = 2;
                $updateOrderTracking->updateFlag = 2;
                $updateOrderTracking->updated_at = Carbon::now();
                $updateOrderTracking->save();
            }
        }
    }

	
	
    public function pushOrderStatusod()
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
                    $endpoint = '/api/v1/sap/erp-credit-notes';
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

// USE [sfa_evo]
// GO

// /****** Object:  View [dbo].[vw_OrderTrackingWithItems]    Script Date: 27/01/2026 14:49:28 ******/
// SET ANSI_NULLS ON
// GO

// SET QUOTED_IDENTIFIER ON
// GO


// CREATE VIEW [dbo].[vw_OrderTrackingWithItems] AS
// SELECT
//      i.OrderNum, i.ExtOrderNum, i.Description,
//      i.InvNum_dModifiedDate, i.AutoIndex, i.InvDate, i.InvNumber, i.DocState,
//      c.Account, s.Code AS sales_rep, i.DocType,
//      st.Code AS item_code, st.Pack AS uom_code,
//      CAST(ROUND(l.fQuantity, 2) AS numeric(36,2)) AS item_quantity,
//      CAST(ROUND(l.fUnitPriceExcl, 2) AS numeric(36,2)) AS item_price
//  FROM cil..InvNum i
//  INNER JOIN cil..Client c ON i.AccountID = c.DCLink
//  INNER JOIN cil..SalesRep s ON s.idSalesRep = i.DocRepID
//  INNER JOIN cil.._btblInvoiceLines l ON l.iInvoiceID = i.AutoIndex
//  INNER JOIN cil..StkItem st ON st.StockLink = l.iStockCodeID
//  WHERE i.DocState > 1
//    AND i.DocType IN (1, 4)
//    AND i.InvNumber IS NOT NULL
//    AND i.InvNumber <> ''
//    AND i.OrderDate >= CAST(DATEADD(MONTH, -3, GETDATE()) AS DATE);
// GO



