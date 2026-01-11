<?php

namespace App\Livewire\Reports\MilkProduction;

use App\Livewire\Reports\BaseReport;
use Illuminate\Support\Facades\DB;

class DailyMilkRegister extends BaseReport
{
    public string $title = 'Daily Milk Register';

    protected string $dateField = 'mi.date';

    protected function columns(): array
    {
        return [
            'date' => 'Date',
            'center' => 'Center',
            'shift' => 'Shift',
            'qty_ltr' => 'Qty (Ltr)',
            'avg_fat' => 'Avg Fat',
            'avg_snf' => 'Avg SNF',
            'rate_per_ltr' => 'Rate/Ltr',
            'amount' => 'Amount',
        ];
    }

    protected function baseQuery()
    {
        return DB::table('milk_intakes as mi')
            ->join('centers as c', 'c.id', '=', 'mi.center_id')
            ->whereNull('mi.deleted_at')
            ->selectRaw('mi.date as date')
            ->selectRaw('c.name as center')
            ->selectRaw('mi.shift as shift')
            ->selectRaw('ROUND(COALESCE(SUM(mi.qty_ltr), 0), 3) as qty_ltr')
            ->selectRaw('ROUND(COALESCE(AVG(mi.fat_pct), 0), 2) as avg_fat')
            ->selectRaw('ROUND(COALESCE(AVG(mi.snf_pct), 0), 2) as avg_snf')
            ->selectRaw('ROUND(COALESCE(AVG(mi.rate_per_ltr), 0), 2) as rate_per_ltr')
            ->selectRaw('ROUND(COALESCE(SUM(mi.amount), 0), 2) as amount')
            ->groupBy('mi.date', 'c.name', 'mi.shift')
            ->orderByDesc('mi.date')
            ->orderBy('c.name')
            ->orderBy('mi.shift');
    }
}
