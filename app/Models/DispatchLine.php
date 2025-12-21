<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DispatchLine extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const MODE_BULK = 'BULK';
    public const MODE_PACK = 'PACK';

    protected $fillable = [
        'dispatch_id',
        'customer_id',
        'invoice_id',
        'product_id',
        'sale_mode',
        'qty_bulk',
        'uom',
        'pack_size_id',
        'pack_count',
        'computed_total_qty',
        'pack_qty_snapshot',
        'pack_uom',
    ];

    protected $casts = [
        'qty_bulk' => 'decimal:3',
        'pack_count' => 'integer',
        'computed_total_qty' => 'decimal:3',
        'pack_qty_snapshot' => 'decimal:3',
    ];

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function packSize(): BelongsTo
    {
        return $this->belongsTo(PackSize::class);
    }

    public function invoice(): BelongsTo
    {
        $related = class_exists('App\\Models\\SalesInvoice') ? 'App\\Models\\SalesInvoice' : self::class;

        return $this->belongsTo($related, 'invoice_id');
    }
}
