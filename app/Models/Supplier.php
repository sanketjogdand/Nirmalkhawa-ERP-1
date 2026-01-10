<?php

namespace App\Models;

use App\Models\DispatchDeliveryExpense;
use App\Models\GeneralExpense;
use App\Models\GeneralExpensePayment;
use App\Models\Purchase;
use App\Models\SupplierPayment;
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

    public function dispatchDeliveryExpenses(): HasMany
    {
        return $this->hasMany(DispatchDeliveryExpense::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function generalExpenses(): HasMany
    {
        return $this->hasMany(GeneralExpense::class);
    }

    public function generalExpensePayments(): HasMany
    {
        return $this->hasMany(GeneralExpensePayment::class);
    }

    /**
     * Placeholder for future supplier payments relation.
     */
    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }
}
