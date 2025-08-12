<?php

namespace App\Models;

use App\Models\InvoiceHeader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceDetails extends Model
{
    use HasFactory;
    protected $table='InvoiceDetails';
    protected $primaryKey='RowId';
    public function invoiceHeader()
    {
         return $this->belongsTo(InvoiceHeader::class, 'OrderIndex', 'OrderIndex');
    }
}
