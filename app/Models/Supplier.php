<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'taxNumber',
        'status',
        'address',
        'opening_balance',
    ];
}
