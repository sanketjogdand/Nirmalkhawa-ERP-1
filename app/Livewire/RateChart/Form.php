<?php

namespace App\Livewire\RateChart;

use App\Models\RateChart;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Illuminate\Support\Carbon;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Rate Charts';

    public ?int $rateChartId = null;
    public $code;
    public $milk_type = 'CM';
    public $base_rate;
    public $base_fat = 3.5;
    public $base_snf = 8.5;
    public $effective_from;
    public $effective_to;

    public function mount($rateChart = null): void
    {
        if ($rateChart) {
            $chart = RateChart::findOrFail($rateChart);
            $this->authorize('ratechart.update');
            $this->rateChartId = $chart->id;
            $this->fill($chart->only([
                'code',
                'milk_type',
                'base_rate',
                'base_fat',
                'base_snf',
                'effective_from',
                'effective_to',
            ]));
        } else {
            $this->authorize('ratechart.create');
            $this->effective_from = $this->effective_from ?? now()->toDateString();
            $this->code = $this->generateCode();
        }
    }

    public function updated($property): void
    {
        if ($this->rateChartId) {
            return;
        }

        if (in_array($property, ['milk_type', 'effective_from'])) {
            $this->code = $this->generateCode();
        }
    }

    protected function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^RC-(CM|BM)-[0-9]{4}-[0-9]{3}$/',
                Rule::unique('rate_charts', 'code')->ignore($this->rateChartId),
            ],
            'milk_type' => ['required', 'in:CM,BM'],
            'base_rate' => ['required', 'numeric', 'gt:0'],
            'base_fat' => ['required', 'numeric', 'gt:0'],
            'base_snf' => ['required', 'numeric', 'gt:0'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }

    public function save()
    {
        $data = $this->validate();
        $data['code'] = strtoupper(trim($data['code']));

        if ($this->rateChartId) {
            RateChart::where('id', $this->rateChartId)->update($data);
            session()->flash('success', 'Rate chart updated.');
            $id = $this->rateChartId;
        } else {
            $chart = RateChart::create($data);
            session()->flash('success', 'Rate chart created.');
            $id = $chart->id;
        }

        return redirect()->route('rate-charts.show', $id);
    }

    public function render()
    {
        return view('livewire.rate-chart.form')
            ->with(['title_name' => $this->title ?? 'Rate Charts']);
    }

    private function generateCode(): string
    {
        $year = $this->effective_from
            ? Carbon::parse($this->effective_from)->format('Y')
            : now()->format('Y');

        $prefix = 'RC-'.$this->milk_type.'-'.$year;

        $latest = RateChart::where('milk_type', $this->milk_type)
            ->where('code', 'like', $prefix.'-%')
            ->orderBy('code', 'desc')
            ->value('code');

        $next = 1;
        if ($latest && preg_match('/^RC-(CM|BM)-\d{4}-(\d{3})$/', $latest, $matches)) {
            $next = (int) $matches[2] + 1;
        }

        return sprintf('%s-%03d', $prefix, $next);
    }
}
