<?php

namespace App\Services;

use App\Models\Center;
use App\Models\CenterSettlement;
use App\Models\MilkIntake;
use App\Models\SettlementPeriodTemplate;
use App\Services\CenterBalanceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CenterPeriodBillReportService
{
    public function resolvePeriodRange(string $selectedMonth, string $templateId): array
    {
        if (! $selectedMonth || ! $templateId) {
            return [null, null];
        }

        try {
            $month = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            return [null, null];
        }

        $template = SettlementPeriodTemplate::find((int) $templateId);
        if (! $template) {
            return [null, null];
        }

        $endOfMonth = $month->copy()->endOfMonth();
        $from = $month->copy();
        $to = $month->copy();

        $startDay = min((int) $template->start_day, (int) $endOfMonth->day);
        $from->day($startDay);

        if ($template->end_of_month) {
            $to = $endOfMonth;
        } else {
            $endDay = $template->end_day ?? $template->start_day;
            $endDay = min((int) $endDay, (int) $endOfMonth->day);
            $to->day($endDay);
        }

        if ($to->greaterThan($endOfMonth)) {
            $to = $endOfMonth;
        }

        return [$from->toDateString(), $to->toDateString()];
    }

    public function buildReport(int $centerId, string $from, string $to): array
    {
        $center = Center::find($centerId);

        $intakes = MilkIntake::where('center_id', $centerId)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->orderBy('shift')
            ->get();

        $rows = [];
        $intakesByDate = $intakes->groupBy(fn (MilkIntake $intake) => $intake->date?->toDateString());
        $cursor = Carbon::parse($from);
        $endDate = Carbon::parse($to);
        $shifts = [MilkIntake::SHIFT_MORNING, MilkIntake::SHIFT_EVENING];

        while ($cursor->lte($endDate)) {
            $date = $cursor->toDateString();
            $dayIntakes = $intakesByDate->get($date, collect());
            $dayIntakesByShift = $dayIntakes->groupBy('shift');

            foreach ($shifts as $shift) {
                $shiftIntakes = $dayIntakesByShift->get($shift, collect());

                if ($shiftIntakes->isEmpty()) {
                    $rows[] = [
                        'date' => $date,
                        'shift' => $shift,
                        'milk_type' => '-',
                        'qty' => 0,
                        'qty_unit' => 'L',
                        'fat_pct' => null,
                        'snf_pct' => null,
                        'rate' => null,
                        'amount' => 0,
                    ];
                    continue;
                }

                foreach ($shiftIntakes as $intake) {
                    $rows[] = [
                        'date' => $intake->date?->toDateString(),
                        'shift' => $intake->shift,
                        'milk_type' => $intake->milk_type,
                        'qty' => (float) $intake->qty_ltr,
                        'qty_unit' => 'L',
                        'fat_pct' => $intake->fat_pct,
                        'snf_pct' => $intake->snf_pct,
                        'rate' => $intake->rate_per_ltr,
                        'amount' => $intake->amount,
                    ];
                }
            }

            $cursor->addDay();
        }

        $totals = [
            'qty_ltr' => (float) $intakes->sum('qty_ltr'),
            'amount' => (float) $intakes->sum('amount'),
            'commission' => (float) $intakes->sum('commission_amount'),
        ];

        $settlement = CenterSettlement::where('center_id', $centerId)
            ->whereDate('period_from', $from)
            ->whereDate('period_to', $to)
            ->first();

        $advanceGiven = $this->advanceGivenInPeriod($centerId, $from, $to);

        if ($settlement) {
            $payable = [
                'gross_amount_total' => (float) $settlement->gross_amount_total,
                'commission_total' => $totals['commission'],
                'incentive_amount' => (float) $settlement->incentive_amount,
                'advance_given' => $advanceGiven,
                'advance_deducted' => (float) $settlement->advance_deducted,
                'short_adjustment' => (float) $settlement->short_adjustment,
                'other_deductions' => (float) $settlement->other_deductions,
                'discount_amount' => (float) $settlement->discount_amount,
                'tds_amount' => (float) $settlement->tds_amount,
                'net_total' => (float) $settlement->net_total,
            ];
            $hasSettlement = true;
        } else {
            $gross = $totals['amount'];

            $payable = [
                'gross_amount_total' => $gross,
                'commission_total' => $totals['commission'],
                'incentive_amount' => 0.0,
                'advance_given' => $advanceGiven,
                'advance_deducted' => 0.0,
                'short_adjustment' => 0.0,
                'other_deductions' => 0.0,
                'discount_amount' => 0.0,
                'tds_amount' => 0.0,
                'net_total' => round($gross, 2),
            ];
            $hasSettlement = false;
        }

        $balanceService = app(CenterBalanceService::class);
        $netPayableTillEnd = $balanceService->getNetPayableTillDate($centerId, $to);
        $advanceOutstandingTillEnd = $balanceService->getAdvanceOutstandingTillDate($centerId, $to);

        return [
            'center' => $center,
            'rows' => $rows,
            'totals' => $totals,
            'payable' => $payable,
            'netPayableTillEnd' => $netPayableTillEnd,
            'advanceOutstandingTillEnd' => $advanceOutstandingTillEnd,
            'hasSettlement' => $hasSettlement,
        ];
    }

    private function advanceGivenInPeriod(int $centerId, string $from, string $to): float
    {
        $query = DB::table('center_payments')
            ->whereNull('deleted_at')
            ->where('center_id', $centerId)
            ->whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->where('payment_type', 'ADVANCE');

        if (Schema::hasColumn('center_payments', 'is_locked')) {
            $query->where('is_locked', true);
        }

        return round((float) $query->sum('amount'), 2);
    }
}
