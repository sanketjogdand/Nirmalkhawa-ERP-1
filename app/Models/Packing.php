<?php

namespace App\Models;

use App\Models\PackingMaterialUsage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Packing extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'date',
        'product_id',
        'total_bulk_qty',
        'remarks',
        'created_by',
        'is_locked',
        'locked_by',
        'locked_at',
    ];

    protected $casts = [
        'date' => 'date',
        'total_bulk_qty' => 'decimal:3',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PackingItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function materialUsages(): HasMany
    {
        return $this->hasMany(PackingMaterialUsage::class);
    }
}
