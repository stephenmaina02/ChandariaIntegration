<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use App\Models\Customer;
use App\Classes\AccessToken;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public static function pushToSfa()
    {
        //$customer = DB::table('customers')->latest()->where('status', 0)->first();
        $customers = Customer::where('status', 0)->take(25)->get();
        if (!is_null($customers)) {
            $client = new Client(['verify'=>false]);
            $acc = new AccessToken();
            $accessToken = $acc->getTokenFromSFA();

            $headers = [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];
            foreach ($customers as $customer) {
                $response = $client->request('POST', env('SFA_BASE_URL') . '/api/v1/sap/sap-customers', [
                    'headers' => $headers,
                    'json' => [
                        'name' => $customer->name,
                        'region' => $customer->region,
                        'location' => $customer->location,
                        'account' => $customer->account,
                        'category' => $customer->category,
                        'phone_number' => $customer->phone_number,
                        'customer_code' => $customer->customer_code,
                        'customer_warehouse' => $customer->customer_warehouse,
                        'pricelist_code' => $customer->pricelist_code,
                        'credit_limit' => $customer->credit_limit,
                        'email' => $customer->email,
                        'latitude' => $customer->latitude,
                        'longitude' => $customer->longitude,
                        'kra_pin' => $customer->kra_pin,
                        'contact_person' => $customer->contact_person,
                        'postal_address' => $customer->postal_address,
                        'discount_rate'=>$customer->discount,
                    ]
                ]);


                if ($response->getStatusCode() == 200) {
                    $updatecustomer = Customer::find($customer->id);
                    $updatecustomer->status = 1;
                    $updatecustomer->updated_at = Carbon::now();
                    $updatecustomer->save();
                    Log::info('Customer '.$customer->account.' posted to SFA');
                } else {
                    Log::error($response->getBody());
                }
            }
            //log error in file or db table
        }

        Log::info('No customer to send to SFA');
    }
    // public static function getCustomersFromSage()
    // {
    //     ini_set('max_execution_time', 2400);
    //     $date = Carbon::now();
    //     $customers = DB::select("SELECT [Name]  name, GroupDescription region ,AreaDescription location,  account ,GroupDescription  category , Telephone phone_number,
    //     account customer_code,'' customer_warehouse ,[PriceListName] pricelist_code,[Credit_Limit] credit_limit ,EMail email
    //     ,'' latitude  ,'' longitude, Client_dModifiedDate, Tax_Number kra_pin,Contact_Person contact_person, CONCAT(Post1, Post2) postal_address,
	// 	'' addres, AutoDisc discounts FROM " . env('SAGE_HOST_DB_NAME') . "[_bvARAccountsFull] WHERE AreaDescription!='' AND GroupDescription!='' AND On_Hold=0 AND account NOT IN (SELECT account FROM " . env('APP_DB_NAME') . "customers)");
    //     if (!is_null($customers)) {
    //         foreach ($customers as $customer) {
    //             $query = DB::insert('insert into ' . env('APP_DB_NAME') . 'customers ([name],[region],[location],[account],[category],[phone_number],[customer_code],[customer_warehouse]
    //             ,[pricelist_code],[credit_limit],[email],[latitude],[longitude],[kra_pin],[contact_person],[postal_address],[address], [sage_modify_time], created_at, updated_at, discount) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
    //                 $customer->name, $customer->region, $customer->location,
    //                 $customer->account, $customer->category, $customer->phone_number, $customer->customer_code, $customer->customer_warehouse, $customer->pricelist_code, $customer->credit_limit,
    //                 $customer->email, $customer->latitude, $customer->longitude, $customer->kra_pin, $customer->contact_person, $customer->postal_address, $customer->address ?? '', $customer->Client_dModifiedDate, $date, $date, $customer->discount ?? 0
    //             ]);
    //             if ($query)
    //                 Log::info("Customer inserted from sage");
    //         }
    //     }
    //     $upd_customers = DB::select("SELECT [Name]  name, GroupDescription region ,AreaDescription location,  account ,GroupDescription  category , Telephone phone_number,
    //     account customer_code,'' customer_warehouse ,[PriceListName] pricelist_code,[Credit_Limit] credit_limit ,EMail email
    //     ,'' latitude  ,'' longitude, Client_dModifiedDate, Tax_Number kra_pin,Contact_Person contact_person, CONCAT(Post1, Post2) postal_address,
	// 	'' address, AutoDisc discount FROM " . env('SAGE_HOST_DB_NAME') . "[_bvARAccountsFull] b WHERE account IN (SELECT account FROM " . env('APP_DB_NAME') . "customers where sage_modify_time<>b.Client_dModifiedDate or status=0 )");
    //     if (!is_null($upd_customers)) {
    //         foreach ($upd_customers as $cust) {
    //             $customer = Customer::where('account', $cust->account)->first();
    //             if ($customer->sage_modify_time != $cust->Client_dModifiedDate) {
    //                 $query = DB::update("update " . env('APP_DB_NAME') . "customers set [region]='$cust->region',[location]='$cust->location',[account]='$cust->account',[category]='$cust->category',[customer_code]='$cust->customer_code',[customer_warehouse]='$cust->customer_warehouse'
    //                 ,[pricelist_code]='$cust->pricelist_code',[credit_limit]='$cust->credit_limit',[longitude]='$cust->longitude',[discount]->$cust->discount, status=0, [sage_modify_time]='$cust->Client_dModifiedDate', updated_at='$date' where account = ?", [$customer->account]);
    //                 if ($query > 0)
    //                     Log::info("Customer Update from sage");
    //             }
    //         }
    //     }
    // }
    public static function getCustomersFromSage()
    {
        ini_set('max_execution_time', 2400);
        $customers = DB::select("SELECT [Name]  name, GroupDescription region ,AreaDescription location,  account ,GroupDescription  category , Telephone phone_number,
        account customer_code,'' customer_warehouse ,[PriceListName] pricelist_code,[Credit_Limit] credit_limit ,EMail email
        ,'' latitude  ,'' longitude, Client_dModifiedDate, Tax_Number kra_pin,Contact_Person contact_person, CONCAT(Post1, Post2) postal_address,
        '' address, AutoDisc as discount FROM " . env('SAGE_HOST_DB_NAME') . "[_bvARAccountsFull] WHERE AreaDescription!='' AND GroupDescription!='' AND On_Hold=0");

        if (!is_null($customers)) {
            foreach ($customers as $customer) {
                $cust= Customer::updateOrCreate(
                    [
                        'account' => $customer->account,                       
                    ],
                    [
                        'name' => $customer->name,
                        'category' => $customer->category,
                        'pricelist_code' => $customer->pricelist_code,
                        'credit_limit' => $customer->credit_limit,
                        'region' => $customer->region,
                        'location' => $customer->location,
                        'phone_number' => $customer->phone_number,
                        'customer_code' => $customer->customer_code,
                        'email' => $customer->email,
                        'contact_person' => $customer->contact_person,
                        'postal_address' => $customer->postal_address,
                        'address' => $customer->address,
                        'kra_pin' => $customer->kra_pin,
                        'customer_warehouse' =>  $customer->customer_warehouse,                        
                        'discount' => $customer->discount
                        // You can set latitude and longitude here if needed
                    ]
                );
                if(!$cust->wasRecentlyCreated && $cust->wasChanged()){
                    $cust->status=0;
                    $cust->save();
                    // Log::info("Customer $cust->account updated from sage");
                }
                if($cust->wasRecentlyCreated){
                //    Log::info("Customer $cust->account inserted from sage");
                 }
                
            }
        }
    }
}
