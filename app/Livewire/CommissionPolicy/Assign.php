<?php

namespace App\Livewire\CommissionPolicy;

use App\Models\Center;
use App\Models\CenterCommissionAssignment;
use App\Models\CommissionPolicy;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Assign extends Component
{
    use AuthorizesRequests;

    public $title = 'Commission Assignments';
    public $commission_policy_id;
    public array $selectedCenters = [];
    public $effective_from;
    public $effective_to;
    public $is_active = true;
    public $assignments;
    public $centers;
    public $policies;
    public ?int $editId = null;

    public function mount(): void
    {
        $this->authorize('commissionassignment.view');
        $this->centers = Center::orderBy('name')->get();
        $this->policies = CommissionPolicy::orderBy('code')->get();
        $this->loadAssignments();
    }

    public function save(): void
    {
        $this->authorize('commissionassignment.create');
        $data = $this->validate($this->rules(), [], [
            'commission_policy_id' => 'Commission Policy',
            'selectedCenters' => 'Center',
            'effective_from' => 'Effective from',
            'effective_to' => 'Effective to',
        ]);

        if ($this->editId && count($data['selectedCenters']) > 1) {
            $this->addError('selectedCenters', 'Only one center can be selected while editing.');
            return;
        }

        foreach ($data['selectedCenters'] as $centerId) {
            if ($this->hasOverlap($centerId, $data['commission_policy_id'], $data['effective_from'], $data['effective_to'], $this->editId)) {
                $this->addError('selectedCenters', 'Overlapping commission policy for this center and milk type.');
                return;
            }
        }

        if ($this->editId) {
            CenterCommissionAssignment::where('id', $this->editId)->update([
                'center_id' => $data['selectedCenters'][0],
                'commission_policy_id' => $data['commission_policy_id'],
                'effective_from' => $data['effective_from'],
                'effective_to' => $data['effective_to'],
                'is_active' => (bool) $data['is_active'],
            ]);
        } else {
            foreach ($data['selectedCenters'] as $centerId) {
                CenterCommissionAssignment::create([
                    'center_id' => $centerId,
                    'commission_policy_id' => $data['commission_policy_id'],
                    'effective_from' => $data['effective_from'],
                    'effective_to' => $data['effective_to'],
                    'is_active' => (bool) $data['is_active'],
                ]);
            }
        }

        session()->flash('success', 'Assignment saved.');
        $this->resetForm();
        $this->loadAssignments();
    }

    public function edit(int $id): void
    {
        $this->authorize('commissionassignment.update');
        $assignment = CenterCommissionAssignment::with('commissionPolicy')->findOrFail($id);
        $this->editId = $assignment->id;
        $this->selectedCenters = [$assignment->center_id];
        $this->commission_policy_id = $assignment->commission_policy_id;
        $this->effective_from = $assignment->effective_from?->toDateString();
        $this->effective_to = $assignment->effective_to?->toDateString();
        $this->is_active = $assignment->is_active;
    }

    public function resetForm(): void
    {
        $this->editId = null;
        $this->selectedCenters = [];
        $this->commission_policy_id = null;
        $this->effective_from = null;
        $this->effective_to = null;
        $this->is_active = true;
    }

    public function render()
    {
        return view('livewire.commission-policy.assign')
            ->with(['title_name' => $this->title ?? 'Commission Assignments']);
    }

    protected function rules(): array
    {
        return [
            'commission_policy_id' => ['required', 'exists:commission_policies,id'],
            'selectedCenters' => ['required', 'array', 'min:1'],
            'selectedCenters.*' => ['required', 'exists:centers,id'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['boolean'],
        ];
    }

    private function hasOverlap(int $centerId, int $policyId, string $from, ?string $to, ?int $ignoreId = null): bool
    {
        $policy = CommissionPolicy::find($policyId);
        $milkType = $policy?->milk_type;
        $toDate = $to ?? '9999-12-31';

        return CenterCommissionAssignment::query()
            ->where('center_id', $centerId)
            ->where('is_active', true)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereHas('commissionPolicy', fn ($q) => $milkType ? $q->where('milk_type', $milkType) : $q)
            ->where('effective_from', '<=', $toDate)
            ->where(function ($q) use ($from) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $from);
            })
            ->exists();
    }

    private function loadAssignments(): void
    {
        $this->assignments = CenterCommissionAssignment::with(['center', 'commissionPolicy'])->latest()->get();
    }
}
