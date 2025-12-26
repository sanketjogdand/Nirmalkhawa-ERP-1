<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialConsumptionLine extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'material_consumption_id',
        'product_id',
        'qty',
        'uom',
        'remarks',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
    ];

    public function consumption(): BelongsTo
    {
        return $this->belongsTo(MaterialConsumption::class, 'material_consumption_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
