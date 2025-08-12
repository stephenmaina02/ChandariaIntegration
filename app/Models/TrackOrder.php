<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackOrder extends Model
{

    protected $guarded=[];

    protected $table='BranchOrderTracking';
    use HasFactory;

}
