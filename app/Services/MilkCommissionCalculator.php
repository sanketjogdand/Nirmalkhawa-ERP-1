<?php

namespace App\Services;

use App\Models\CenterCommissionAssignment;
use Illuminate\Support\Carbon;
use RuntimeException;

class MilkCommissionCalculator
{
    /**
     * @return array{commission_amount:float, commission_policy_id:int|null}
     */
    public function calculate(int $centerId, string $milkType, $date, float $qtyLtr): array
    {
        $targetDate = Carbon::parse($date)->toDateString();

        $assignment = CenterCommissionAssignment::query()
            ->active()
            ->where('center_id', $centerId)
            ->forDate($targetDate)
            ->whereHas('commissionPolicy', function ($q) use ($milkType) {
                $q->where('milk_type', $milkType)->where('is_active', true);
            })
            ->with('commissionPolicy')
            ->orderByDesc('effective_from')
            ->first();

        if (! $assignment) {
            return [
                'commission_amount' => 0.0,
                'commission_policy_id' => null,
            ];
        }

        $policy = $assignment->commissionPolicy;
        if (! $policy) {
            throw new RuntimeException('Commission policy not found for assignment.');
        }

        $amount = round($qtyLtr * (float) $policy->value, 2);

        return [
            'commission_amount' => $amount,
            'commission_policy_id' => $policy->id,
        ];
    }
}
