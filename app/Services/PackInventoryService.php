<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PackInventoryService
{
    public function getPackOnHand(int $productId, int $packSizeId): int
    {
        return (int) DB::table('pack_operations_view')
            ->where('product_id', $productId)
            ->where('pack_size_id', $packSizeId)
            ->selectRaw('COALESCE(SUM(pack_count_in - pack_count_out), 0) as balance')
            ->value('balance');
    }
}
