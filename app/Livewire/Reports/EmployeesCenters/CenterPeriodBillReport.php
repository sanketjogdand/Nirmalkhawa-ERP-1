<?php

namespace App\Livewire\Reports\EmployeesCenters;

use App\Models\Center;
use App\Services\CenterPeriodBillReportService;
use App\Models\SettlementPeriodTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class CenterPeriodBillReport extends Component
{
    use AuthorizesRequests;

    public string $title = 'Milk Purchase Bill Report';

    public string $centerId = '';
    public string $selectedMonth = '';
    public string $templateId = '';

    public string $periodFrom = '';
    public string $periodTo = '';

    public array $centers = [];
    public array $templates = [];
    public array $rows = [];
    public array $totals = [];
    public array $payable = [];
    public float $netPayableTillEnd = 0.0;
    public float $advanceOutstandingTillEnd = 0.0;
    public bool $hasSettlement = false;

    public function mount(): void
    {
        $this->authorize('center_bill.view');
        $this->centers = Center::orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Center $center) => [
                'value' => (string) $center->id,
                'label' => $center->code ? $center->name.' ('.$center->code.')' : $center->name,
            ])
            ->all();

        $this->templates = SettlementPeriodTemplate::where('is_active', true)
            ->orderBy('start_day')
            ->get(['id', 'name', 'start_day', 'end_day', 'end_of_month'])
            ->map(fn (SettlementPeriodTemplate $template) => [
                'value' => (string) $template->id,
                'label' => $template->name.' ('.$template->start_day.'-'.($template->end_of_month ? 'EOM' : $template->end_day).')',
            ])
            ->all();

        $this->selectedMonth = now()->format('Y-m');
        $this->templateId = $this->templates[0]['value'] ?? '';
        $this->resetReport();
    }

    public function updated($field): void
    {
        if (in_array($field, ['centerId', 'selectedMonth', 'templateId'], true)) {
            $this->refreshReport();
        }
    }

    public function refreshReport(): void
    {
        if (! $this->centerId || ! $this->selectedMonth || ! $this->templateId) {
            $this->resetReport();
            return;
        }

        $service = app(CenterPeriodBillReportService::class);
        [$from, $to] = $service->resolvePeriodRange($this->selectedMonth, $this->templateId);
        if (! $from || ! $to) {
            $this->resetReport();
            return;
        }

        $report = $service->buildReport((int) $this->centerId, $from, $to);
        $this->periodFrom = $from;
        $this->periodTo = $to;
        $this->rows = $report['rows'];
        $this->totals = $report['totals'];
        $this->payable = $report['payable'];
        $this->netPayableTillEnd = $report['netPayableTillEnd'];
        $this->advanceOutstandingTillEnd = $report['advanceOutstandingTillEnd'];
        $this->hasSettlement = $report['hasSettlement'];
    }

    public function render()
    {
        return view('livewire.reports.center-period-bill')
            ->with(['title_name' => $this->title]);
    }


    private function resetReport(): void
    {
        $this->periodFrom = '';
        $this->periodTo = '';
        $this->rows = [];
        $this->totals = [
            'qty_ltr' => 0,
            'amount' => 0,
            'commission' => 0,
        ];
        $this->payable = [
            'gross_amount_total' => 0,
            'commission_total' => 0,
            'incentive_amount' => 0,
            'advance_given' => 0,
            'advance_deducted' => 0,
            'short_adjustment' => 0,
            'other_deductions' => 0,
            'discount_amount' => 0,
            'tds_amount' => 0,
            'net_total' => 0,
        ];
        $this->netPayableTillEnd = 0.0;
        $this->advanceOutstandingTillEnd = 0.0;
        $this->hasSettlement = false;
    }
}
