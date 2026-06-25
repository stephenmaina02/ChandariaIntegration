<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Dashboard extends Component
{
    public $accessToken = '';
    public $newOrders = '';
    public $insertedOrders = '';
    public $customers = '';
    public $products;

    public $allowedWarehouses = [];
    public $newWarehouse = '';
    public $warehouseSaved = false;

    public function mount()
    {
        $this->accessToken = Auth::user()->createToken('authToken')->accessToken;
        $this->newOrders = Order::where('status', 0)->count();
        $this->insertedOrders = Order::where('status', 1)->count();
        $this->customers = DB::table('customers')->count();
        $this->products = DB::table('products')->count();
        $this->loadWarehouses();
    }

    public function addWarehouse()
    {
        $code = strtoupper(trim($this->newWarehouse));
        if ($code === '' || in_array($code, $this->allowedWarehouses)) {
            return;
        }
        $this->allowedWarehouses[] = $code;
        $this->newWarehouse = '';
        $this->saveWarehouses();
    }

    public function removeWarehouse($index)
    {
        array_splice($this->allowedWarehouses, $index, 1);
        $this->saveWarehouses();
    }

    public function saveWarehouses()
    {
        $settings = $this->readSettings();
        $settings['allowed_order_warehouses'] = array_values($this->allowedWarehouses);
        Storage::put('settings.json', json_encode($settings, JSON_PRETTY_PRINT));
        $this->warehouseSaved = true;
    }

    private function loadWarehouses()
    {
        $settings = $this->readSettings();
        $this->allowedWarehouses = $settings['allowed_order_warehouses'] ?? [];
    }

    private function readSettings(): array
    {
        if (!Storage::exists('settings.json')) {
            return [];
        }
        return json_decode(Storage::get('settings.json'), true) ?? [];
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
