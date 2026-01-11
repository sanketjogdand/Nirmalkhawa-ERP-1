<?php

namespace App\Services;

use App\Models\CenterSettlement;
use App\Models\MilkIntake;
use App\Services\CenterBalanceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CenterSettlementService
{
    public function createSettlement(array $payload): CenterSettlement
    {
        $this->assertUniquePeriod($payload['center_id'], $payload['period_from'], $payload['period_to']);

        $intakes = $this->fetchIntakes(
            (int) $payload['center_id'],
            $payload['period_from'],
            $payload['period_to']
        );

        if ($intakes->isEmpty()) {
            throw new RuntimeException('No unsettled milk intakes found for the selected period.');
        }

        $totals = $this->calculateTotals($intakes, $payload);
        $this->assertAdvanceDeductedWithinOutstanding(
            (int) $payload['center_id'],
            $payload['period_to'],
            $payload['advance_deducted'] ?? 0
        );

        return DB::transaction(function () use ($payload, $totals, $intakes) {
            $settlement = CenterSettlement::create([
                'center_id' => $payload['center_id'],
                'period_from' => $payload['period_from'],
                'period_to' => $payload['period_to'],
                'notes' => $payload['notes'] ?? null,
                'remarks' => $payload['remarks'] ?? null,
                'created_by' => $payload['created_by'] ?? Auth::id(),
                'is_locked' => false,
                ...$totals,
            ]);

            MilkIntake::whereIn('id', $intakes->pluck('id'))->update([
                'center_settlement_id' => $settlement->id,
            ]);

            return $settlement->load('center');
        });
    }

    public function updateSettlement(CenterSettlement $settlement, array $payload): CenterSettlement
    {
        if ($settlement->is_locked) {
            throw new RuntimeException('Settlement is locked. Ask admin to unlock first.');
        }

        $this->assertUniquePeriod($payload['center_id'], $payload['period_from'], $payload['period_to'], $settlement->id);

        return DB::transaction(function () use ($settlement, $payload) {
            $intakes = $this->fetchIntakes(
                (int) $payload['center_id'],
                $payload['period_from'],
                $payload['period_to'],
                $settlement->id
            );

            if ($intakes->isEmpty()) {
                throw new RuntimeException('No unsettled milk intakes found for the selected period.');
            }

            $totals = $this->calculateTotals($intakes, $payload);
            $this->assertAdvanceDeductedWithinOutstanding(
                (int) $payload['center_id'],
                $payload['period_to'],
                $payload['advance_deducted'] ?? 0,
                $settlement
            );

            MilkIntake::where('center_settlement_id', $settlement->id)->update([
                'center_settlement_id' => null,
            ]);

            $settlement->update([
                'center_id' => $payload['center_id'],
                'period_from' => $payload['period_from'],
                'period_to' => $payload['period_to'],
                'notes' => array_key_exists('notes', $payload) ? ($payload['notes'] ?? null) : $settlement->notes,
                'remarks' => $payload['remarks'] ?? null,
                'is_locked' => false,
                'locked_by' => null,
                'locked_at' => null,
                ...$totals,
            ]);

            MilkIntake::whereIn('id', $intakes->pluck('id'))->update([
                'center_settlement_id' => $settlement->id,
            ]);

            return $settlement->refresh()->load('center');
        });
    }

    public function lock(CenterSettlement $settlement, int $userId): CenterSettlement
    {
        if ($settlement->is_locked) {
            return $settlement;
        }

        $intakes = $settlement->milkIntakes()->get();
        if ($intakes->isEmpty()) {
            throw new RuntimeException('Cannot lock settlement without linked milk intakes.');
        }

        $totals = $this->calculateTotals($intakes, $this->extractAdjustments($settlement));

        $settlement->update([
            'is_locked' => true,
            'locked_by' => $userId,
            'locked_at' => now(),
            ...$totals,
        ]);

        return $settlement->refresh();
    }

    public function unlock(CenterSettlement $settlement): CenterSettlement
    {
        $settlement->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ]);

        return $settlement->refresh();
    }

    public function delete(CenterSettlement $settlement): void
    {
        if ($this->hasPaymentAllocations($settlement)) {
            throw new RuntimeException('Cannot delete settlement with payment allocations.');
        }

        if ($settlement->is_locked) {
            throw new RuntimeException('Settlement is locked. Ask admin to unlock first.');
        }

        DB::transaction(function () use ($settlement) {
            MilkIntake::where('center_settlement_id', $settlement->id)->update([
                'center_settlement_id' => null,
            ]);

            $settlement->delete();
        });
    }

    public function calculateTotals(Collection $intakes, array $adjustments = []): array
    {
        $cm = $intakes->where('milk_type', 'CM');
        $bm = $intakes->where('milk_type', 'BM');

        $adjustments = $this->normalizeAdjustments($adjustments);
        $grossAmount = (float) $intakes->sum('amount');
        $commissionTotal = (float) $intakes->sum('commission_amount');
        $netTotal = $grossAmount
            + $commissionTotal
            + $adjustments['incentive_amount']
            - $adjustments['advance_deducted']
            - $adjustments['short_adjustment']
            - $adjustments['other_deductions']
            - $adjustments['discount_amount']
            - $adjustments['tds_amount'];

        return [
            'total_qty_ltr' => $intakes->sum('qty_ltr'),
            'gross_amount_total' => $grossAmount,
            'commission_total' => $commissionTotal,
            'incentive_amount' => $adjustments['incentive_amount'],
            'advance_deducted' => $adjustments['advance_deducted'],
            'short_adjustment' => $adjustments['short_adjustment'],
            'other_deductions' => $adjustments['other_deductions'],
            'discount_amount' => $adjustments['discount_amount'],
            'tds_amount' => $adjustments['tds_amount'],
            'net_total' => round($netTotal, 2),
            'cm_qty_ltr' => $cm->sum('qty_ltr'),
            'cm_gross_amount' => $cm->sum('amount'),
            'cm_commission' => $cm->sum('commission_amount'),
            'cm_net' => $cm->sum('net_amount'),
            'bm_qty_ltr' => $bm->sum('qty_ltr'),
            'bm_gross_amount' => $bm->sum('amount'),
            'bm_commission' => $bm->sum('commission_amount'),
            'bm_net' => $bm->sum('net_amount'),
        ];
    }

    private function normalizeAdjustments(array $adjustments): array
    {
        return [
            'incentive_amount' => $this->normalizeAmount($adjustments['incentive_amount'] ?? null),
            'advance_deducted' => $this->normalizeAmount($adjustments['advance_deducted'] ?? null),
            'short_adjustment' => $this->normalizeAmount($adjustments['short_adjustment'] ?? null),
            'other_deductions' => $this->normalizeAmount($adjustments['other_deductions'] ?? null),
            'discount_amount' => $this->normalizeAmount($adjustments['discount_amount'] ?? null),
            'tds_amount' => $this->normalizeAmount($adjustments['tds_amount'] ?? null),
        ];
    }

    private function normalizeAmount($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) $value;
    }

    private function extractAdjustments(CenterSettlement $settlement): array
    {
        return [
            'incentive_amount' => $settlement->incentive_amount,
            'advance_deducted' => $settlement->advance_deducted,
            'short_adjustment' => $settlement->short_adjustment,
            'other_deductions' => $settlement->other_deductions,
            'discount_amount' => $settlement->discount_amount,
            'tds_amount' => $settlement->tds_amount,
        ];
    }

    private function assertAdvanceDeductedWithinOutstanding(
        int $centerId,
        string $periodTo,
        $advanceDeducted,
        ?CenterSettlement $existingSettlement = null
    ): void {
        $advanceDeducted = $this->normalizeAmount($advanceDeducted);
        $balanceService = app(CenterBalanceService::class);
        $available = $balanceService->getAdvanceOutstandingTillDate($centerId, $periodTo);

        if ($existingSettlement && (int) $existingSettlement->center_id === $centerId) {
            $available += (float) $existingSettlement->advance_deducted;
        }

        if ($advanceDeducted > $available) {
            $availableFormatted = number_format($available, 2, '.', '');
            $attemptedFormatted = number_format($advanceDeducted, 2, '.', '');
            throw new RuntimeException(
                "Advance deducted exceeds advance outstanding. Available {$availableFormatted}, attempted {$attemptedFormatted}."
            );
        }
    }

    private function fetchIntakes(int $centerId, string $from, string $to, ?int $existingSettlementId = null): Collection
    {
        return MilkIntake::where('center_id', $centerId)
            ->whereBetween('date', [$from, $to])
            ->where(function ($query) use ($existingSettlementId) {
                $query->whereNull('center_settlement_id');

                if ($existingSettlementId) {
                    $query->orWhere('center_settlement_id', $existingSettlementId);
                }
            })
            ->get();
    }

    private function hasPaymentAllocations(CenterSettlement $settlement): bool
    {
        // Placeholder for future payment allocation check
        return false;
    }

    private function assertUniquePeriod(int $centerId, string $from, string $to, ?int $ignoreId = null): void
    {
        $query = CenterSettlement::withTrashed()
            ->where('center_id', $centerId)
            ->whereDate('period_from', $from)
            ->whereDate('period_to', $to);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new RuntimeException('A settlement already exists for this center and period.');
        }
    }
}
