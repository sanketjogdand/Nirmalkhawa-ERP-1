<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\State;
use App\Models\Supplier;

class GeneralExpenseLine extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'general_expense_id',
        'category_id',
        'description',
        'qty',
        'rate',
        'taxable_amount',
        'gst_rate',
        'gst_amount',
        'total_amount',
        'place_of_supply_state_id',
        'is_rcm_applicable',
        'vendor_id',
        'vendor_name',
        'vendor_invoice_no',
        'vendor_invoice_date',
        'vendor_gstin',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'rate' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'gst_rate' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_rcm_applicable' => 'boolean',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(GeneralExpense::class, 'general_expense_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function placeOfSupplyState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'place_of_supply_state_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }
}
