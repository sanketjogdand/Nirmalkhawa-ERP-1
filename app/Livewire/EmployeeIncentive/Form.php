<?php

namespace App\Livewire\EmployeeIncentive;

use App\Models\Employee;
use App\Models\EmployeeIncentive;
use App\Services\EmploymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Incentives';

    public ?int $incentiveId = null;
    public $employee_id;
    public $incentive_date;
    public $incentive_type;
    public $amount;
    public $remarks;
    public bool $isLocked = false;

    public $employees = [];

    public function mount($incentive = null): void
    {
        $this->employees = Employee::orderBy('name')->get();

        if ($incentive) {
            $record = EmployeeIncentive::findOrFail($incentive);
            $this->authorize('incentive.update');
            $this->incentiveId = $record->id;
            $this->fill($record->only([
                'employee_id',
                'incentive_date',
                'incentive_type',
                'amount',
                'remarks',
            ]));
            $this->incentive_date = $record->incentive_date?->toDateString();
            $this->isLocked = (bool) $record->is_locked;
        } else {
            $this->authorize('incentive.create');
            $this->incentive_date = now()->toDateString();
        }
    }

    public function save(EmploymentService $employmentService)
    {
        $this->authorize($this->incentiveId ? 'incentive.update' : 'incentive.create');

        if ($this->incentiveId) {
            $record = EmployeeIncentive::findOrFail($this->incentiveId);
            if ($record->is_locked) {
                abort(403, 'Incentive is locked and cannot be edited.');
            }
        }

        $data = $this->validate($this->rules());

        if (! $employmentService->isEmployedOn($data['employee_id'], $data['incentive_date'])) {
            $this->addError('incentive_date', 'Incentive date must fall within an employment period.');
            return;
        }

        if ($this->incentiveId) {
            EmployeeIncentive::where('id', $this->incentiveId)->update($data);
            session()->flash('success', 'Incentive updated.');
        } else {
            $data['created_by'] = Auth::id();
            EmployeeIncentive::create($data);
            session()->flash('success', 'Incentive added.');
            $this->reset(['employee_id', 'incentive_date', 'incentive_type', 'amount', 'remarks']);
            $this->incentive_date = now()->toDateString();
        }

        return redirect()->route('employee-incentives.view');
    }

    public function render()
    {
        return view('livewire.employee-incentive.form')
            ->with(['title_name' => $this->title ?? 'Incentives']);
    }

    private function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'incentive_date' => ['required', 'date'],
            'incentive_type' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
