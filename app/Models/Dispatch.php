<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dispatch extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_POSTED = 'POSTED';

    public const DELIVERY_SELF = 'SELF_PICKUP';
    public const DELIVERY_COMPANY = 'COMPANY_DELIVERY';

    protected $fillable = [
        'dispatch_no',
        'dispatch_date',
        'delivery_mode',
        'vehicle_no',
        'driver_name',
        'remarks',
        'status',
        'is_locked',
        'locked_by',
        'locked_at',
        'created_by',
    ];

    protected $casts = [
        'dispatch_date' => 'date',
        'locked_at' => 'datetime',
        'is_locked' => 'boolean',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(DispatchLine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }
}
