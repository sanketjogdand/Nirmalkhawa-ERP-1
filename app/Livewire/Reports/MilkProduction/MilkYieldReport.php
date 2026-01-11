<?php

namespace App\Livewire\Reports\MilkProduction;

use App\Livewire\Reports\BaseReport;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MilkYieldReport extends BaseReport
{
    public string $title = 'Milk Yield Report';

    protected function columns(): array
    {
        return [
            'period' => 'Date Range',
            'milk_qty' => 'Milk Qty',
            'output_qty' => 'Output Qty',
            'yield_percent' => 'Yield %',
        ];
    }

    protected function baseQuery()
    {
        return DB::query()->selectRaw('1 as placeholder');
    }

    protected function buildRows(): array
    {
        $milkQuery = DB::table('milk_intakes as mi')
            ->whereNull('mi.deleted_at')
            ->when($this->fromDate, fn ($q) => $q->whereDate('mi.date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('mi.date', '<=', $this->toDate));

        $outputQuery = DB::table('production_batches as pb')
            ->whereNull('pb.deleted_at')
            ->when($this->fromDate, fn ($q) => $q->whereDate('pb.date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('pb.date', '<=', $this->toDate));

        $milkQty = (float) $milkQuery->sum('qty_ltr');
        $outputQty = (float) $outputQuery->sum('actual_output_qty');
        $yield = $milkQty > 0 ? round(($outputQty / $milkQty) * 100, 2) : 0.0;

        $period = $this->fromDate && $this->toDate
            ? $this->fromDate.' to '.$this->toDate
            : ($this->fromDate ? $this->fromDate.' onward' : 'All Dates');

        return [[
            'period' => $period,
            'milk_qty' => round($milkQty, 3),
            'output_qty' => round($outputQty, 3),
            'yield_percent' => $yield,
        ]];
    }

    protected function paginatedRows(): LengthAwarePaginator
    {
        $rows = $this->buildRows();

        return new LengthAwarePaginator(
            $rows,
            count($rows),
            $this->perPage,
            1,
            ['path' => request()->url()]
        );
    }

    protected function exportRows(): array
    {
        return $this->buildRows();
    }
}
