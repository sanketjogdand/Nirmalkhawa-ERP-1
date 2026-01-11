<?php

namespace App\Services;

use App\Models\Center;
use App\Models\CenterRateChart;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

class MilkRateCalculator
{
    /**
     * Calculate rate for a center on a given date with the supplied fat/snf values.
     *
     * @return array{rate_chart_id:int, final_rate:float, base_rate:float, fat_adjustment:float, snf_adjustment:float}
     */
    public function calculate(int|Center $center, string $milkType, $date, float $fat, float $snf): array
    {
        $centerModel = $center instanceof Center ? $center : Center::findOrFail($center);
        $targetDate = Carbon::parse($date)->toDateString();

        $assignment = CenterRateChart::query()
            ->where('center_id', $centerModel->id)
            ->where('milk_type', $milkType)
            ->forDate($targetDate)
            ->with(['rateChart.slabs'])
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        if (! $assignment) {
            throw new RuntimeException('rate_not_configured');
        }

        $rateChart = $assignment->rateChart;
        $fatAdjustment = $this->calculateAdjustment($fat, $rateChart->base_fat, $rateChart->slabs->where('param_type', 'FAT'));
        $snfAdjustment = $this->calculateAdjustment($snf, $rateChart->base_snf, $rateChart->slabs->where('param_type', 'SNF'));

        $finalRate = round($rateChart->base_rate + $fatAdjustment + $snfAdjustment, 2);

        return [
            'rate_chart_id' => $rateChart->id,
            'final_rate' => $finalRate,
            'base_rate' => (float) $rateChart->base_rate,
            'fat_adjustment' => round($fatAdjustment, 2),
            'snf_adjustment' => round($snfAdjustment, 2),
        ];
    }

    private function calculateAdjustment(float $value, float $base, Collection $slabs): float
    {
        if (abs($value - $base) < 0.0001) {
            return 0.0;
        }

        $rangeStart = min($value, $base);
        $rangeEnd = max($value, $base);
        $adjustment = 0.0;

        $orderedSlabs = $slabs->sortBy([
            ['priority', 'asc'],
            ['start_val', 'asc'],
        ]);

        foreach ($orderedSlabs as $slab) {
            $overlapStart = max($rangeStart, $slab->start_val);
            $overlapEnd = min($rangeEnd, $slab->end_val);

            if ($overlapEnd <= $overlapStart) {
                continue;
            }

            $step = max($slab->step, 0.0001);
            $steps = floor((($overlapEnd - $overlapStart) / $step) + 1e-9);

            if ($steps <= 0) {
                continue;
            }

            $adjustment += $steps * $slab->rate_per_step;
        }

        return $adjustment;
    }
}
