<?php

namespace App\Services;

use App\Models\CenterSettlement;
use App\Models\MilkIntake;
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

        $totals = $this->calculateTotals($intakes);

        return DB::transaction(function () use ($payload, $totals, $intakes) {
            $settlement = CenterSettlement::create([
                'center_id' => $payload['center_id'],
                'period_from' => $payload['period_from'],
                'period_to' => $payload['period_to'],
                'notes' => $payload['notes'] ?? null,
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

            $totals = $this->calculateTotals($intakes);

            MilkIntake::where('center_settlement_id', $settlement->id)->update([
                'center_settlement_id' => null,
            ]);

            $settlement->update([
                'center_id' => $payload['center_id'],
                'period_from' => $payload['period_from'],
                'period_to' => $payload['period_to'],
                'notes' => $payload['notes'] ?? null,
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

        $totals = $this->calculateTotals($intakes);

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

    public function calculateTotals(Collection $intakes): array
    {
        $cm = $intakes->where('milk_type', 'CM');
        $bm = $intakes->where('milk_type', 'BM');

        return [
            'total_qty_ltr' => $intakes->sum('qty_ltr'),
            'gross_amount_total' => $intakes->sum('amount'),
            'commission_total' => $intakes->sum('commission_amount'),
            'net_total' => $intakes->sum('net_amount'),
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
