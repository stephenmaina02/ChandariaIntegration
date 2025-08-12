<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Delivery as DeliveryModel;

class Delivery extends Component
{
    public $deliveries=[];
    public $queryDelId;
    public $queryInvNum, $queryVehicle, $queryDelStatus, $queryDelDate, $notes, $status;

    public function mount()
    {
        $this->deliveries=DeliveryModel::orderBy('created_at', 'desc')->get();
    }
    public function updated()
    {
        if($this->queryDelId=="" && $this->queryInvNum=="" && $this->queryVehicle=="" && $this->queryDelStatus==""
            && $this->queryDelDate=="" && $this->notes=="" && $this->status==""){
            $this->deliveries=DeliveryModel::orderBy('created_at', 'desc')->get();
            }
        else
        {
            $this->deliveries=DeliveryModel::where('delivery_id','LIKE', '%'.$this->queryDelId.'%')
                                            ->where('invoice_number','LIKE', '%'.$this->queryInvNum.'%')
                                            ->where('vehicle_details','LIKE', '%'.$this->queryVehicle.'%')
                                            ->where('delivery_status','LIKE', '%'.$this->queryDelStatus.'%')
                                            ->where('delivery_date','LIKE', '%'.$this->queryDelDate.'%')
                                            ->where('notes','LIKE', '%'.$this->notes.'%')
                                            ->where('status','LIKE', '%'.$this->status.'%')

                                            ->orderBy('created_at', 'desc')
                                            ->get();

        }
    }
    public function render()
    {
        return view('livewire.delivery');
    }
}
