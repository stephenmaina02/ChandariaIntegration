<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\InvoiceHeader;
use App\Models\ItemList;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public $accessToken="";
    public $newOrders="";
    public $insertedOrders="";
    public $customers="";
    public $products;

    // public function generateAccessToken()
    // {
    //     $this->accessToken =Auth::user()->createToken('authToken')->accessToken;
    // }
    public function mount(){
        $this->accessToken =Auth::user()->createToken('authToken')->accessToken;
        $this->newOrders=Order::where('status', 0)->get()->count();
        $this->insertedOrders=Order::where('status', 1)->get()->count();
        $this->customers=DB::table('customers')->count();
        $this->products=DB::table('products')->count();

    }
    public function render()
    {
        return view('livewire.dashboard');
    }
}
