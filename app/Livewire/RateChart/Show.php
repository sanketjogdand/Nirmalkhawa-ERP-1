<?php

namespace App\Livewire\RateChart;

use App\Models\Center;
use App\Models\CenterRateChart;
use App\Models\RateChart;
use App\Models\RateChartSlab;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'Rate Charts';
    public RateChart $rateChart;

    public ?int $slabId = null;
    public $param_type = 'FAT';
    public $start_val;
    public $end_val;
    public $step = 0.10;
    public $rate_per_step;

    public ?int $assignmentId = null;
    public array $selectedCenters = [];
    public $assignment_effective_from;
    public $assignment_effective_to;
    public $assignment_is_active = true;
    public $centers = [];

    public function mount(RateChart $rateChart): void
    {
        $this->authorize('ratechart.view');
        $this->rateChart = $rateChart->load([
            'slabs' => fn ($q) => $q->orderBy('param_type')->orderBy('start_val'),
            'assignments.center',
        ]);
        $this->centers = Center::orderBy('name')->get();
    }

    public function startCreateSlab(string $type = 'FAT'): void
    {
        $this->authorize('ratechart.update');
        $this->resetSlabForm();
        $this->param_type = $type;
    }

    public function editSlab(int $slabId): void
    {
        $this->authorize('ratechart.update');
        $slab = RateChartSlab::where('rate_chart_id', $this->rateChart->id)->findOrFail($slabId);
        $this->slabId = $slab->id;
        $this->param_type = $slab->param_type;
        $this->start_val = $slab->start_val;
        $this->end_val = $slab->end_val;
        $this->step = $slab->step;
        $this->rate_per_step = $slab->rate_per_step;
    }

    public function saveSlab(): void
    {
        $this->authorize('ratechart.update');
        $data = $this->validate($this->slabRules(), [], [
            'start_val' => 'Start value',
            'end_val' => 'End value',
        ]);

        if ($data['start_val'] >= $data['end_val']) {
            $this->addError('end_val', 'End value must be greater than start value.');
            return;
        }

        if ($this->hasOverlappingSlab($data['param_type'], $data['start_val'], $data['end_val'], $this->slabId)) {
            $this->addError('start_val', 'Overlapping slab found for this parameter.');
            return;
        }

        $data['rate_chart_id'] = $this->rateChart->id;

        if ($this->slabId) {
            RateChartSlab::where('id', $this->slabId)->update($data);
            session()->flash('success', 'Slab updated.');
        } else {
            RateChartSlab::create($data);
            session()->flash('success', 'Slab added.');
        }

        $this->resetSlabForm();
        $this->refreshChart();
    }

    public function deleteSlab(int $slabId): void
    {
        $this->authorize('ratechart.update');
        RateChartSlab::where('rate_chart_id', $this->rateChart->id)
            ->where('id', $slabId)
            ->delete();

        $this->resetSlabForm();
        $this->refreshChart();
        session()->flash('success', 'Slab removed.');
    }

    public function saveAssignment(): void
    {
        $this->authorize('ratechart.assign');
        $data = $this->validate($this->assignmentRules(), [], [
            'selectedCenters' => 'Center',
            'assignment_effective_from' => 'Effective from',
            'assignment_effective_to' => 'Effective to',
        ]);

        if ($this->assignmentId && count($data['selectedCenters']) > 1) {
            $this->addError('selectedCenters', 'Only one center can be selected while editing.');
            return;
        }

        $from = Carbon::parse($data['assignment_effective_from'])->toDateString();
        $to = $data['assignment_effective_to'] ? Carbon::parse($data['assignment_effective_to'])->toDateString() : null;

        foreach ($data['selectedCenters'] as $centerId) {
            if ($this->hasOverlappingAssignment($centerId, $from, $to, $this->assignmentId)) {
                $this->addError('selectedCenters', 'Selected center already has an active rate chart in this period.');
                return;
            }
        }

        if ($this->assignmentId) {
            CenterRateChart::where('id', $this->assignmentId)->update([
                'center_id' => $data['selectedCenters'][0],
                'rate_chart_id' => $this->rateChart->id,
                'effective_from' => $from,
                'effective_to' => $to,
                'is_active' => (bool) $data['assignment_is_active'],
            ]);
            session()->flash('success', 'Assignment updated.');
        } else {
            foreach ($data['selectedCenters'] as $centerId) {
                CenterRateChart::create([
                    'center_id' => $centerId,
                    'rate_chart_id' => $this->rateChart->id,
                    'effective_from' => $from,
                    'effective_to' => $to,
                    'is_active' => (bool) $data['assignment_is_active'],
                ]);
            }
            session()->flash('success', 'Assignments saved.');
        }

        $this->resetAssignmentForm();
        $this->refreshChart();
    }

    public function editAssignment(int $assignmentId): void
    {
        $this->authorize('ratechart.assign');
        $assignment = CenterRateChart::where('rate_chart_id', $this->rateChart->id)->findOrFail($assignmentId);
        $this->assignmentId = $assignment->id;
        $this->selectedCenters = [$assignment->center_id];
        $this->assignment_effective_from = $assignment->effective_from?->toDateString();
        $this->assignment_effective_to = $assignment->effective_to?->toDateString();
        $this->assignment_is_active = $assignment->is_active;
    }

    public function toggleAssignment(int $assignmentId): void
    {
        $this->authorize('ratechart.assign');
        $assignment = CenterRateChart::where('rate_chart_id', $this->rateChart->id)->findOrFail($assignmentId);
        if ($assignment->is_active) {
            session()->flash('success', 'Assignment is already active.');
            return;
        }

        if ($this->hasOverlappingAssignment($assignment->center_id, $assignment->effective_from->toDateString(), $assignment->effective_to?->toDateString(), $assignment->id)) {
            session()->flash('error', 'Cannot activate due to another active rate chart for this center in the same period.');
            return;
        }

        $assignment->update(['is_active' => true]);
        $this->refreshChart();
        session()->flash('success', 'Assignment activated.');
    }

    public function deleteAssignment(int $assignmentId): void
    {
        $this->authorize('ratechart.assign');
        CenterRateChart::where('rate_chart_id', $this->rateChart->id)
            ->where('id', $assignmentId)
            ->delete();

        $this->refreshChart();
        $this->resetAssignmentForm();
        session()->flash('success', 'Assignment deleted.');
    }

    public function resetSlabForm(): void
    {
        $this->slabId = null;
        $this->param_type = 'FAT';
        $this->start_val = null;
        $this->end_val = null;
        $this->step = 0.10;
        $this->rate_per_step = null;
    }

    public function resetAssignmentForm(): void
    {
        $this->assignmentId = null;
        $this->selectedCenters = [];
        $this->assignment_effective_from = null;
        $this->assignment_effective_to = null;
        $this->assignment_is_active = true;
    }

    public function render()
    {
        return view('livewire.rate-chart.show')
            ->with(['title_name' => $this->title ?? 'Rate Charts']);
    }

    protected function slabRules(): array
    {
        return [
            'param_type' => ['required', Rule::in(['FAT', 'SNF'])],
            'start_val' => ['required', 'numeric'],
            'end_val' => ['required', 'numeric'],
            'step' => ['required', 'numeric', 'gt:0'],
            'rate_per_step' => ['required', 'numeric'],
        ];
    }

    protected function assignmentRules(): array
    {
        return [
            'selectedCenters' => ['required', 'array', 'min:1'],
            'selectedCenters.*' => ['required', 'exists:centers,id'],
            'assignment_effective_from' => ['required', 'date'],
            'assignment_effective_to' => ['nullable', 'date', 'after_or_equal:assignment_effective_from'],
            'assignment_is_active' => ['boolean'],
        ];
    }

    protected function refreshChart(): void
    {
        $this->rateChart = $this->rateChart->fresh([
            'slabs' => fn ($q) => $q->orderBy('param_type')->orderBy('start_val'),
            'assignments.center',
        ]);
    }

    private function hasOverlappingSlab(string $paramType, float $start, float $end, ?int $ignoreId = null): bool
    {
        return $this->rateChart->slabs()
            ->where('param_type', $paramType)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($query) use ($start, $end) {
                $query->where('start_val', '<', $end)
                    ->where('end_val', '>', $start);
            })
            ->exists();
    }

    private function hasOverlappingAssignment(int $centerId, string $from, ?string $to, ?int $ignoreId = null): bool
    {
        $toDate = $to ?? '9999-12-31';

        return CenterRateChart::query()
            ->where('center_id', $centerId)
            ->where('is_active', true)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('effective_from', '<=', $toDate)
            ->where(function ($q) use ($from) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $from);
            })
            ->exists();
    }
}
