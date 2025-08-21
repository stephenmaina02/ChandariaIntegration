<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionV1Controller extends Controller
{
    public function getCustomerPrices(Request $request)
    {
        // Default per page (can override with ?per_page=xxx)
        $perPage = $request->get('per_page', 20000);
        $sage_db =  env('SAGE_HOST_DB_NAME');

        // Build query
        $query = DB::table($sage_db.'_etblVDAR as e')
            ->join($sage_db.'Client as c', 'c.DCLink', '=', 'e.iARAPID')
            ->join($sage_db.'_etblVDLnAR as er', 'e.IDVD', '=', 'er.iVDID')
            ->join($sage_db.'_etblVDLnLvlAR as erl', 'erl.iVDLnID', '=', 'er.IDVDLn')
            ->join($sage_db.'StkItem as s', 's.StockLink', '=', 'er.iStockID')
            ->select(
                'c.Account as customer_code',
                's.Code as item_code',
                's.Pack as uom_code',
                'erl.fQuantity as quantity',
                DB::raw('CAST(erl.fPriceDisc as DECIMAL(12,2)) as price'),
                DB::raw('CAST(er.dEffDate as DATE) as start_date'),
                DB::raw('CAST(er.dExpDate as DATE) as expiry_date')
            )
            ->where('er.On_Hold', '=', 0)
            ->whereRaw('er.dExpDate > GETDATE()')
            ->whereRaw('er.dEffDate <= GETDATE()');

        // Paginate results
        $customerPrices = $query->paginate($perPage);

        // Return JSON
        return response()->json([
            'organization_code' => 'CHANDARIA',
            'client_code'       => 'CHANDARIA',
            'customer_prices'   => $customerPrices->items(),
            'pagination' => [
                'current_page'   => $customerPrices->currentPage(),
                'per_page'       => $customerPrices->perPage(),
                'total'          => $customerPrices->total(),
                'last_page'      => $customerPrices->lastPage(),
                'next_page_url'  => $customerPrices->nextPageUrl(),
                'prev_page_url'  => $customerPrices->previousPageUrl(),
                'first_page_url' => $customerPrices->url(1),
                'last_page_url'  => $customerPrices->url($customerPrices->lastPage()),
            ]
        ]);
    }
}
