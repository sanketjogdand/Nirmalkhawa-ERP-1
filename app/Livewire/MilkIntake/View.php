<?php

namespace App\Livewire\MilkIntake;

use App\Models\Center;
use App\Models\MilkIntake;
use App\Services\MilkRateCalculator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Milk Intake';
    public $perPage = 25;
    public $search = '';
    public $centerId = '';
    public $shift = '';
    public $milkType = '';
    public $fromDate = '';
    public $toDate = '';
    public array $selected = [];
    public bool $selectAll = false;

    public bool $showLockModal = false;
    public array $pendingLockIds = [];
    public bool $showUnlockModal = false;
    public array $pendingUnlockIds = [];
    public bool $showApplyModal = false;
    public array $pendingApplyIds = [];

    public bool $showOverrideModal = false;
    public ?int $overrideId = null;
    public $override_rate_per_ltr;
    public $override_reason;

    public Collection $centers;

    public function mount(): void
    {
        $this->authorize('milkintake.view');
        $this->centers = Center::orderBy('name')->get();
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function updating($field): void
    {
        if (in_array($field, ['search', 'centerId', 'shift', 'milkType', 'fromDate', 'toDate'])) {
            $this->resetPage();
            $this->selected = [];
            $this->selectAll = false;
        }
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selected = $this->baseQuery()->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function updatedSelected(): void
    {
        $currentIds = $this->baseQuery()->pluck('id')->toArray();
        if (empty($currentIds)) {
            $this->selectAll = false;
            return;
        }

        $this->selectAll = count(array_diff($currentIds, $this->selected)) === 0;
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $intakes = $this->baseQuery()
            ->latest('date')
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.milk-intake.view', compact('intakes'))
            ->with(['title_name' => $this->title ?? 'Milk Intake']);
    }

    public function startApplyRateChart(): void
    {
        $this->authorize('milkintake.apply_ratechart');

        $ids = $this->selected;
        if (! empty($ids)) {
            $manualCount = MilkIntake::whereIn('id', $ids)->where('rate_status', MilkIntake::STATUS_MANUAL)->count();
            $this->pendingApplyIds = $ids;
            if ($manualCount > 0) {
                $this->showApplyModal = true;
                return;
            }
        } else {
            $this->pendingApplyIds = [];
        }

        $this->applyRateChart(app(MilkRateCalculator::class), false);
    }

    public function applyRateChart(MilkRateCalculator $calculator, bool $includeManual = false): void
    {
        $this->authorize('milkintake.apply_ratechart');

        $query = $this->baseQuery()
            ->where('is_locked', false);

        if (! empty($this->pendingApplyIds)) {
            $query->whereIn('id', $this->pendingApplyIds);
            if (! $includeManual) {
                $query->where('rate_status', MilkIntake::STATUS_CALCULATED);
            } else {
                $query->whereIn('rate_status', [MilkIntake::STATUS_CALCULATED, MilkIntake::STATUS_MANUAL]);
            }
        } else {
            $query->where('rate_status', MilkIntake::STATUS_CALCULATED)
                ->where(function ($q) {
                    $q->whereNull('rate_per_ltr')->orWhere('rate_per_ltr', 0);
                });
        }

        $records = $query->get();

        foreach ($records as $intake) {
            $rate = null;

            try {
                $result = $calculator->calculate(
                    $intake->center_id,
                    $intake->milk_type,
                    $intake->date,
                    (float) $intake->fat_pct,
                    (float) $intake->snf_pct,
                );
                $rate = $result['final_rate'];
            } catch (RuntimeException $e) {
                $rate = null;
            }

            $intake->rate_status = MilkIntake::STATUS_CALCULATED;
            $intake->manual_rate_by = null;
            $intake->manual_rate_at = null;
            $intake->manual_rate_reason = null;
            $intake->syncDerivedAmounts($rate);
            $intake->rate_per_ltr = $rate;
            $intake->save();
        }

        $this->selected = [];
        $this->pendingApplyIds = [];
        $this->showApplyModal = false;
        session()->flash('success', 'Rate chart applied to matching records.');
    }

    public function confirmLock(?int $id = null): void
    {
        $this->authorize('milkintake.lock');
        $ids = $id ? [$id] : $this->selected;
        $this->pendingLockIds = MilkIntake::whereIn('id', $ids)
            ->where('is_locked', false)
            ->pluck('id')
            ->all();

        if (empty($this->pendingLockIds)) {
            session()->flash('danger', 'No unlocked records selected for locking.');
            return;
        }

        $this->showLockModal = true;
    }

    public function lockConfirmed(): void
    {
        $this->authorize('milkintake.lock');

        if (empty($this->pendingLockIds)) {
            $this->showLockModal = false;
            return;
        }

        MilkIntake::whereIn('id', $this->pendingLockIds)
            ->where('is_locked', false)
            ->update([
                'is_locked' => true,
                'locked_by' => auth()->id(),
                'locked_at' => now(),
            ]);

        $this->showLockModal = false;
        $this->pendingLockIds = [];
        $this->selected = [];
        session()->flash('success', 'Selected records locked.');
    }

    public function confirmUnlock(?int $id = null): void
    {
        $this->authorize('milkintake.unlock');
        $ids = $id ? [$id] : $this->selected;
        $this->pendingUnlockIds = MilkIntake::whereIn('id', $ids)
            ->where('is_locked', true)
            ->pluck('id')
            ->all();

        if (empty($this->pendingUnlockIds)) {
            session()->flash('danger', 'No locked records selected for unlocking.');
            return;
        }

        $this->showUnlockModal = true;
    }

    public function unlockConfirmed(): void
    {
        $this->authorize('milkintake.unlock');

        if (empty($this->pendingUnlockIds)) {
            $this->showUnlockModal = false;
            return;
        }

        MilkIntake::whereIn('id', $this->pendingUnlockIds)
            ->where('is_locked', true)
            ->update([
                'is_locked' => false,
                'locked_by' => null,
                'locked_at' => null,
            ]);

        $this->showUnlockModal = false;
        $this->pendingUnlockIds = [];
        $this->selected = [];
        session()->flash('success', 'Records unlocked.');
    }

    public function openOverride(int $id): void
    {
        $this->authorize('milkintake.rate.override');
        $intake = MilkIntake::findOrFail($id);

        if ($intake->is_locked) {
            session()->flash('danger', 'Locked records cannot be overridden.');
            return;
        }

        $this->overrideId = $intake->id;
        $this->override_rate_per_ltr = $intake->rate_per_ltr;
        $this->override_reason = $intake->manual_rate_reason;
        $this->showOverrideModal = true;
    }

    public function saveOverride(): void
    {
        $this->authorize('milkintake.rate.override');

        if (! $this->overrideId) {
            return;
        }

        $data = $this->validate([
            'override_rate_per_ltr' => ['required', 'numeric', 'gt:0'],
            'override_reason' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'override_rate_per_ltr' => 'Rate per liter',
        ]);

        $intake = MilkIntake::findOrFail($this->overrideId);

        if ($intake->is_locked) {
            session()->flash('danger', 'Locked records cannot be overridden.');
            return;
        }

        $intake->markManualRate((float) $data['override_rate_per_ltr'], $data['override_reason'], auth()->id());
        $intake->save();

        $this->overrideId = null;
        $this->override_rate_per_ltr = null;
        $this->override_reason = null;
        $this->showOverrideModal = false;
        $this->selected = [];

        session()->flash('success', 'Manual rate applied.');
    }

    public function cancelOverride(): void
    {
        $this->overrideId = null;
        $this->override_rate_per_ltr = null;
        $this->override_reason = null;
        $this->showOverrideModal = false;
    }

    private function baseQuery()
    {
        return MilkIntake::with(['center', 'manualRateUser', 'lockedByUser'])
            ->when($this->search, function ($query) {
                $query->whereHas('center', function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->centerId, fn ($q) => $q->where('center_id', $this->centerId))
            ->when($this->shift, fn ($q) => $q->where('shift', $this->shift))
            ->when($this->milkType, fn ($q) => $q->where('milk_type', $this->milkType))
            ->when($this->fromDate, fn ($q) => $q->where('date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->where('date', '<=', $this->toDate));
    }
}
