<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionV1Controller extends Controller
{
    public function getCustomerPrices(Request $request)
    {
        // Get pagination inputs
        $perPage = (int) $request->get('per_page', 20000);
        $page    = (int) $request->get('page', 1);
        $offset  = ($page - 1) * $perPage;
        $sage_db =  env('SAGE_HOST_DB_NAME');

        // Count total records (for pagination metadata)
        $countSql = "
        SELECT COUNT(*) as total
        FROM " . $sage_db . "_etblVDAR e
        JOIN " . $sage_db . "Client c ON c.DCLink = e.iARAPID
        JOIN " . $sage_db . "_etblVDLnAR er ON e.IDVD = er.iVDID
        JOIN " . $sage_db . "_etblVDLnLvlAR erl ON erl.iVDLnID = er.IDVDLn
        JOIN " . $sage_db . "StkItem s ON s.StockLink = er.iStockID
        WHERE er.dExpDate > GETDATE()
          AND er.dEffDate <= GETDATE()
          AND er.On_Hold = 0
    ";
        $total = DB::selectOne($countSql)->total;

        // Fetch paginated records
        $sql = "
        SELECT 
            c.Account AS customer_code, 
            s.Code AS item_code, 
            s.Pack AS uom_code, 
            erl.fQuantity AS quantity, 
            CAST(erl.fPriceDisc AS DECIMAL(12,2)) AS price, 
            CAST(er.dEffDate AS DATE) AS start_date, 
            CAST(er.dExpDate AS DATE) AS expiry_date
        FROM " . $sage_db . "_etblVDAR e
        JOIN " . $sage_db . "Client c ON c.DCLink = e.iARAPID
        JOIN " . $sage_db . "_etblVDLnAR er ON e.IDVD = er.iVDID
        JOIN " . $sage_db . "_etblVDLnLvlAR erl ON erl.iVDLnID = er.IDVDLn
        JOIN " . $sage_db . "StkItem s ON s.StockLink = er.iStockID
        WHERE er.dExpDate > GETDATE()
          AND er.dEffDate <= GETDATE()
          AND er.On_Hold = 0
        ORDER BY er.dEffDate DESC
        OFFSET :offset ROWS FETCH NEXT :perPage ROWS ONLY
    ";

        $results = DB::select($sql, [
            'offset'  => $offset,
            'perPage' => $perPage,
        ]);

        // Calculate pagination meta
        $lastPage = (int) ceil($total / $perPage);

        return response()->json([
            'organization_code' => 'ZCP1',
            'client_code'       => 'DEMO',
            'customer_prices'   => $results,
            'pagination' => [
                'current_page'   => $page,
                'per_page'       => $perPage,
                'total'          => $total,
                'last_page'      => $lastPage,
                'next_page_url'  => $page < $lastPage ? url()->current() . '?page=' . ($page + 1) . '&per_page=' . $perPage : null,
                'prev_page_url'  => $page > 1 ? url()->current() . '?page=' . ($page - 1) . '&per_page=' . $perPage : null,
                'first_page_url' => url()->current() . '?page=1&per_page=' . $perPage,
                'last_page_url'  => url()->current() . '?page=' . $lastPage . '&per_page=' . $perPage,
            ]
        ]);
    }
}
