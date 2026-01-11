<?php

namespace App\Livewire\Reports\EmployeesCenters;

use App\Livewire\Reports\BaseReport;
use Illuminate\Support\Facades\DB;

class CenterSettlementReport extends BaseReport
{
    public string $title = 'Center Settlement Report';

    protected function columns(): array
    {
        return [
            'center' => 'Center',
            'milk_amount' => 'Milk Amount',
            'paid' => 'Paid',
            'balance' => 'Balance',
        ];
    }

    protected function applyFilters($query)
    {
        return $query;
    }

    protected function baseQuery()
    {
        $settlements = DB::table('center_settlements as cs')
            ->whereNull('cs.deleted_at')
            ->when($this->fromDate, fn ($q) => $q->whereDate('cs.period_from', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('cs.period_to', '<=', $this->toDate))
            ->selectRaw('cs.center_id as center_id, SUM(cs.net_total) as milk_amount')
            ->groupBy('cs.center_id');

        $payments = DB::table('center_payments as cp')
            ->whereNull('cp.deleted_at')
            ->when($this->fromDate, fn ($q) => $q->whereDate('cp.payment_date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('cp.payment_date', '<=', $this->toDate))
            ->selectRaw('cp.center_id as center_id, SUM(cp.amount) as paid')
            ->groupBy('cp.center_id');

        return DB::table('centers as c')
            ->leftJoinSub($settlements, 'settlements', 'settlements.center_id', '=', 'c.id')
            ->leftJoinSub($payments, 'payments', 'payments.center_id', '=', 'c.id')
            ->selectRaw('c.name as center')
            ->selectRaw('ROUND(COALESCE(settlements.milk_amount, 0), 2) as milk_amount')
            ->selectRaw('ROUND(COALESCE(payments.paid, 0), 2) as paid')
            ->selectRaw('ROUND(COALESCE(settlements.milk_amount, 0) - COALESCE(payments.paid, 0), 2) as balance')
            ->orderBy('c.name');
    }
}
