<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DispatchDeliveryExpense extends Model
{
    use SoftDeletes;

    public const EXPENSE_TYPES = [
        'FUEL',
        'DRIVER',
        'VEHICLE_RENT',
        'TOLL',
        'LOADING_UNLOADING',
        'PACKING',
        'OTHER',
    ];

    protected $fillable = [
        'dispatch_id',
        'supplier_id',
        'expense_date',
        'expense_type',
        'amount',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
    ];

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
