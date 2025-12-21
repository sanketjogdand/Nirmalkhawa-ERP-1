<?php

namespace App\Livewire\Customer;

use App\Models\Customer;
use App\Models\District;
use App\Models\State;
use App\Models\Taluka;
use App\Models\Village;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Customers';

    public ?int $customerId = null;
    public $name;
    public $customer_code;
    public $mobile;
    public $gstin;
    public $state_id;
    public $district_id;
    public $taluka_id;
    public $village_id;
    public $address_line;
    public $pincode;
    public $is_active = true;

    public $states = [];
    public $districts = [];
    public $talukas = [];
    public $villages = [];

    public function mount($customer = null): void
    {
        $this->states = State::orderBy('name')->get();

        if ($customer) {
            $record = Customer::findOrFail($customer);
            $this->authorize('customer.update');
            $this->customerId = $record->id;
            $this->fill($record->only([
                'name',
                'customer_code',
                'mobile',
                'gstin',
                'state_id',
                'district_id',
                'taluka_id',
                'village_id',
                'address_line',
                'pincode',
                'is_active',
            ]));

            $this->syncLocationFromVillage();
            $this->loadLocationOptions();
        } else {
            $this->authorize('customer.create');
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
        $this->authorize($this->customerId ? 'customer.update' : 'customer.create');
        $this->trimInputs();
        $this->syncLocationFromVillage();

        $data = $this->normalizeOptionalIds($this->validate($this->rules()));

        if ($this->customerId) {
            Customer::findOrFail($this->customerId)->update($data);
            session()->flash('success', 'Customer updated.');
        } else {
            Customer::create($data);
            session()->flash('success', 'Customer created.');
            $this->resetForm();
        }

        return redirect()->route('customers.view');
    }

    public function render()
    {
        return view('livewire.customer.form')
            ->with(['title_name' => $this->title ?? 'Customers']);
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'customer_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('customers', 'customer_code')->ignore($this->customerId),
            ],
            'mobile' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'gstin' => [
                'nullable',
                'string',
                'size:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
                Rule::unique('customers', 'gstin')->ignore($this->customerId)->whereNull('deleted_at'),
            ],
            'state_id' => ['nullable', 'exists:states,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'taluka_id' => ['nullable', 'exists:talukas,id'],
            'village_id' => ['nullable', 'exists:villages,id'],
            'address_line' => ['nullable', 'string', 'max:500'],
            'pincode' => ['nullable', 'regex:/^[0-9]{6}$/'],
            'is_active' => ['boolean'],
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'name',
            'customer_code',
            'mobile',
            'gstin',
            'state_id',
            'district_id',
            'taluka_id',
            'village_id',
            'address_line',
            'pincode',
        ]);

        $this->is_active = true;
        $this->districts = $this->talukas = $this->villages = collect();
    }

    private function trimInputs(): void
    {
        $this->name = $this->name ? trim($this->name) : null;
        $this->customer_code = $this->customer_code ? trim($this->customer_code) : null;
        $this->mobile = $this->mobile ? trim($this->mobile) : null;
        $this->gstin = $this->gstin ? strtoupper(trim($this->gstin)) : null;
        $this->address_line = $this->address_line ? trim($this->address_line) : null;
        $this->pincode = $this->pincode ? trim($this->pincode) : null;
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
