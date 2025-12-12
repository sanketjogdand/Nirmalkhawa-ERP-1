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
    public $status = '';
    public $effectiveOn = '';

    public function mount(): void
    {
        $this->authorize('ratechart.view');
    }

    public function updating($field): void
    {
        if (in_array($field, ['search', 'milkType', 'status', 'effectiveOn'])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $chartId): void
    {
        $this->authorize('ratechart.update');

        $chart = RateChart::findOrFail($chartId);
        $chart->is_active = ! $chart->is_active;
        $chart->save();

        session()->flash('success', 'Rate chart status updated.');
    }

    public function render()
    {
        $charts = RateChart::query()
            ->withCount(['assignments as active_assignments_count' => fn ($q) => $q->where('is_active', true)])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->milkType, fn ($q) => $q->where('milk_type', $this->milkType))
            ->when($this->status !== '', fn ($q) => $q->where('is_active', $this->status === 'active'))
            ->when($this->effectiveOn, fn ($q) => $q->effectiveOn($this->effectiveOn))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.rate-chart.view', compact('charts'))
            ->with(['title_name' => $this->title ?? 'Rate Charts']);
    }
}
