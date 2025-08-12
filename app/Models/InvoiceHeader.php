<?php

namespace App\Models;

use App\Models\InvoiceDetails;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceHeader extends Model
{
    use HasFactory;
    protected $table='InvoiceHeader';
    protected $primaryKey='OrderIndex';

    public function invoiceDetails()
    {
         return $this->hasMany(InvoiceDetails::class, 'OrderIndex');
    }
}
