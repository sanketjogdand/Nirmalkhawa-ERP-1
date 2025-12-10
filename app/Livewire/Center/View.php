<?php

namespace App\Livewire\Center;

use App\Models\Center;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Centers';
    public $perPage = 25;
    public $searchName = '';
    public $searchCode = '';
    public $searchMobile = '';
    public $statusFilter = '';

    public function mount(): void
    {
        $this->authorize('center.view');
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function updating($field): void
    {
        if (in_array($field, ['searchName', 'searchCode', 'searchMobile', 'statusFilter'])) {
            $this->resetPage();
        }
    }

    public function toggleStatus(int $centerId): void
    {
        $this->authorize('center.delete');

        $center = Center::findOrFail($centerId);
        $center->status = $center->status === 'Active' ? 'Inactive' : 'Active';
        $center->save();

        session()->flash('success', 'Center status updated.');
    }

    public function render()
    {
        $centers = Center::with(['village.taluka.district.state'])
            ->when($this->searchName, fn ($q) => $q->where('name', 'like', '%'.$this->searchName.'%'))
            ->when($this->searchCode, fn ($q) => $q->where('code', 'like', '%'.$this->searchCode.'%'))
            ->when($this->searchMobile, fn ($q) => $q->where('mobile', 'like', '%'.$this->searchMobile.'%'))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.center.view', compact('centers'))
            ->with(['title_name' => $this->title ?? 'KCB Industries Pvt. Ltd.']);
    }
}
