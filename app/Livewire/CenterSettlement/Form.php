<?php

namespace App\Livewire\CenterSettlement;

use App\Models\Center;
use App\Models\CenterSettlement;
use App\Models\SettlementPeriodTemplate;
use App\Models\MilkIntake;
use App\Services\CenterSettlementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use RuntimeException;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Center Settlement';

    public ?int $settlementId = null;
    public $center_id;
    public $period_from;
    public $period_to;
    public $remarks;
    public $incentive_amount = 0;
    public $advance_deducted = 0;
    public $short_adjustment = 0;
    public $other_deductions = 0;
    public $discount_amount = 0;
    public $tds_amount = 0;

    public string $selectedMonth;
    public $templateId = '';

    public Collection $centers;
    public Collection $templates;
    public array $previewTotals = [];
    public int $previewCount = 0;
    public array $previewRows = [];

    public function mount($settlement = null): void
    {
        $this->centers = Center::orderBy('name')->get();
        $this->templates = SettlementPeriodTemplate::where('is_active', true)->orderBy('start_day')->get();
        $this->selectedMonth = now()->format('Y-m');

        if ($settlement) {
            $record = CenterSettlement::findOrFail($settlement);
            $this->authorize('centersettlement.update');

            if ($record->is_locked) {
                session()->flash('danger', 'Settlement is locked. Ask admin to unlock first.');
                $this->redirectRoute('center-settlements.show', $record->id);
                return;
            }

            $this->settlementId = $record->id;
            $this->center_id = $record->center_id;
            $this->period_from = $record->period_from?->toDateString();
            $this->period_to = $record->period_to?->toDateString();
            $this->remarks = $record->remarks ?? $record->notes;
            $this->incentive_amount = $record->incentive_amount ?? 0;
            $this->advance_deducted = $record->advance_deducted ?? 0;
            $this->short_adjustment = $record->short_adjustment ?? 0;
            $this->other_deductions = $record->other_deductions ?? 0;
            $this->discount_amount = $record->discount_amount ?? 0;
            $this->tds_amount = $record->tds_amount ?? 0;
            $this->selectedMonth = $record->period_from?->format('Y-m') ?? $this->selectedMonth;

            $this->refreshPreview();
        } else {
            $this->authorize('centersettlement.create');
            $this->resetAdjustments();
            $this->applyTemplate();
            $this->refreshPreview();
        }
    }

    public function updated($property): void
    {
        if (in_array($property, [
            'center_id',
            'period_from',
            'period_to',
            'incentive_amount',
            'advance_deducted',
            'short_adjustment',
            'other_deductions',
            'discount_amount',
            'tds_amount',
        ])) {
            $this->refreshPreview();
        }

        if ($property === 'templateId' || $property === 'selectedMonth') {
            $this->applyTemplate();
            $this->refreshPreview();
        }
    }

    public function render()
    {
        return view('livewire.center-settlement.form')
            ->with(['title_name' => $this->title ?? 'Center Settlement']);
    }

    public function save(CenterSettlementService $service)
    {
        $this->authorize($this->settlementId ? 'centersettlement.update' : 'centersettlement.create');
        $data = $this->validate([
            'center_id' => ['required', 'exists:centers,id'],
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
            'incentive_amount' => ['nullable', 'numeric', 'min:0'],
            'advance_deducted' => ['nullable', 'numeric', 'min:0'],
            'short_adjustment' => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tds_amount' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
        ], [], [
            'center_id' => 'Center',
            'period_from' => 'Period from',
            'period_to' => 'Period to',
        ]);

        $adjustments = $this->adjustmentPayload();

        try {
            if ($this->settlementId) {
                $settlement = CenterSettlement::findOrFail($this->settlementId);
                $settlement = $service->updateSettlement($settlement, [
                    ...$data,
                    ...$adjustments,
                    'remarks' => $data['remarks'] ?? null,
                ]);
            } else {
                $settlement = $service->createSettlement([
                    ...$data,
                    ...$adjustments,
                    'remarks' => $data['remarks'] ?? null,
                ]);
            }
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
            $this->refreshPreview();
            return;
        }

        session()->flash('success', 'Settlement saved.');

        return redirect()->route('center-settlements.show', $settlement->id);
    }

    public function refreshPreview(): void
    {
        if (! $this->center_id || ! $this->period_from || ! $this->period_to) {
            $this->previewTotals = $this->emptyTotals();
            $this->previewCount = 0;
            $this->previewRows = [];
            return;
        }

        $intakes = MilkIntake::with('centerSettlement')
            ->where('center_id', $this->center_id)
            ->whereBetween('date', [$this->period_from, $this->period_to])
            ->when($this->settlementId, function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('center_settlement_id')
                        ->orWhere('center_settlement_id', $this->settlementId);
                });
            }, function ($query) {
                $query->whereNull('center_settlement_id');
            })
            ->get();

        $this->previewCount = $intakes->count();
        $this->previewTotals = app(CenterSettlementService::class)->calculateTotals(
            $intakes,
            $this->adjustmentPayload()
        );
        $this->previewRows = $intakes->map(function (MilkIntake $intake) {
            return [
                'id' => $intake->id,
                'date' => $intake->date?->toDateString(),
                'shift' => $intake->shift,
                'milk_type' => $intake->milk_type,
                'qty_ltr' => $intake->qty_ltr,
                'fat_pct' => $intake->fat_pct,
                'snf_pct' => $intake->snf_pct,
                'center_settlement_id' => $intake->center_settlement_id,
            ];
        })->toArray();
    }

    private function applyTemplate(): void
    {
        if (! $this->selectedMonth || (! $this->templateId && $this->templates->isEmpty())) {
            return;
        }

        $template = $this->templateId
            ? $this->templates->firstWhere('id', (int) $this->templateId)
            : $this->templates->first();

        if (! $template) {
            return;
        }

        $month = Carbon::createFromFormat('Y-m', $this->selectedMonth)->startOfMonth();
        $from = $month->copy()->day((int) $template->start_day);

        $endOfMonth = $month->copy()->endOfMonth();
        $to = $template->end_of_month
            ? $endOfMonth
            : $month->copy()->day($template->end_day ?? $template->start_day);

        if ($to->greaterThan($endOfMonth)) {
            $to = $endOfMonth;
        }

        $this->period_from = $from->toDateString();
        $this->period_to = $to->toDateString();
    }

    private function emptyTotals(): array
    {
        return [
            'total_qty_ltr' => 0,
            'gross_amount_total' => 0,
            'commission_total' => 0,
            'incentive_amount' => 0,
            'advance_deducted' => 0,
            'short_adjustment' => 0,
            'other_deductions' => 0,
            'discount_amount' => 0,
            'tds_amount' => 0,
            'net_total' => 0,
            'cm_qty_ltr' => 0,
            'cm_gross_amount' => 0,
            'cm_commission' => 0,
            'cm_net' => 0,
            'bm_qty_ltr' => 0,
            'bm_gross_amount' => 0,
            'bm_commission' => 0,
            'bm_net' => 0,
        ];
    }

    private function adjustmentPayload(): array
    {
        return [
            'incentive_amount' => $this->normalizeAmount($this->incentive_amount),
            'advance_deducted' => $this->normalizeAmount($this->advance_deducted),
            'short_adjustment' => $this->normalizeAmount($this->short_adjustment),
            'other_deductions' => $this->normalizeAmount($this->other_deductions),
            'discount_amount' => $this->normalizeAmount($this->discount_amount),
            'tds_amount' => $this->normalizeAmount($this->tds_amount),
        ];
    }

    private function normalizeAmount($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) $value;
    }

    private function resetAdjustments(): void
    {
        $this->incentive_amount = 0;
        $this->advance_deducted = 0;
        $this->short_adjustment = 0;
        $this->other_deductions = 0;
        $this->discount_amount = 0;
        $this->tds_amount = 0;
    }
}
