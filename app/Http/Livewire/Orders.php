<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use App\Models\Order;
use Livewire\Component;
use App\Models\ItemList;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\OrderController;

class Orders extends Component
{
    public $orders=[];
    public $invoiceDetailsModal=false;
    public $invoicedetails=[];
    public $queryID, $queryRef, $queryDate,$queryCust, $queryBra, $querySta;


    public function mount(){
        $this->orders=Order::where('transaction_date','>=', date('Y-m-01', strtotime(Carbon::now())))->where('transaction_date', '<=', date('Y-m-t', strtotime(Carbon::now())))->orderBy('transaction_date', 'desc')->get();
    }
    public function invoiceDetails($OrderIndex)
    {
        $this->invoiceDetailsModal=true;
        $this->invoicedetails=ItemList::where('transaction_id', $OrderIndex)->get();
    }
    public function updated()
    {
        if($this->queryRef=="" && $this->queryID=="" && $this->queryDate=="" && $this->queryCust && $this->queryBra=="" && $this->querySta=="")
        $this->orders=Order::orderBy('transaction_date', 'desc')->get();
        else
        {
        $this->orders=Order::where('transaction_id', 'LIKE',"%{$this->queryID}%")
                                    ->where('transaction_type','LIKE', '%'.$this->queryRef.'%')
                                    ->where('customer_code','LIKE', '%'.$this->queryCust.'%')
                                    ->where('region','LIKE', '%'.$this->queryBra.'%')
                                    ->where('transaction_date','LIKE', '%'.$this->queryDate.'%')
                                    ->where('status','LIKE', '%'.$this->querySta.'%')
                                    ->orderBy('transaction_date', 'desc')
                                    ->get();
        }
    }
    public function deleteOrder($transaction_id)
    {
        $order=Order::where('transaction_id', $transaction_id)->first();
        if(!is_null($order)){
            DB::delete('delete item_lists where transaction_id = ?', [$transaction_id]);
            $order->delete();
        }
        $this->mount();
    }
    public function insertOrder($transaction_id)
    {
        $order=Order::where('transaction_id', $transaction_id)->first();
        $ord=new OrderController();
        $status=$ord->postToSage($order);
        if($status){
            $order->status = 1;
            $order->updated_at=Carbon::now();
            $order->save();
        }
    }
    public function render()
    {
        return view('livewire.orders');
    }
}
