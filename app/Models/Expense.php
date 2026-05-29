<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'reference',
        'expense_category_id',
        'store_id',
        'date',
        'amount',
        'payment_method',
        'note',
        'attachment',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
