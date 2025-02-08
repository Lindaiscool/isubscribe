<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Customer;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'vat'];

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_subscription');
    }
}
