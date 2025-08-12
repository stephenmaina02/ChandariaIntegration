<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\TrackOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BranchTrackingController extends Controller
{
    public function trackBranchDelivery(Delivery $delivery)
    {

        $incomingData = [
            'delivery_id' => request()->input('delivery_id'),
            'invoice_number'=> request()->input('invoice_number'),
            //'delivery_date'=> Carbon::parse(request()->input('delivery_date'))->format('d-m-Y'),
            'delivery_date'=> request()->input('delivery_date'),
            'vehicle_details'=> request()->input('vehicle_details'),
            'driver_details' => request()->input('driver_details'),
            'delivery_status'=> request()->input('delivery_status'),
            'notes'=> request()->input('notes'),
            'item_list' => request()->input('item_list'),
            'region' => request()->route('branch')
        ];

        // dd(request()->all());

        $delivery->create($incomingData);
        //return $response->json();
        return json_encode(['Message'=>'Delivery has been posted to SFA & updated to HQ']);
    }

    public function trackBranchOrder(TrackOrder $trackOrder)
    {

        $processedOrder = [
            'transaction_id' => request()->input('transaction_id'),
            'doc_num'=> request()->input('doc_num'),
            'item_list' => request()->input('item_list'),
            'date'=> request()->input('date'),
            'status'=> request()->input('status'),
            'branch'=> request()->route('branch')
        ];

        
        $trackOrder->create($processedOrder);
        return json_encode(['Message'=>'Order status has been posted to SFA & updated to HQ']);
    }
}
