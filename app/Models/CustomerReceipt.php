<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerReceipt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'receipt_date',
        'amount',
        'payment_mode',
        'company_account',
        'reference_no',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
