<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Nette\Utils\Json;
use GuzzleHttp\Client;
use App\Models\Promotion;
use App\Classes\AccessToken;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromotionController extends Controller
{
    public function getPromotionToStaging()
    {
        ini_set('max_execution_time', 600);
        $promotions = DB::select('select * from promotion_header where name is not null');
        $unique_headers = [];
        foreach ($promotions as $promo) {
            if (!in_array($promo->name, $unique_headers)) {
                array_push($unique_headers, $promo->name);
            }
        }
        foreach ($unique_headers as $header) {

            $items = [];
            $details = DB::select("select * from promotion_details where name = '$header'");
            foreach ($details as $detail) {
                $items[] = ['item_code' => $detail->code, 'quantity' => $detail->quantity, 'type' => $detail->type, 'price' => $detail->price, 'start_date' => $detail->start_date, 'expiry_date' => $detail->expiry_date];
            }
            $customers = [];
            $customer_categories = [];
            $cms = DB::select("select * from promotion_header where name = '$header'");
            foreach ($cms as $customer) {
                if (strtolower($customer->customer_type) == 'customer')
                    $customers[] = ['customer_code' => $customer->Account];
                else
                    $customer_categories[] = ['category_code' => $customer->Account, 'category_name' => $customer->Account];
            }
            $existPromo = DB::selectOne("select * from promotion_header where name= '$header'");
            $prom = Promotion::updateOrCreate(['name' => $header, 'status' => $existPromo->status, 'start_date'=>$items[0]['start_date'], 'expiry_date'=>$items[0]['expiry_date'],'type'=>$items[0]['type']], ['items' => json_encode($items) , 'customers' => json_encode($customers), 'customer_categories' => json_encode($customer_categories), 'description' => $header,  'record_time' => Carbon::now()]);

            if (!$prom->wasRecentlyCreated && $prom->wasChanged()) {
                Promotion::where('name', $header)->first()->update(['sync_status' => 0]);
            }

            if ($prom->wasRecentlyCreated) {
                Promotion::where('name', $header)->first()->update(['sync_status' => 0]);
            }
        }
    }

    public function postPromotionToSFA()
    {
        ini_set('max_execution_time', 1200);
        //$this->getPromotionToStaging();
        $promotions = Promotion::where('sync_status', 0)->where('name','<>', '')->where('status', 'Active')->take(20)->get();
        if (!is_null($promotions)) {
            try {
                $client = new Client(['verify' => false]);
                $acc = new AccessToken();
                $accessToken = $acc->getTokenFromSFA();

                $headers = [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ];
                foreach ($promotions as $promotion) {
                    // $decoded_fst_item = json_decode($promotion->items)[0];
                    $response = $client->request('POST', env('SFA_BASE_URL') . '/api/v1/erp/promotions', [
                        'headers' => $headers,
                        'json' => [
                            'name' => $promotion->name,
                            'type' => $promotion->type,
                            'start_date' => $promotion->start_date,
                            'expiry_date' => $promotion->expiry_date,
                            'description' => $promotion->description,
                            'status' => $promotion->status,
                            'items' => json_decode($promotion->items, true),
                            'customer' => json_decode($promotion->customers, true),
                            'customer_categories' => json_decode($promotion->customer_categories, true)
                        ]
                    ]);

                    $promo_update = Promotion::where('name', $promotion->name)->first();
                    if ($response->getStatusCode() == 200) {
                        $promo_update->sync_status = 1;
                        $promo_update->updated_at = Carbon::now();
                        $promo_update->save();
                        // Log::info('Promotion ' . $promotion->name . ' posted to SFA');
                    } else {
                        $promo_update->sync_status = 2;
                        $promo_update->updated_at = Carbon::now();
                        $promo_update->save();
                        Log::error($response->getBody());
                    }
                }
            } catch (Exception $ex) {
                Log::error('Promotion error: ' . $ex->getMessage());
            }
            //log error in file or db table
        }

        // Log::info('No promotion to send to SFA');
    }
}
