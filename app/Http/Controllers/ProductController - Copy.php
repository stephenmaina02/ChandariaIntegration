<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use App\Models\Product;
use App\Classes\AccessToken;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public static function pushProductToSFA()
    {
        $client = new Client();
        $acc = new AccessToken();
        $accessToken = $acc->getTokenFromSFA();

        //$product = DB::table('Products')->latest()->where('status', 0)->where('uom_list', '!=', null)->first();
        $product = DB::selectOne('select * from products where status=? and uom_list is not null', [0]);
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        if (!is_null($product)) {
            // $response = $client->request('POST', "http://127.0.0.1:8000/api/test", [
            $response = $client->request('POST', env('SFA_BASE_URL') . '/api/v1/sap/sap-products', [
                'headers' => $headers,
                'json' => [
                    'principal_code' => $product->principal_code,
                    'principal_name' => $product->principal_name,
                    'warehouse_code' => $product->warehouse_code,
                    'product_code' => $product->product_code,
                    'product_name' => $product->product_name,
                    'category' => $product->category,
                    'description' => $product->description,
                    'tax_code' => $product->tax_code,
                    'uom_list' => $product->uom_list == '' ? [] : json_decode($product->uom_list, true),
                    'status' => $product->product_status
                ]
            ]);
            if ($response->getStatusCode() == 200) {

                DB::update("update products set status=1, updated_at='". Carbon::now(). "' where product_code=?", [$product->product_code]);
                return $response->getBody()->read(1024);
            } else if ($response->getStatusCode() == 422) {

                return json_encode(['Message' => 'Some fields are missing']);
            } else {
                return json_decode($response->getBody());
            }
        }

        return json_encode(['Message' => 'No Product to send to SFA']);
    }
    public static function getProducts()
    {
        ini_set('max_execution_time', 300);
        $date = Carbon::now();
        $products = DB::select("SELECT Code, ItemActive FROM " . env('SAGE_HOST_DB_NAME') . "StkItem WHERE Code not in (SELECT product_code FROM " . env('APP_DB_NAME') . "Products) AND ItemActive=1");
        if (!is_null($products)) {
            foreach ($products as $product) {
                $item = DB::selectOne("SELECT isnull(b.DefaultSupplierCode,'') principal_code, isnull(b.DefaultSupplierName,'') principal_name,
                b.WhseCode warehouse_code, s.Code product_code, s.Description_1 product_name,ItemGroupDescription category, s.Description_1 description, s.TTI tax_code,
                ISNULL('[{\"uom_code\":\"'+s.Pack+'\",\"uom_name\":\"'+pck.Description+'\",\"uom_quantity\":\"'+CAST(pck.PackSize as nvarchar)+'\",\"length\":\"0\",\"width\":\"0\",\"height\":\"0\",\"weight\":\"0\"}]', '') uom_list,
				CASE WHEN s.ItemActive=1 THEN 'Active' ELSE 'Inactive' End as status, s.StkItem_dModifiedDate
                from " . env('SAGE_HOST_DB_NAME') . "[_bvStockAndWhseItems] b  join" . env('SAGE_HOST_DB_NAME') . "StkItem s on b.Code=s.Code
                LEFT JOIN " . env('SAGE_HOST_DB_NAME') . "PckTbl pck ON pck.Code =s.Pack
				WHERE s.ItemActive=1 and s.code= '$product->Code'");
                $query = DB::insert(
                    'insert into ' . env("APP_DB_NAME") . 'Products (principal_code, principal_name,warehouse_code, product_code,product_name, category,description,tax_code,uom_list, product_status, sage_modify_time, created_at, updated_at)
                values (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    [
                        $item->principal_code, $item->principal_name, $item->warehouse_code, $item->product_code, $item->product_name, $item->category,
                        $item->description, $item->tax_code, $item->uom_list, $item->status, $item->StkItem_dModifiedDate, $date, $date
                    ]
                );
                if ($query)
                    Log::info("Products inserted from sage");
            }
        }
        $productsToUpdate = DB::select("SELECT isnull(DefaultSupplierCode,'') principal_code,s.StkItem_dModifiedDate, isnull(DefaultSupplierName,'') principal_name, WhseCode warehouse_code,V.[Code] product_code
        ,V.[Description_1] product_name,V.ItemGroup category ,ItemGroupDescription description ,V.TTI tax_code,UOMStockingUnitCode uom_list,Case when V.ItemActive=1 then 'Active' else  'Inactive'
        End as status,(SELECT  TOP 1 [cBranchCode] FROM " . env('SAGE_HOST_DB_NAME') . "[_etblBranch] where  idbranch=WhseStk_iBranchID) Branch FROM " . env('SAGE_HOST_DB_NAME') . "[_bvStockAndWhseItems] v inner join " . env('SAGE_HOST_DB_NAME') . "StkItem s on v.Code=s.Code LEFT JOIN
       " . env('SAGE_HOST_DB_NAME') . "WhseStk  W ON V.WhseLink=W.WHWhseID AND V.StockLink=W.WHStockLink  where v.Code in (SELECT product_code FROM " . env('APP_DB_NAME') . "Products)");
        if (!is_null($productsToUpdate)) {
            foreach ($productsToUpdate as $prod) {
                $prods = Product::where('product_code', $prod->product_code)->first();
                if (!is_null($prods)) {
                    if ($prod->StkItem_dModifiedDate != $prods->sage_modify_time) {
                        $query2 = DB::update("update " . env('APP_DB_NAME') . "Products set principal_code='$prod->principal_code',principal_name='$prod->principal_name',
                                warehouse_code='$prod->warehouse_code', product_name='$prod->product_name',
                                category='$prod->category', description='$prod->description',tax_code='$prod->tax_code',uom_list='$prod->uom_list',
                                product_status='$prod->status',status=0,updated_at='$date', sage_modify_time = '$prod->StkItem_dModifiedDate' where product_code = '$prod->product_code'");
                        if ($query2 > 0)
                            Log::info("Products updated from sage");
                    }
                }
            }
        }
    }
}
