<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'customer_code',
        'mobile',
        'gstin',
        'state_id',
        'district_id',
        'taluka_id',
        'village_id',
        'address_line',
        'pincode',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function taluka(): BelongsTo
    {
        return $this->belongsTo(Taluka::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    /**
     * Placeholder for future sales invoices relation.
     */
    public function salesInvoices(): HasMany
    {
        $related = class_exists('App\\Models\\SalesInvoice') ? 'App\\Models\\SalesInvoice' : self::class;

        return $this->hasMany($related);
    }

    /**
     * Placeholder for future receipts relation.
     */
    public function receipts(): HasMany
    {
        $related = class_exists('App\\Models\\Receipt') ? 'App\\Models\\Receipt' : self::class;

        return $this->hasMany($related);
    }

    public function dispatchLines(): HasMany
    {
        return $this->hasMany(DispatchLine::class);
    }
}
