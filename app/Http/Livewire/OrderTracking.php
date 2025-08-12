<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\TrackOrder;
use App\Models\OrderTracking as ModelsOrderTracking;

class OrderTracking extends Component
{
    public $orderstatus=[];
    public $queryTranID, $queryDocNum, $queryDate, $status;

    public function mount()
    {
       $this->orderstatus=ModelsOrderTracking::where('created_at','>=', date('Y-m-01', strtotime(Carbon::now())))->where('created_at','<=', date('Y-m-t', strtotime(Carbon::now())))->orderBy('created_at', 'desc')->get();
    }
    public function updated()
    {
       if($this->queryTranID=="" && $this->queryDocNum=="" && $this->queryDate=="" && $this->status==""){
        $this->orderstatus=ModelsOrderTracking::orderBy('created_at', 'desc')->get();
        }
        else{
        $this->orderstatus=ModelsOrderTracking::where('transaction_id','LIKE', '%'.$this->queryTranID.'%')
                                        ->where('doc_num','LIKE', '%'.$this->queryDocNum.'%')
                                        ->where('date','LIKE', '%'.$this->queryDate.'%')
                                        ->where('status','LIKE', '%'.$this->status.'%')
                                        ->orderBy('created_at','desc')
                                        ->get();

        }
    }
    public function render()
    {
        return view('livewire.order-tracking');
    }
}
