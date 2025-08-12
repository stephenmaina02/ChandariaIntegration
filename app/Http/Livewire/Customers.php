<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class Customers extends Component
{
    use WithPagination;
    // public $customers=[];
    public $queryCode, $queryName, $queryRegion, $queryCat, $queryCredit, $queryPricel, $status;

    // public function mount(){
    //    $this->customers=Customer::orderBy('created_at', 'desc')->get();
    // }
    // public function updated()
    // {
    //     if($this->queryCode=="" && $this->queryName=="" && $this->queryRegion=="" && $this->queryCat=="" && $this->queryCredit=="" && $this->queryPricel=="" && $this->status=="")
    //     $this->customers=Customer::orderBy('created_at', 'desc')->get();
    //     else
    //     {
    //     $this->customers=Customer::where('customer_code', 'LIKE','%'.$this->queryCode.'%')
    //                             ->where('name','LIKE', '%'.$this->queryName.'%')
    //                             ->where('region','LIKE', '%'.$this->queryRegion.'%')
    //                             ->where('category','LIKE', '%'.$this->queryCat.'%')
    //                             ->where('credit_limit','LIKE', '%'.$this->queryCredit.'%')
    //                             ->where('pricelist_code','LIKE', '%'.$this->queryPricel.'%')
    //                             ->where('status','LIKE', '%'.$this->status.'%')
    //                             ->orderBy('created_at', 'desc')
    //                             ->limit(10)
    //                             ->get();
    //     }
    // }
    public function render()
    {
        $customers= Customer::orderBy('created_at', 'desc')->paginate(200);
        return view('livewire.customers', compact('customers'));
    }
}
