<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transaction_date',
        'type',
        'other_type',
        'reference',
        'from_party_type',
        'to_party_type',
        'from_party',
        'to_party',
        'payment_mode',
        'debit_credit',
        'gst_type',
        'amount',
        'gst_percent',
        'gst_amount',
        'total_amount',
        'paid_amount',
        'gstin',
        'description',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'gst_percent' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];
}
