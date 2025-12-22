<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'supplier_code',
        'contact_person',
        'mobile',
        'email',
        'gstin',
        'state_id',
        'district_id',
        'taluka_id',
        'village_id',
        'address_line',
        'pincode',
        'account_name',
        'account_number',
        'ifsc',
        'bank_name',
        'branch',
        'upi_id',
        'is_active',
        'notes',
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
     * Placeholder for future dispatch delivery expenses relation.
     */
    public function dispatchDeliveryExpenses(): HasMany
    {
        $related = class_exists('App\\Models\\DispatchDeliveryExpense') ? 'App\\Models\\DispatchDeliveryExpense' : self::class;

        return $this->hasMany($related);
    }

    /**
     * Placeholder for future supplier payments relation.
     */
    public function supplierPayments(): HasMany
    {
        $related = class_exists('App\\Models\\SupplierPayment') ? 'App\\Models\\SupplierPayment' : self::class;

        return $this->hasMany($related);
    }
}
