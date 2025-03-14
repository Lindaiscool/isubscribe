<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Customer;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'vat', 'start_date', 'end_date'];

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_subscription');
    }

    // In Subscription model
public function getBasePrice()
{
    return $this->price / (1 + $this->vat / 100);
}

public function getVatAmount()
{
    return $this->price - $this->getBasePrice();
}


}
