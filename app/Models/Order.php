<?php

namespace App\Models;

use App\Models\ItemList;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $guarded=[];

    protected $table='SFAOrders';
    protected $casts = [
        'item_list' => 'array'
    ];

    public function itemLists()
    {
        return $this->hasMany(ItemList::class, 'transaction_id');
    }
}
