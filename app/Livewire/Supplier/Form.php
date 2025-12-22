<?php

namespace App\Livewire\Supplier;

use App\Models\District;
use App\Models\State;
use App\Models\Supplier;
use App\Models\Taluka;
use App\Models\Village;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Suppliers';

    public ?int $supplierId = null;
    public $name;
    public $supplier_code;
    public $contact_person;
    public $mobile;
    public $email;
    public $gstin;
    public $state_id;
    public $district_id;
    public $taluka_id;
    public $village_id;
    public $address_line;
    public $pincode;
    public $account_name;
    public $account_number;
    public $ifsc;
    public $bank_name;
    public $branch;
    public $upi_id;
    public $notes;
    public $is_active = true;

    public $states = [];
    public $districts = [];
    public $talukas = [];
    public $villages = [];

    public function mount($supplier = null): void
    {
        $this->states = State::orderBy('name')->get();

        if ($supplier) {
            $record = Supplier::findOrFail($supplier);
            $this->authorize('supplier.update');
            $this->supplierId = $record->id;
            $this->fill($record->only([
                'name',
                'supplier_code',
                'contact_person',
                'mobile',
                'email',
                'gstin',
                'state_id',
                'district_id',
                'taluka_id',
                'village_id',
                'address_line',
                'pincode',
                'account_name',
                'account_number',
                'ifsc',
                'bank_name',
                'branch',
                'upi_id',
                'notes',
                'is_active',
            ]));

            $this->syncLocationFromVillage();
            $this->loadLocationOptions();
        } else {
            $this->authorize('supplier.create');
        }
    }

    public function updated($field): void
    {
        $this->resetErrorBag($field);
        session()->forget('success');
    }

    public function updatedStateId(): void
    {
        $this->districts = $this->state_id
            ? District::where('state_id', $this->state_id)->orderBy('name')->get()
            : collect();

        $this->talukas = collect();
        $this->villages = collect();
        $this->district_id = null;
        $this->taluka_id = null;
        $this->village_id = null;
    }

    public function updatedDistrictId(): void
    {
        $this->talukas = $this->district_id
            ? Taluka::where('district_id', $this->district_id)->orderBy('name')->get()
            : collect();

        $this->villages = collect();
        $this->taluka_id = null;
        $this->village_id = null;
    }

    public function updatedTalukaId(): void
    {
        $this->villages = $this->taluka_id
            ? Village::where('taluka_id', $this->taluka_id)->orderBy('name')->get()
            : collect();

        $this->village_id = null;
    }

    public function save()
    {
        $this->authorize($this->supplierId ? 'supplier.update' : 'supplier.create');
        $this->trimInputs();
        $this->syncLocationFromVillage();

        $data = $this->normalizeOptionalIds($this->validate($this->rules()));

        if ($this->supplierId) {
            Supplier::findOrFail($this->supplierId)->update($data);
            session()->flash('success', 'Supplier updated.');
        } else {
            Supplier::create($data);
            session()->flash('success', 'Supplier created.');
            $this->resetForm();
        }

        return redirect()->route('suppliers.view');
    }

    public function render()
    {
        return view('livewire.supplier.form')
            ->with(['title_name' => $this->title ?? 'Suppliers']);
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'supplier_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('suppliers', 'supplier_code')->ignore($this->supplierId),
            ],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'gstin' => [
                'nullable',
                'string',
                'size:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
                Rule::unique('suppliers', 'gstin')->ignore($this->supplierId)->whereNull('deleted_at'),
            ],
            'state_id' => ['nullable', 'exists:states,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'taluka_id' => ['nullable', 'exists:talukas,id'],
            'village_id' => ['nullable', 'exists:villages,id'],
            'address_line' => ['nullable', 'string', 'max:500'],
            'pincode' => ['nullable', 'regex:/^[0-9]{6}$/'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'regex:/^[0-9]{6,20}$/'],
            'ifsc' => ['nullable', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'branch' => ['nullable', 'string', 'max:255'],
            'upi_id' => ['nullable', 'regex:/^[\\w\\.\\-]{2,256}@[\\w]{2,64}$/i'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'name',
            'supplier_code',
            'contact_person',
            'mobile',
            'email',
            'gstin',
            'state_id',
            'district_id',
            'taluka_id',
            'village_id',
            'address_line',
            'pincode',
            'account_name',
            'account_number',
            'ifsc',
            'bank_name',
            'branch',
            'upi_id',
            'notes',
        ]);

        $this->is_active = true;
        $this->districts = $this->talukas = $this->villages = collect();
    }

    private function trimInputs(): void
    {
        $this->name = $this->name ? trim($this->name) : null;
        $this->supplier_code = $this->supplier_code ? trim($this->supplier_code) : null;
        $this->contact_person = $this->contact_person ? trim($this->contact_person) : null;
        $this->mobile = $this->mobile ? trim($this->mobile) : null;
        $this->email = $this->email ? trim($this->email) : null;
        $this->gstin = $this->gstin ? strtoupper(trim($this->gstin)) : null;
        $this->address_line = $this->address_line ? trim($this->address_line) : null;
        $this->pincode = $this->pincode ? trim($this->pincode) : null;
        $this->account_name = $this->account_name ? trim($this->account_name) : null;
        $this->account_number = $this->account_number ? trim($this->account_number) : null;
        $this->ifsc = $this->ifsc ? strtoupper(trim($this->ifsc)) : null;
        $this->bank_name = $this->bank_name ? trim($this->bank_name) : null;
        $this->branch = $this->branch ? trim($this->branch) : null;
        $this->upi_id = $this->upi_id ? trim($this->upi_id) : null;
        $this->notes = $this->notes ? trim($this->notes) : null;
    }

    private function syncLocationFromVillage(): void
    {
        if ($this->village_id) {
            $village = Village::with('taluka.district')->find($this->village_id);
            if ($village) {
                $this->taluka_id = $this->taluka_id ?? $village->taluka_id;
                $taluka = $village->taluka;
                $this->district_id = $this->district_id ?? $taluka?->district_id;
                $district = $taluka?->district;
                $this->state_id = $this->state_id ?? $district?->state_id;
            }
        }
    }

    private function loadLocationOptions(): void
    {
        $this->districts = $this->state_id
            ? District::where('state_id', $this->state_id)->orderBy('name')->get()
            : collect();

        $this->talukas = $this->district_id
            ? Taluka::where('district_id', $this->district_id)->orderBy('name')->get()
            : collect();

        $this->villages = $this->taluka_id
            ? Village::where('taluka_id', $this->taluka_id)->orderBy('name')->get()
            : collect();
    }

    private function normalizeOptionalIds(array $data): array
    {
        foreach (['state_id', 'district_id', 'taluka_id', 'village_id'] as $key) {
            if (empty($data[$key])) {
                $data[$key] = null;
            }
        }

        return $data;
    }
}
