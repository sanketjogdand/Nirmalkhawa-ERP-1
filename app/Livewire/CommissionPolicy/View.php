<?php

namespace App\Livewire\CommissionPolicy;

use App\Models\CommissionPolicy;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Commission Policies';
    public $perPage = 25;
    public $search = '';
    public $milkType = '';
    public $status = '';

    public function mount(): void
    {
        $this->authorize('commissionpolicy.view');
    }

    public function updating($field): void
    {
        if (in_array($field, ['search', 'milkType', 'status'])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $this->authorize('commissionpolicy.update');
        $policy = CommissionPolicy::findOrFail($id);
        $policy->is_active = ! $policy->is_active;
        $policy->save();
    }

    public function render()
    {
        $policies = CommissionPolicy::query()
            ->when($this->search, fn ($q) => $q->where('code', 'like', '%'.$this->search.'%'))
            ->when($this->milkType, fn ($q) => $q->where('milk_type', $this->milkType))
            ->when($this->status !== '', fn ($q) => $q->where('is_active', $this->status === 'active'))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.commission-policy.view', compact('policies'))
            ->with(['title_name' => $this->title ?? 'Commission Policies']);
    }
}
