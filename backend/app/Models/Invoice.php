<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Customer;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id','invoicenumber','invoicedate','duedate','sentdate','sent','paymentterms'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
