<?php

namespace App\Models;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemList extends Model
{
    use HasFactory;

    protected $guarded=[];
    protected $table='item_lists';
    protected $primary_key='Row_id';

    public function order()
    {
        return $this->belongsTo(Order::class, 'transaction_id');
    }
}
