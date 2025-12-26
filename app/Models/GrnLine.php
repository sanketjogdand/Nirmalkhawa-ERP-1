<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GrnLine extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'grn_id',
        'product_id',
        'received_qty',
        'uom',
        'remarks',
    ];

    protected $casts = [
        'received_qty' => 'decimal:3',
    ];

    public function grn(): BelongsTo
    {
        return $this->belongsTo(Grn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
