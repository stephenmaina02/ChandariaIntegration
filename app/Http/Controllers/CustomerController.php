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
        $customers = Customer::where('status', 0)->orderBy('updated_at')->take(25)->get();

        if ($customers->isEmpty()) {
            Log::info('No customer to send to SFA');
            return;
        }

        $client = self::sfaClient();
        $headers = self::sfaHeaders();

        foreach ($customers as $customer) {
            if (!self::sendCustomerToSfa($customer, $client, $headers)) {
                continue;
            }

            $now = Carbon::now();
            $customer->status = 1;
            $customer->updated_at = $now;

            // This payload already carries discount_rate, so it settles any
            // pending discount change too and keeps it off the discount queue.
            if ($customer->discount_status == 0) {
                $customer->discount_status = 1;
                $customer->discount_synced_at = $now;
            }

            $customer->save();
            Log::info('Customer ' . $customer->account . ' posted to SFA');
        }
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

    /**
     * Compare every local customer's discount against Sage and flag the ones
     * that drifted, so they can be pushed to SFA on their own track instead of
     * queueing behind the general customer sync.
     */
    public static function syncDiscountsFromSage($account = null)
    {
        ini_set('max_execution_time', 2400);

        $sql = "SELECT account, AutoDisc AS discount FROM " . env('SAGE_HOST_DB_NAME') . "[_bvARAccountsFull]
                WHERE AreaDescription!='' AND GroupDescription!='' AND On_Hold=0";
        $bindings = [];

        if (!is_null($account)) {
            $sql .= " AND account = ?";
            $bindings[] = $account;
        }

        $sageDiscounts = [];
        foreach (DB::select($sql, $bindings) as $row) {
            $sageDiscounts[$row->account] = (float) $row->discount;
        }

        // A dropped Sage connection would otherwise look like "every discount is now 0".
        if (empty($sageDiscounts)) {
            Log::warning('Discount sync: Sage returned no customers, skipping run');
            return 0;
        }

        $changed = 0;
        $now = Carbon::now();

        Customer::select('id', 'account', 'discount')
            ->when(!is_null($account), function ($query) use ($account) {
                return $query->where('account', $account);
            })
            ->chunkById(500, function ($customers) use ($sageDiscounts, $now, &$changed) {
                foreach ($customers as $customer) {
                    if (!array_key_exists($customer->account, $sageDiscounts)) {
                        continue;
                    }

                    $sageDiscount = $sageDiscounts[$customer->account];

                    if (abs((float) $customer->discount - $sageDiscount) < 0.0001) {
                        continue;
                    }

                    Log::info("Discount changed for customer {$customer->account}: {$customer->discount} -> {$sageDiscount}");

                    Customer::where('id', $customer->id)->update([
                        'discount' => $sageDiscount,
                        'discount_status' => 0,
                        'discount_changed_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $changed++;
                }
            });

        return $changed;
    }

    public static function pushDiscountUpdatesToSfa($limit = 100)
    {
        // status=1 only: a customer still queued in pushToSfa will have its
        // discount carried by that push, so sending it here as well is a duplicate.
        $customers = Customer::where('discount_status', 0)
            ->where('status', 1)
            ->orderBy('discount_changed_at')
            ->take($limit)
            ->get();

        if ($customers->isEmpty()) {
            return 0;
        }

        $client = self::sfaClient();
        $headers = self::sfaHeaders();
        $pushed = 0;

        foreach ($customers as $customer) {
            if (!self::sendCustomerToSfa($customer, $client, $headers)) {
                continue;
            }

            Customer::where('id', $customer->id)->update([
                'discount_status' => 1,
                'discount_synced_at' => Carbon::now(),
            ]);

            Log::info('Discount ' . $customer->discount . ' for customer ' . $customer->account . ' pushed to SFA');
            $pushed++;
        }

        return $pushed;
    }

    protected static function sfaClient()
    {
        // http_errors off so a single rejected customer cannot abort the whole batch.
        return new Client(['verify' => false, 'http_errors' => false]);
    }

    protected static function sfaHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . AccessToken::getTokenFromSFA(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    protected static function sendCustomerToSfa($customer, Client $client, array $headers)
    {
        try {
            $response = $client->request('POST', env('SFA_BASE_URL') . '/api/v1/sap/sap-customers', [
                'headers' => $headers,
                'json' => self::sfaCustomerPayload($customer),
            ]);

            if ($response->getStatusCode() == 200) {
                return true;
            }

            Log::error('SFA rejected customer ' . $customer->account . ' [' . $response->getStatusCode() . ']: ' . (string) $response->getBody());
        } catch (\Throwable $e) {
            Log::error('SFA push failed for customer ' . $customer->account . ': ' . $e->getMessage());
        }

        return false;
    }

    protected static function sfaCustomerPayload($customer)
    {
        return [
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
            'discount_rate' => $customer->discount,
        ];
    }
}
