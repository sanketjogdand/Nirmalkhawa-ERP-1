<?php

namespace App\Livewire\RateChart;

use App\Models\RateChart;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Rate Charts';
    public $perPage = 25;
    public $search = '';
    public $milkType = '';
    public $effectiveOn = '';

    public function mount(): void
    {
        $this->authorize('ratechart.view');
    }

    public function updating($field): void
    {
        if (in_array($field, ['search', 'milkType', 'effectiveOn'])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function deleteChart(int $chartId): void
    {
        $this->authorize('ratechart.delete');

        $chart = RateChart::findOrFail($chartId);
        if ($chart->assignments()->exists()) {
            session()->flash('error', 'Remove assignments before deleting this rate chart.');
            return;
        }

        $chart->delete();

        session()->flash('success', 'Rate chart deleted.');
        $this->resetPage();
    }

    public function render()
    {
        $charts = RateChart::query()
            ->withCount('assignments')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->milkType, fn ($q) => $q->where('milk_type', $this->milkType))
            ->when($this->effectiveOn, fn ($q) => $q->effectiveOn($this->effectiveOn))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.rate-chart.view', compact('charts'))
            ->with(['title_name' => $this->title ?? 'Rate Charts']);
    }
}
