<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'reference',
        'customer_id',
        'store_id',
        'date',
        'subtotal',
        'tax',
        'discount',
        'grand_total',
        'paid',
        'due',
        'payment_status',
        'status',
        'note',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}
