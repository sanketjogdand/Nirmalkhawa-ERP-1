<?php

namespace App\Livewire\Reports\Management;

use App\Livewire\Reports\BaseReport;
use App\Models\MilkIntake;
use Illuminate\Support\Facades\DB;

class ManualOverrideReport extends BaseReport
{
    public string $title = 'Manual Override Report';

    protected string $dateField = 'date';

    protected function columns(): array
    {
        return [
            'date' => 'Date',
            'module' => 'Module',
            'user' => 'User',
            'remarks' => 'Remarks',
        ];
    }

    protected function baseQuery()
    {
        $milk = DB::table('milk_intakes as mi')
            ->leftJoin('users as u', 'u.id', '=', 'mi.manual_rate_by')
            ->whereNull('mi.deleted_at')
            ->where('mi.rate_status', MilkIntake::STATUS_MANUAL)
            ->selectRaw('mi.date as date')
            ->selectRaw('"Milk Intake" as module')
            ->selectRaw('COALESCE(u.name, "-") as user')
            ->selectRaw('COALESCE(mi.manual_rate_reason, "-") as remarks')
            ->selectRaw('mi.created_at as created_at');

        $adjustments = DB::table('stock_adjustments as sa')
            ->leftJoin('users as u', 'u.id', '=', 'sa.created_by')
            ->whereNull('sa.deleted_at')
            ->selectRaw('sa.adjustment_date as date')
            ->selectRaw('"Stock Adjustment" as module')
            ->selectRaw('COALESCE(u.name, "-") as user')
            ->selectRaw('COALESCE(sa.remarks, sa.reason, "-") as remarks')
            ->selectRaw('sa.created_at as created_at');

        $union = $milk->unionAll($adjustments);

        return DB::query()
            ->fromSub($union, 't')
            ->select('date', 'module', 'user', 'remarks', 'created_at')
            ->orderByDesc('date')
            ->orderByDesc('created_at');
    }
}
