<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Invoice;
use App\Models\Subscription;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'adres', 'subscriptions'];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function subscriptions()
    {
        return $this->belongsToMany(Subscription::class, 'customer_subscription');
    }
}
