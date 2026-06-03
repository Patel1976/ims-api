<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    protected $fillable = [
        'reference',
        'sale_id',
        'product_id',
        'quantity',
        'return_amount',
        'return_date',
        'reason',
        'status',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class)->with('customer');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
