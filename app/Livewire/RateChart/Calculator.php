<?php

namespace App\Livewire\RateChart;

use App\Models\Center;
use App\Services\MilkRateCalculator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use RuntimeException;

class Calculator extends Component
{
    use AuthorizesRequests;

    public $title = 'Rate Calculator';
    public $center_id;
    public $milk_type = 'CM';
    public $date;
    public $fat;
    public $snf;

    public ?array $result = null;
    public ?string $errorMessage = null;
    public $centers = [];

    public function mount(): void
    {
        $this->authorize('ratechart.view');
        $this->centers = Center::orderBy('name')->get();
        $this->date = now()->toDateString();
    }

    public function calculate(MilkRateCalculator $calculator): void
    {
        $data = $this->validate([
            'center_id' => ['required', 'exists:centers,id'],
            'milk_type' => ['required', 'in:CM,BM,MIX'],
            'date' => ['required', 'date'],
            'fat' => ['required', 'numeric'],
            'snf' => ['required', 'numeric'],
        ], [], [
            'center_id' => 'Center',
        ]);

        try {
            $this->result = $calculator->calculate(
                (int) $data['center_id'],
                $data['milk_type'],
                $data['date'],
                (float) $data['fat'],
                (float) $data['snf']
            );
            $this->errorMessage = null;
        } catch (RuntimeException $e) {
            $this->result = null;
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.rate-chart.calculator')
            ->with(['title_name' => $this->title ?? 'Rate Calculator']);
    }
}
