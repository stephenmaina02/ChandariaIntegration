<?php

namespace App\Service;

use App\Http\Resources\Order;
use App\Models\StkItem;
use App\Models\ItemList;
use App\Models\TaxRateType;
use Illuminate\Support\Carbon;
use App\Models\Order as Orders;
use App\Models\CustomerPriceList;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function saveData($orderHeader, $orderDetails)
    {
        DB::beginTransaction();
        if (!Orders::create($orderHeader) || !ItemList::insert($orderDetails)) {
            DB::rollBack();
            return false;
        } else {
            DB::commit();
            return true;
        }
    }
    public function sageCustomerDetails($customerCode)
    {
        return DB::selectOne('SELECT DCLink,Name,Post1,Post2,Post3,Post4,AutoDisc FROM ' . env('SAGE_HOST_DB_NAME') . 'Client WHERE Account= ?', [$customerCode]);
    }
    public function sageStockItem($itemCode, $order_id)
    {
        $itemDetail = StkItem::where('Code', $itemCode)->first();
        if (is_null($itemDetail)) {
            die('Item with code ' . $itemCode . ' does not exist on sage. Order with transaction id ' . $order_id . ' not inserted to sage');
        } else
            return $itemDetail;
    }
    public function deleteFailedOrder($order_id)
    {
        Orders::where('transaction_id', $order_id)->first()->delete();
        DB::delete('delete item_lists where transaction_id = ?', [$order_id]);
    }
    public function sageCustomerPriceList($customerCode, $stockId)
    {
        return CustomerPriceList::where('Account', $customerCode)->where('iStockID', $stockId)->first();
    }
    public function stockItemTaxType($stockLink)
    {
        return TaxRateType::where('StockLink', $stockLink)->first();
    }
    public function stockItemTaxRate($iTaxTypeID)
    {
        return DB::selectOne('select TaxRate from ' . env('SAGE_HOST_DB_NAME') . 'TaxRate WHERE idTaxRate = ?', [$iTaxTypeID]);
    }
    public function saveSageLineDetails($order, $invoiceID)
    {
        $lineInsertArray = [];
        foreach ($order['item_list'] as $line) {
            $stk_items = $this->sageStockItem($line['item_code'], $order['transaction_id']);
            $stockId = $stk_items->StockLink;
            $unitPrice = $this->sageCustomerPriceList($order['customer_code'], $stockId);
            $taxRate = $this->stockItemTaxRate($this->stockItemTaxType($stk_items->StockLink)->idTaxRate)->TaxRate;
            $fUnitPriceExcl = $unitPrice->fExclPrice ?? $line['item_price'] ?? 0;
            $fUnitPriceIncl = $unitPrice->fInclPrice ?? $line['item_price'] ?? 0;
            $fnQtPriceInc = $line['item_quantity'] * $fUnitPriceIncl;
            $fUnitPriceExcl = $fUnitPriceIncl - $fnQtPriceInc*$taxRate/100; 
            $fnQtPriceInc = $line['item_quantity'] * $fUnitPriceIncl;
            $fnQtPriceExc = $line['item_quantity'] * $fUnitPriceExcl;
            $isLineSaved = DB::insert(
                'INSERT INTO ' . env('SAGE_HOST_DB_NAME') . '[_btblInvoiceLines] (fLineDiscount,iInvoiceid, iOrigLineID, iGrvLineID, cDescription, iUnitsOfMeasureStockingID,
                        istockcodeid, iwarehouseid, iunitsofmeasureid, ipricelistnameid, fQuantity, fQtyChange, funitpriceEXCL, fUnitPriceIncl,
                        iTaxTypeID, fTaxRate, bIsWhseItem, fQuantityLineTotIncl, fQuantityLineTotExcl, fQuantityLineTotInclNoDisc, fQuantityLineTotExclNoDisc,
                        fQuantityLineTaxAmount, fQuantityLineTaxAmountNoDisc,fQuantityUR,fQtyToProcessUR) VALUES (?, ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    0, $invoiceID, 0, 0, $stk_items->Description_1, $stk_items->iUOMStockingUnitID, $stockId, 0, $stk_items->iUOMStockingUnitID, $unitPrice->iARPriceListNameID ?? '', $line['item_quantity'], $line['item_quantity'], $fUnitPriceExcl,
                    $fUnitPriceIncl, $this->stockItemTaxType($stk_items->StockLink)->idTaxRate, $taxRate, 1, $fnQtPriceInc, $fnQtPriceExc, $fnQtPriceInc, $fnQtPriceExc, $fnQtPriceInc * $taxRate / 100,
                    $fnQtPriceExc * $taxRate / 100, $line['item_quantity'], $line['item_quantity']
                ]
            );
            if ($isLineSaved)
                array_push($lineInsertArray, true);
            else
                array_push($lineInsertArray, false);
        }
        if (in_array(false, $lineInsertArray))
            return false;
        else
            return true;
    }
    public function saveToSage($order)
    {
        $existOnSage = DB::select("SELECT Autoindex  FROM " . env('SAGE_HOST_DB_NAME') . "[INVNUM] where OrderNum='" . $order['transaction_id'] . "' and ExtOrderNum='" . $order['transaction_id'] . "'");
        if (count($existOnSage) < 1) {
            $customer_details = $this->sageCustomerDetails($order['customer_code']);
            $orderDate = Carbon::today();
            DB::beginTransaction();
            $header_result = DB::insert('INSERT INTO ' . env('SAGE_HOST_DB_NAME') . '[INVNUM] (DocType, OrderNum, ExtOrderNum, OrderDate, AccountID, iDCBranchID,[DocVersion],
                [DocState],[DocFlag],[OrigDocID],grvid,InvDate,DueDate,DeliveryDate,TaxInclusive, cAccountName, InvNum_iBranchID, Message2, InvDisc, Paddress1, Paddress2,Paddress3,PAddress4,
                Address1, Address2, DocRepID) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                4, $order['transaction_id'], $order['transaction_id'], Carbon::parse($order['transaction_date'])->format('Y-m-d'), $customer_details->DCLink, 0, 3, 1, 0, 0, 0, $orderDate, $orderDate, $orderDate, 1, $customer_details->Name, 0, 'SOL Order',
                $customer_details->AutoDisc, $customer_details->Post1, $customer_details->Post2, $customer_details->Post3, $customer_details->Post4, $customer_details->Post1, $customer_details->Post2
            ,$this->salesRep($order['user_code'])]);
            // $invoiceId = DB::getPdo()->lastInsertId();
            $invoiceId = $this->sageAutoIndex($order['transaction_id']);
            // \Log::info($invoiceId);
            $linesSaved = $this->saveSageLineDetails($order, $invoiceId);
            if (!$header_result || !$linesSaved) {
                DB::rollBack();
                return ['status'=>false, 'message'=> 'Unable to post order to sage due to unknown error.'];
            } else {
                DB::commit();
                return ['status'=>true, 'message'=>'SUCCESS'];
            }
        } else {
            return ['status'=>false, 'message'=> 'Transaction '.$order['transaction_id'].' already exist in sage.'];
        }
    }

    public function salesRep($userCode)
    {
        $rep=DB::selectOne("SELECT idSalesRep, Code FROM " . env('SAGE_HOST_DB_NAME') ."SalesRep WHERE Code=?", [$userCode]);
        if(is_null($rep)) return '';
        else return $rep->idSalesRep;
    }

    public function sageAutoIndex($orderNum)
    {
        $inv_recod= DB::selectOne("SELECT AutoIndex, OrderNum, ExtOrderNum FROM " . env('SAGE_HOST_DB_NAME') ."InvNum WHERE OrderNum=? AND ExtOrderNum=?", [$orderNum, $orderNum]);
        return $inv_recod->AutoIndex ?? 0;
    }
}
