<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'packing_id',
        'pack_size_id',
        'pack_count',
        'pack_qty_snapshot',
        'pack_uom',
    ];

    protected $casts = [
        'pack_count' => 'integer',
        'pack_qty_snapshot' => 'decimal:3',
    ];

    public function packing(): BelongsTo
    {
        return $this->belongsTo(Packing::class);
    }

    public function packSize(): BelongsTo
    {
        return $this->belongsTo(PackSize::class);
    }
}
