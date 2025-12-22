<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceLine extends Model
{
    use HasFactory;

    public const MODE_BULK = 'BULK';
    public const MODE_PACK = 'PACK';

    protected $fillable = [
        'sales_invoice_id',
        'product_id',
        'sale_mode',
        'rate_per_kg',
        'gst_rate_percent',
        'dispatch_id',
        'dispatch_line_id',
        'qty_bulk',
        'uom',
        'pack_size_id',
        'pack_count',
        'computed_total_qty',
        'pack_qty_snapshot',
        'pack_uom',
        'taxable_amount',
        'gst_amount',
        'line_total',
    ];

    protected $casts = [
        'rate_per_kg' => 'decimal:3',
        'gst_rate_percent' => 'decimal:2',
        'qty_bulk' => 'decimal:3',
        'pack_count' => 'integer',
        'computed_total_qty' => 'decimal:3',
        'pack_qty_snapshot' => 'decimal:3',
        'taxable_amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function packSize(): BelongsTo
    {
        return $this->belongsTo(PackSize::class);
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class);
    }

    public function dispatchLine(): BelongsTo
    {
        return $this->belongsTo(DispatchLine::class);
    }
}
