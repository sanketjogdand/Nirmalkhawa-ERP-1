<?php

namespace App\Livewire\Supplier;

use App\Models\District;
use App\Models\State;
use App\Models\Supplier;
use App\Models\Taluka;
use App\Models\Village;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Suppliers';
    public $perPage = 25;
    public $searchName = '';
    public $searchMobile = '';
    public $statusFilter = '';
    public $filter_state_id;
    public $filter_district_id;
    public $filter_taluka_id;
    public $filter_village_id;

    public $states = [];
    public $districts = [];
    public $talukas = [];
    public $villages = [];

    public function mount(): void
    {
        $this->authorize('supplier.view');
        $this->states = State::orderBy('name')->get();
    }

    public function updating($field): void
    {
        if (in_array($field, [
            'searchName',
            'searchMobile',
            'statusFilter',
            'filter_state_id',
            'filter_district_id',
            'filter_taluka_id',
            'filter_village_id',
        ])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStateId(): void
    {
        $this->districts = $this->filter_state_id
            ? District::where('state_id', $this->filter_state_id)->orderBy('name')->get()
            : collect();

        $this->talukas = collect();
        $this->villages = collect();
        $this->filter_district_id = null;
        $this->filter_taluka_id = null;
        $this->filter_village_id = null;
    }

    public function updatedFilterDistrictId(): void
    {
        $this->talukas = $this->filter_district_id
            ? Taluka::where('district_id', $this->filter_district_id)->orderBy('name')->get()
            : collect();

        $this->villages = collect();
        $this->filter_taluka_id = null;
        $this->filter_village_id = null;
    }

    public function updatedFilterTalukaId(): void
    {
        $this->villages = $this->filter_taluka_id
            ? Village::where('taluka_id', $this->filter_taluka_id)->orderBy('name')->get()
            : collect();

        $this->filter_village_id = null;
    }

    public function deleteSupplier(int $supplierId): void
    {
        $this->authorize('supplier.delete');

        $supplier = Supplier::findOrFail($supplierId);
        $supplier->delete();

        session()->flash('success', 'Supplier deleted.');
        $this->resetPage();
    }

    public function render()
    {
        $suppliers = Supplier::with(['village.taluka.district.state'])
            ->when($this->searchName, fn ($q) => $q->where('name', 'like', '%'.$this->searchName.'%'))
            ->when($this->searchMobile, fn ($q) => $q->where('mobile', 'like', '%'.$this->searchMobile.'%'))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('is_active', (bool) ((int) $this->statusFilter)))
            ->when($this->filter_state_id, fn ($q) => $q->where('state_id', $this->filter_state_id))
            ->when($this->filter_district_id, fn ($q) => $q->where('district_id', $this->filter_district_id))
            ->when($this->filter_taluka_id, fn ($q) => $q->where('taluka_id', $this->filter_taluka_id))
            ->when($this->filter_village_id, fn ($q) => $q->where('village_id', $this->filter_village_id))
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.supplier.view', [
            'suppliers' => $suppliers,
        ])->with(['title_name' => $this->title ?? 'Suppliers']);
    }
}
