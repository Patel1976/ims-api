<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'reference',
        'supplier_id',
        'store_id',
        'date',
        'subtotal',
        'tax',
        'shipping',
        'grand_total',
        'paid',
        'due',
        'payment_status',
        'status',
        'note',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
