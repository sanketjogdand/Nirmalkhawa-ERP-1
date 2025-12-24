<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CenterPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'center_id',
        'payment_date',
        'amount',
        'payment_mode',
        'company_account',
        'reference_no',
        'remarks',
        'created_by',
        'is_locked',
        'locked_by',
        'locked_at',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'locked_at' => 'datetime',
        'is_locked' => 'boolean',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}
