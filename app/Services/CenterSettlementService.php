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
    public function createDraft(array $payload): CenterSettlement
    {
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
                'settlement_no' => $payload['settlement_no'] ?? $this->generateSettlementNo($payload['period_from']),
                'status' => CenterSettlement::STATUS_DRAFT,
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

    public function updateDraft(CenterSettlement $settlement, array $payload): CenterSettlement
    {
        if ($settlement->status === CenterSettlement::STATUS_FINAL && $settlement->is_locked) {
            throw new RuntimeException('Unlock the settlement before editing.');
        }

        return DB::transaction(function () use ($settlement, $payload) {
            MilkIntake::where('center_settlement_id', $settlement->id)->update([
                'center_settlement_id' => null,
            ]);

            $intakes = $this->fetchIntakes(
                (int) $payload['center_id'],
                $payload['period_from'],
                $payload['period_to']
            );

            if ($intakes->isEmpty()) {
                throw new RuntimeException('No unsettled milk intakes found for the selected period.');
            }

            $totals = $this->calculateTotals($intakes);

            $settlement->update([
                'center_id' => $payload['center_id'],
                'period_from' => $payload['period_from'],
                'period_to' => $payload['period_to'],
                'notes' => $payload['notes'] ?? null,
                'status' => CenterSettlement::STATUS_DRAFT,
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

    public function finalize(CenterSettlement $settlement, int $userId): CenterSettlement
    {
        if ($settlement->status === CenterSettlement::STATUS_FINAL && $settlement->is_locked) {
            return $settlement;
        }

        $intakes = $settlement->milkIntakes()->get();
        if ($intakes->isEmpty()) {
            throw new RuntimeException('Cannot finalize settlement without linked milk intakes.');
        }

        $totals = $this->calculateTotals($intakes);

        $settlement->update([
            'status' => CenterSettlement::STATUS_FINAL,
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
            'status' => CenterSettlement::STATUS_DRAFT,
        ]);

        return $settlement->refresh();
    }

    public function delete(CenterSettlement $settlement): void
    {
        if ($this->hasPaymentAllocations($settlement)) {
            throw new RuntimeException('Cannot delete settlement with payment allocations.');
        }

        if ($settlement->is_locked) {
            throw new RuntimeException('Unlock the settlement before deleting.');
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

    public function generateSettlementNo(string $periodFrom): string
    {
        $prefix = 'CS-'.date('Ym', strtotime($periodFrom)).'-';

        $last = CenterSettlement::withTrashed()
            ->where('settlement_no', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('settlement_no');

        $next = 1;
        if ($last && str_starts_with($last, $prefix)) {
            $next = (int) substr($last, strlen($prefix)) + 1;
        }

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    private function fetchIntakes(int $centerId, string $from, string $to): Collection
    {
        return MilkIntake::where('center_id', $centerId)
            ->whereBetween('date', [$from, $to])
            ->whereNull('center_settlement_id')
            ->get();
    }

    private function hasPaymentAllocations(CenterSettlement $settlement): bool
    {
        // Placeholder for future payment allocation check
        return false;
    }
}
