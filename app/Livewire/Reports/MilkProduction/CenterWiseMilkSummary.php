<?php

namespace App\Livewire\Reports\MilkProduction;

use App\Livewire\Reports\BaseReport;
use App\Models\Center;
use Illuminate\Support\Facades\DB;

class CenterWiseMilkSummary extends BaseReport
{
    public string $title = 'Center-wise Milk Summary';
    public string $centerId = '';
    public string $milkType = '';
    public array $centerOptions = [];

    protected string $dateField = 'mi.date';
    protected array $filterFields = ['centerId', 'milkType'];

    protected function initFilters(): void
    {
        $this->centerOptions = Center::orderBy('name')
            ->get()
            ->map(fn ($center) => ['value' => (string) $center->id, 'label' => $center->name])
            ->all();
    }

    protected function filterConfig(): array
    {
        return [
            'centerId' => [
                'label' => 'Center',
                'options' => $this->centerOptions,
            ],
            'milkType' => [
                'label' => 'Milk Type',
                'options' => [
                    ['value' => 'CM', 'label' => 'CM'],
                    ['value' => 'BM', 'label' => 'BM'],
                    ['value' => 'MIX', 'label' => 'MIX'],
                ],
            ],
        ];
    }

    protected function columns(): array
    {
        return [
            'center' => 'Center',
            'milk_type' => 'Milk Type',
            'total_qty_ltr' => 'Total Qty (Ltr)',
            'avg_fat' => 'Avg Fat',
            'avg_snf' => 'Avg SNF',
            'total_amount' => 'Total Amount',
        ];
    }

    protected function baseQuery()
    {
        return DB::table('milk_intakes as mi')
            ->join('centers as c', 'c.id', '=', 'mi.center_id')
            ->whereNull('mi.deleted_at')
            ->when($this->centerId, fn ($q) => $q->where('mi.center_id', $this->centerId))
            ->when($this->milkType, fn ($q) => $q->where('mi.milk_type', $this->milkType))
            ->selectRaw('c.name as center')
            ->selectRaw('mi.milk_type as milk_type')
            ->selectRaw('ROUND(COALESCE(SUM(mi.qty_ltr), 0), 3) as total_qty_ltr')
            ->selectRaw('ROUND(COALESCE(AVG(mi.fat_pct), 0), 2) as avg_fat')
            ->selectRaw('ROUND(COALESCE(AVG(mi.snf_pct), 0), 2) as avg_snf')
            ->selectRaw('ROUND(COALESCE(SUM(mi.amount), 0), 2) as total_amount')
            ->groupBy('c.name', 'mi.milk_type')
            ->orderBy('c.name')
            ->orderBy('mi.milk_type');
    }
}
