<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPriceList;
use App\Models\ItemList;
use App\Models\Order;
use App\Models\StkItem;
use App\Models\TaxRateType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function __construct()
    {
        ini_set('max_execution_time', 300);
    }
    public function messages()
    {
        return ['transaction_id.unique' => [
            'transaction_reference' => request()->input('id'),
            'transaction_number' => request()->input('id'),
            'message' => 'Transaction id already taken. Contact Pevium System if transaction status not sent',
            'code' => 1,
            'transaction_id' =>request()->input('transaction_id'),
            'createdate' => Carbon::now()
        ]];
    }

    public function postOrders(Order $order)
    {
        $remote_ip=request()->ip();
        $log_time=Carbon::now();
        DB::insert('insert into ip_logs (ip_address, log_time) values (?, ?)', [$remote_ip, $log_time]);

        // $this->messages();
        $data = [
            'id' => 'required',
            'transaction_id' => 'required|unique:SFAOrders',
            "transaction_type" => "required",
            "customer_code" => "required",
            "region" => "required",
            "item_list" => "required",
            "transaction_date" => "required"
        ];

        $customerCode = request()->input('customer_code');
        // $customer = DB::selectOne('select * from vw_Customers where customer_code=?', [$customerCode]);
        $customer=Customer::where('account', $customerCode)->first();
        //added code
        $trans_id = request()->input('transaction_id');

        $response = [];

        $validation = Validator::make(request()->all(), $data);

        if ($validation->fails()) {
            // $response['response'] = $validation->messages();
            return json_encode($validation->messages());
        } else {
            if (is_null($customer)) {
                return $response['response'] = 'The customer code ' . request()->input('customer_code') . ' does not exist';
            }
            $dataSave = [
                'id' => request()->input('id'),
                'transaction_id' => $trans_id,
                'transaction_type' => request()->input('transaction_type'),
                'lpo_number' => request()->input('lpo_number'),
                'user_code' => request()->input('user_code'),
                'vehicle_code' => request()->input('vehicle_code'),
                'customer_code' => request()->input('customer_code'),
                'route' => request()->input('route'),
                'region' => request()->input('region'),
                'warehouse_code' => request()->input('warehouse_code'),
                'store_code' => request()->input('store_code'),
                'item_list' => request()->input('item_list'),
                'transaction_date' => request()->input('transaction_date'),
                // 'client_code' => $client_code,
            ];

            $itemlistArray = request()->input('item_list');
            $itemdata = [];
            $date=Carbon::now();
            foreach ($itemlistArray as $itemArray) {

                $itemdata[] = [
                    'order_id' =>  request()->input('id'),
                    'transaction_id' => $trans_id,
                    'item_code' => $itemArray['item_code'],
                    'uom_code' => $itemArray['uom_code'],
                    'item_price' => $itemArray['item_price'],
                    'item_quantity' => $itemArray['item_quantity'],
                    'tax_code' => $itemArray['tax_code'],
                    'item_text' => $itemArray['item_code'],
                    'warehouse_code' => request()->input('warehouse_code'),
                    'created_at' => $date,
                    'updated_at'=> $date
                ];
            }
            DB::beginTransaction();
            $createOrder = $order->create($dataSave);
            $createItems = $order->itemLists()->insert($itemdata);
            if (!$createOrder || !$createItems)
                DB::rollBack();
            else {
                DB::commit();

                $orderPosted = Order::where('transaction_id', $trans_id)->first();
                $sage_status = $this->postToSage($orderPosted);
                if ($sage_status['status']) {
                    $orderPosted->status = 1;
                    $orderPosted->updated_at=Carbon::now();
                    $orderPosted->save();
                    $response['transaction_reference'] = $dataSave['id'];
                    $response['transaction_number'] = $dataSave['id'];
                    $response['message'] = $sage_status['message'];
                    $response['code'] = 0;
                    // $response['transaction_id'] = substr($dataSave['transaction_id'], 2);
                    $response['transaction_id'] =$dataSave['transaction_id'];
                    $response['createdate'] = Carbon::now();
                } else {
                    $response['transaction_reference'] = $dataSave['id'];
                    $response['transaction_number'] = $dataSave['id'];
                    $response['message'] = $sage_status['message'];
                    $response['code'] = 1;
                    $response['transaction_id'] =$dataSave['transaction_id'];
                    $response['createdate'] = Carbon::now();
                }
                return $response;
            }
        }
    }
    public static function postToSage($order)
    {
        if (!is_null($order)) {
            $customerCode = $order->customer_code;
            $dbInitial = env('SAGE_HOST_DB_NAME');
            // $branches = DB::selectOne('SELECT * FROM ' . $dbInitial . '[_etblBranch] where cBranchDescription=?', [$order->region]);
            $existOnSage=DB::selectOne("SELECT Autoindex  FROM " . $dbInitial . "[INVNUM] where OrderNum='".$order->transaction_id."' and ExtOrderNum='".$order->transaction_id."'");
            if(is_null($existOnSage)){
                $customer_details = DB::selectOne('SELECT DCLink,Name,Post1,Post2,Post3,Post4,AutoDisc FROM ' . $dbInitial . 'Client WHERE Account= ?', [$customerCode]);
                $post1 = $customer_details->Post1;
                $post2 = $customer_details->Post2;
                $post3 = $customer_details->Post3;
                $post4 = $customer_details->Post4;
                $autoDisc = $customer_details->AutoDisc == "" ? 0 : $customer_details->AutoDisc;
                $customerName = $customer_details->Name;
                $date = Carbon::today();
                $unformattedDate = Carbon::parse($order->transaction_date);
                $transaction_date = $unformattedDate->format('Y-m-d');
                // order agent
                $agent=1;
                $sat_agent = DB::selectOne("SELECT idAgents, cAgentName FROM ".$dbInitial."_rtblAgents WHERE cAgentName='SAT'");
                if(!is_null($sat_agent)){
                    $agent=$sat_agent->idAgents;
                }
                // selecting warehouse from header
                $warehouse= DB::selectOne("SELECT WhseLink, Code FROM ".$dbInitial."WhseMst WHERE Code=$order->warehouse_code");
                               
                // Convert transaction date to be showing zeros on time
                // Check branch id
                $header_result=false;
                DB::beginTransaction();
                $header_result = DB::insert('INSERT INTO ' . $dbInitial . '[INVNUM] (DocType, OrderNum, ExtOrderNum, OrderDate, AccountID, iDCBranchID,[DocVersion],
            [DocState],[DocFlag],[OrigDocID],grvid,InvDate,DueDate,DeliveryDate,TaxInclusive, cAccountName, InvNum_iBranchID, Message2, InvDisc, Paddress1, Paddress2,Paddress3,PAddress4, Address1, Address2, iINVNUMAgentID) VALUES
             (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    4, $order->transaction_id, $order->transaction_id, $transaction_date, $customer_details->DCLink, 0, 3, 1, 0, 0, 0, $date, $date, $date, 1, $customerName, 0, 'SOL Order',
                    $autoDisc, $post1, $post2, $post3, $post4, $post1, $post2, $agent
                ]);

                $invoiceId = DB::getPdo()->lastInsertId();
                // //Ware house code changes depending on branch
                // $wareHouseID = DB::selectOne('SELECT WhseLink FROM ' . $dbInitial . 'WhseMst WHERE Code=?', ['TRA-NYERI']);
                $OrderNum = $order->transaction_id;
                // //loop starts here

                // $itemLists = DB::select('select * from item_lists where transaction_id = ?', [$OrderNum]);
                $itemLists = ItemList::where('transaction_id', $OrderNum)->get();
                $item_results=false;
                // Log::alert(json_encode($itemLists));
                foreach ($itemLists as $line) {
                    $stk_items = StkItem::where('Code', $line->item_code)->first();
                    // $stk_items = DB::selectOne('select * from stk_items where code = ?', [$line->item_code]);
                    if (!is_null($stk_items)) {
                        $stockId = $stk_items->StockLink;
                        $unitPrice = CustomerPriceList::where('Account', $customerCode)->where('iStockID', $stockId)->first();
                        $Description = $stk_items->Description_1;
                        $unitsOfMeasID = $stk_items->iUOMStockingUnitID;
                        $fUnitPriceExcl = $line->price ?? $unitPrice->fExclPrice;
                        $fUnitPriceIncl = $line->price ?? $unitPrice->fInclPrice;
                        $priceListId = $unitPrice->iARPriceListNameID;
                        $qty = $line->item_quantity;
                        // $etblUnits = DB::selectOne('select idUnits from ' . $dbInitial . '[_etblUnits] WHERE cUnitCode=?', [$line->uom_code]);
                        //getting uomid from stkitems instead of etblunits
                        $uomId = $stk_items->iUOMStockingUnitID;
                        $iTaxType = TaxRateType::where('StockLink', $stockId)->first();
                        $iTaxTypeID = $iTaxType->idTaxRate;
                        $taxRateTable = DB::selectOne('select TaxRate from ' . $dbInitial . 'TaxRate WHERE idTaxRate = ?', [$iTaxTypeID]);
                        $taxRate = $taxRateTable->TaxRate;
                        //hardcoded discount since i dont know how its found
                        // $discountPer = CustomerDiscount::where('Account', $customerCode)->where('StockLink', $stockId)->first();
                        // $percent = $discountPer->Percentage;

                        $fnQtPriceInc = $line->item_quantity * $fUnitPriceIncl;
                        $fnQtPriceExc = $line->item_quantity * $fUnitPriceExcl;
                        $item_results = DB::insert(
                            'INSERT INTO ' . $dbInitial . '[_btblInvoiceLines] (fLineDiscount,iInvoiceid, iOrigLineID, iGrvLineID, cDescription, iUnitsOfMeasureStockingID,
                                istockcodeid, iwarehouseid, iunitsofmeasureid, ipricelistnameid, fQuantity, fQtyChange, funitpriceEXCL, fUnitPriceIncl,
                                iTaxTypeID, fTaxRate, bIsWhseItem, fQuantityLineTotIncl, fQuantityLineTotExcl, fQuantityLineTotInclNoDisc, fQuantityLineTotExclNoDisc,
                                fQuantityLineTaxAmount, fQuantityLineTaxAmountNoDisc,fQuantityUR,fQtyToProcessUR) VALUES (?, ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                            [
                                0, $invoiceId, 0, 0, $Description, $unitsOfMeasID, $stockId, $warehouse->WhseLink, $uomId, $priceListId, $qty, $qty, $fUnitPriceExcl,
                                $fUnitPriceIncl, $iTaxTypeID, $taxRate, 1, $fnQtPriceInc, $fnQtPriceExc, $fnQtPriceInc, $fnQtPriceExc, $fnQtPriceInc * $taxRate / 100,
                                $fnQtPriceExc * $taxRate / 100, $qty, $qty
                            ]
                        );
                        // end of invoice details
                    }
                }
                if (!$header_result || !$item_results) {
                    DB::rollBack();
                    return ['status'=>false, 'message'=> 'Unable to post order to sage due to unknown error. This transaction will be cleared from the staging table'];
                } else {
                    DB::commit();
                    return ['status'=>true, 'message'=>'SUCCESS'];
                }
            }
            else{
                return ['status'=>false, 'message'=> 'Transaction '.$order->transaction_id.' already exist in sage. The transaction will be cleared from the staging table'];
            }
        }
    }
    public static function sageResync()
    {
        $orderToPost = Order::where('status', 0)->orderBy('created_at', 'asc')->get();
        if(count($orderToPost)>0){
            foreach($orderToPost as $order){
                $sagePost=self::postToSage($order);
                if($sagePost){
                    $order->status = 1;
                    $order->updated_at=Carbon::now();
                    $order->save();
                }
                else{
                    Log::error('Unable to post to sage. Will retry again in few minutes');
                }
            }
        }

    }
}
