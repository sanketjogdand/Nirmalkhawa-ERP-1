<?php

namespace App\Livewire\Reports\MilkProduction;

use App\Livewire\Reports\BaseReport;
use App\Models\MilkIntake;
use Illuminate\Support\Facades\DB;

class ManualRateExceptionReport extends BaseReport
{
    public string $title = 'Manual Rate Exception Report';

    protected string $dateField = 'mi.date';

    protected function columns(): array
    {
        return [
            'date' => 'Date',
            'center' => 'Center',
            'milk_type' => 'Milk Type',
            'qty_ltr' => 'Qty (Ltr)',
            'rate_per_ltr' => 'Rate/Ltr',
            'manual_rate_by' => 'Manual Rate By',
            'manual_rate_reason' => 'Reason',
        ];
    }

    protected function baseQuery()
    {
        return DB::table('milk_intakes as mi')
            ->join('centers as c', 'c.id', '=', 'mi.center_id')
            ->leftJoin('users as u', 'u.id', '=', 'mi.manual_rate_by')
            ->whereNull('mi.deleted_at')
            ->where('mi.rate_status', MilkIntake::STATUS_MANUAL)
            ->selectRaw('mi.date as date')
            ->selectRaw('c.name as center')
            ->selectRaw('mi.milk_type as milk_type')
            ->selectRaw('ROUND(COALESCE(mi.qty_ltr, 0), 3) as qty_ltr')
            ->selectRaw('ROUND(COALESCE(mi.rate_per_ltr, 0), 2) as rate_per_ltr')
            ->selectRaw('COALESCE(u.name, "-") as manual_rate_by')
            ->selectRaw('COALESCE(mi.manual_rate_reason, "-") as manual_rate_reason')
            ->orderByDesc('mi.date')
            ->orderBy('c.name');
    }
}
