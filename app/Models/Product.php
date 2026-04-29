<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'category',
        'brand',
        'unit',
        'purchase_price',
        'selling_price',
        'quantity',
        'alert_quantity',
        'tax',
        'description',
        'image',
    ];
}
