<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\MasterFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use App\Models\Product;
use App\Models\Pricelist;
use Illuminate\Support\Carbon;

class MasterFileController extends Controller
{

    //get products from sage to sales force
    public function getPriceList()
    {
        ini_set('log_errors', 1);
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', -1);
        $pricelist = DB::select('select top 30000 id, pricelist_name, pricelist_code, uom_code, uom_name, product_code, price from pricelists where status=0');
        $ids=collect($pricelist)->pluck('id')->toArray();
        // DB::table('pricelists')->whereIn('id', $ids)->update(['status'=>1]);
        $idsString = implode(',', $ids);
        // Execute raw SQL update query
        if(!empty($idsString)){
            DB::statement("UPDATE pricelists SET status = 1 WHERE id IN ($idsString)");
        }
        // dd(collect($pricelist));
        return ([
            'code' => 0,
            "message" => 'Success',
            'PriceList' => new MasterFile($pricelist)
        ]
            );
    }

    public function getPriceListFromSage()
    {
        ini_set('log_errors', 1);
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', -1);
        $pricelists = DB::select('select pricelist_name, pricelist_code, uom_code, uom_name, product_code, price from vw_PevPricelists');
        // DB::table('pricelists')->truncate();
        $dataToSave = [];
        foreach ($pricelists as $pricelist) {
            $price = Pricelist::updateOrCreate(
                [
                    'pricelist_name' => $pricelist->pricelist_name,
                    'product_code' => $pricelist->product_code,
                    'price' => $pricelist->price
                ],
                [
                    'pricelist_code' => $pricelist->pricelist_code,
                    'uom_code' => $pricelist->uom_code,
                    'uom_name' => $pricelist->uom_name
                ]
            );
            if(!$price->wasRecentlyCreated && $price->wasChanged()){
                $price->status=1;
                $price->save();
            }
            // $now = Carbon::now();
            // $dataToSave[] = [
            //     'pricelist_name' => $pricelist->pricelist_name
            //     , 'pricelist_code' => $pricelist->pricelist_code
            //     , 'uom_code' => $pricelist->uom_code
            //     , 'uom_name' => $pricelist->uom_name
            //     , 'product_code' => $pricelist->product_code,
            //     'price' => $pricelist->price,
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ];
        }
        // DB::table('pricelists')->insert($dataToSave);
        //Log::info('Pricelists retrieved');
    // return ( [
    //     'code' => 0,
    //     "message" => 'Success',
    //     'PriceList' => new MasterFile($pricelist)
    // ]
    // );
    }
    //get deliveries from sage to sales force
    public function getDelivery()
    {

    }
    //get stock balance from sage to sales force
    public function getStockBalance()
    {
        ini_set('log_errors', 1);
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 600);
        $stockBalance = DB::select('select * from vw_PevStockBalance where Quantity<>0');
        return ([
            'StockBalances' => new MasterFile($stockBalance),
            'code' => 0,
            "message" => 'Success',
        ]
            );
    }
    //get customer credit limit from sage to sales force
    public function getCustomerCreditLimit()
    {
        $customer_code = request()->input('customer_code');
        $customer = DB::selectOne('select * from vw_Customers where customer_code=?', [$customer_code]);
        $customerLimit = DB::selectOne('select * from vw_CreditLimit where customer_code=?', [$customer_code]);

        if (is_null($customerLimit) && !is_null($customer)) {
            return json_encode(['Message' => ' The Customer with specified code has no credit information']);
        }
        elseif (is_null($customerLimit) && is_null($customer)) {
            return json_encode(['Message' => 'Customer Code does not exist']);
        }
        return ([
            'customer_code' => $customerLimit->customer_code,
            'credit_limit' => $customerLimit->Credit_Limit,
            'customer_balance' => $customerLimit->customer_balance,
            'payment_terms' => $customerLimit->payment_terms,
            'payment_days' => explode(" ", $customerLimit->payment_terms)[0] ?? '0',
            'open_invoices' => json_decode($customerLimit->open_invoices, true),
            "message" => 'SUCCESS',
            'code' => 0]);

    }
    //fetch product
    public function getFilteredProduct()
    {
        $principal_code = request()->input('principal_code');
        $filteredProduct = Product::where('principal_code', $principal_code)->get();
        if (is_null($filteredProduct))
            return json_encode(['Message' => 'No product exists for entered principal code']);
        else {
            return (new MasterFile($filteredProduct));
        }
    }

    public function getCustomers()
    {
        $customers = DB::select('select * from vw_Customers where customer_code NOT LIKE ?', ['%-OLD%']);
        return (new MasterFile($customers));
    }
}
