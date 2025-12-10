<?php

namespace App\Livewire\Center;

use App\Models\Center;
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

    public $title = 'Centers';

    public $centerId;
    public $name;
    public $code;
    public $address;
    public $state_id;
    public $district_id;
    public $taluka_id;
    public $village_id;
    public $contact_person;
    public $mobile;
    public $account_name;
    public $account_number;
    public $ifsc;
    public $branch;
    public $status = 'Active';

    public $states = [];
    public $districts = [];
    public $talukas = [];
    public $villages = [];

    public function mount($center = null): void
    {
        $this->states = State::orderBy('name')->get();

        if ($center) {
            $rec = Center::findOrFail($center);
            $this->authorize('center.update');
            $this->centerId = $rec->id;
            $this->fill($rec->toArray());
            $this->syncLocationFromVillage();
            $this->loadLocationOptions();
        } else {
            $this->authorize('center.create');
        }
    }

    public function updateStateId(): void
    {
        $this->districts = District::where('state_id', $this->state_id)->orderBy('name')->get();
        $this->talukas = [];
        $this->villages = [];
        $this->district_id = null;
        $this->taluka_id = null;
        $this->village_id = null;
    }

    public function updateDistrictId(): void
    {
        $this->talukas = Taluka::where('district_id', $this->district_id)->orderBy('name')->get();
        $this->villages = [];
        $this->taluka_id = null;
        $this->village_id = null;
    }

    public function updateTalukaId(): void
    {
        $this->villages = Village::where('taluka_id', $this->taluka_id)->orderBy('name')->get();
        $this->village_id = null;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('centers', 'code')->ignore($this->centerId)],
            'address' => ['nullable', 'string', 'max:1000'],
            'state_id' => ['nullable', 'exists:states,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'taluka_id' => ['nullable', 'exists:talukas,id'],
            'village_id' => ['nullable', 'exists:villages,id'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'mobile' => ['required', 'digits:10'],
            'account_name' => ['nullable', 'string', 'max:150', 'required_with:account_number,ifsc,branch'],
            'account_number' => ['nullable', 'string', 'max:50', 'required_with:account_name,ifsc,branch'],
            'ifsc' => ['nullable', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/', 'max:11', 'required_with:account_name,account_number,branch'],
            'branch' => ['nullable', 'string', 'max:150', 'required_with:account_name,account_number,ifsc'],
            'status' => ['required', 'in:Active,Inactive'],
        ];
    }

    public function save()
    {
        $this->trimInputs();
        $this->syncLocationFromVillage();
        $data = $this->normalizeOptionalIds($this->validate());

        if ($this->centerId) {
            Center::where('id', $this->centerId)->update($data);
            session()->flash('success', 'Center updated!');
        } else {
            Center::create($data);
            session()->flash('success', 'Center added!');
            $this->reset([
                'name', 'code', 'address', 'state_id', 'district_id', 'taluka_id', 'village_id',
                'contact_person', 'mobile', 'account_name', 'account_number', 'ifsc', 'branch',
            ]);
            $this->status = 'Active';
            $this->districts = $this->talukas = $this->villages = [];
        }

        return redirect()->route('centers.view');
    }

    public function render()
    {
        return view('livewire.center.form')
            ->with(['title_name' => $this->title ?? 'KCB Industries Pvt. Ltd.']);
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

    private function trimInputs(): void
    {
        $this->code = $this->code ? trim($this->code) : null;
        $this->name = $this->name ? trim($this->name) : null;
        $this->mobile = $this->mobile ? trim($this->mobile) : null;
    }
}
