<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CenterBalanceService
{
    public function getNetPayableTillDate(int $centerId, string $toDate): float
    {
        $due = (float) DB::table('center_settlements')
            ->whereNull('deleted_at')
            ->where('center_id', $centerId)
            ->whereDate('period_to', '<=', $toDate)
            ->sum('net_total');

        $paidRegular = $this->getPaidRegularTillDate($centerId, $toDate);

        return round($due - $paidRegular, 2);
    }

    public function getAdvanceOutstandingTillDate(int $centerId, string $toDate): float
    {
        $advances = (float) $this->paymentsQuery($centerId, $toDate)
            ->where('payment_type', 'ADVANCE')
            ->sum('amount');

        $deducted = (float) DB::table('center_settlements')
            ->whereNull('deleted_at')
            ->where('center_id', $centerId)
            ->whereDate('period_to', '<=', $toDate)
            ->sum('advance_deducted');

        return round($advances - $deducted, 2);
    }

    public function getPaidRegularTillDate(int $centerId, string $toDate): float
    {
        $paidRegular = (float) $this->paymentsQuery($centerId, $toDate)
            ->where('payment_type', 'REGULAR')
            ->sum('amount');

        return round($paidRegular, 2);
    }

    private function paymentsQuery(int $centerId, string $toDate)
    {
        $query = DB::table('center_payments')
            ->whereNull('deleted_at')
            ->where('center_id', $centerId)
            ->whereDate('payment_date', '<=', $toDate);

        if (Schema::hasColumn('center_payments', 'is_locked')) {
            $query->where('is_locked', true);
        }

        return $query;
    }
}
