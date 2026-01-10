<?php

namespace App\Services;

use Carbon\Carbon;
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

    public function getPackOnHandAsOf(int $productId, int $packSizeId, $asOf): int
    {
        $asOfDate = $asOf ? Carbon::parse($asOf)->toDateString() : now()->toDateString();

        return (int) DB::table('pack_operations_view')
            ->where('product_id', $productId)
            ->where('pack_size_id', $packSizeId)
            ->whereDate('txn_date', '<=', $asOfDate)
            ->selectRaw('COALESCE(SUM(pack_count_in - pack_count_out), 0) as balance')
            ->value('balance');
    }
}
