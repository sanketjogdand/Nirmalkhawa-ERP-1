<?php

namespace App\Livewire\Employee;

use App\Models\District;
use App\Models\Employee;
use App\Models\State;
use App\Models\Taluka;
use App\Models\Village;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Employees';

    public $employeeId;
    public $employee_code;
    public $name;
    public $mobile;
    public $designation;
    public $department;
    public $state_id;
    public $district_id;
    public $taluka_id;
    public $village_id;
    public $address_line;
    public $notes;
    public $firstJoining;
    public $lastResignation;

    public $states = [];
    public $districts = [];
    public $talukas = [];
    public $villages = [];
    public $departments = [];
    public $designationsByDepartment = [];
    public $designationOptions = [];

    public function mount($employee = null): void
    {
        $this->states = State::orderBy('name')->get();
        $this->designationsByDepartment = config('employees.departments', []);
        $this->departments = array_keys($this->designationsByDepartment);

        if ($employee) {
            $record = Employee::findOrFail($employee);
            $this->authorize('employee.update');
            $this->employeeId = $record->id;
            $this->fill($record->only([
                'employee_code',
                'name',
                'mobile',
                'designation',
                'department',
                'state_id',
                'district_id',
                'taluka_id',
                'village_id',
                'address_line',
                'notes',
            ]));
            $this->firstJoining = $record->joining_date?->format('d M Y');
            $this->lastResignation = $record->resignation_date?->format('d M Y');
            $this->updateDesignationOptions($this->department);
            $this->syncLocationFromVillage();
            $this->loadLocationOptions();
        } else {
            $this->authorize('employee.create');
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

    public function updateDepartment(): void
    {
        $this->updateDesignationOptions($this->department);
        $this->designation = null;
    }

    public function save()
    {
        $this->trimInputs();
        $this->syncLocationFromVillage();
        $data = $this->normalizeOptionalIds($this->validate());

        if ($this->employeeId) {
            Employee::where('id', $this->employeeId)->update($data);
            session()->flash('success', 'Employee updated.');
            $employeeId = $this->employeeId;
        } else {
            $data['created_by'] = Auth::id();
            $employee = Employee::create($data);
            $this->employeeId = $employee->id;
            $employeeId = $employee->id;
            session()->flash('success', 'Employee created.');
        }

        return redirect()->route('employees.employment-periods', [
            'employee' => $employeeId,
            'add' => 1,
        ]);
    }

    public function render()
    {
        return view('livewire.employee.form')
            ->with(['title_name' => $this->title ?? 'Employees']);
    }

    protected function rules(): array
    {
        $departmentRule = empty($this->departments)
            ? ['nullable', 'string', 'max:150']
            : ['nullable', Rule::in($this->departments)];

        $designationRule = empty($this->designationOptions)
            ? ['nullable', 'string', 'max:150']
            : ['nullable', Rule::in($this->designationOptions)];

        return [
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_code')->ignore($this->employeeId)],
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'designation' => $designationRule,
            'department' => $departmentRule,
            'state_id' => ['nullable', 'exists:states,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'taluka_id' => ['nullable', 'exists:talukas,id'],
            'village_id' => ['nullable', 'exists:villages,id'],
            'address_line' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
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
        $this->employee_code = $this->employee_code ? trim($this->employee_code) : null;
        $this->name = $this->name ? trim($this->name) : null;
        $this->mobile = $this->mobile ? trim($this->mobile) : null;
    }

    private function updateDesignationOptions(?string $department): void
    {
        $this->designationOptions = $department && isset($this->designationsByDepartment[$department])
            ? $this->designationsByDepartment[$department]
            : [];
    }
}
