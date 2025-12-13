<?php

namespace App\Livewire\MilkIntake;

use App\Models\Center;
use App\Models\MilkIntake;
use App\Models\StockLedger;
use App\Services\MilkRateCalculator;
use App\Services\InventoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;
use RuntimeException;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Milk Intake';

    public ?int $milkIntakeId = null;
    public $center_id;
    public $date;
    public $shift = MilkIntake::SHIFT_MORNING;
    public $milk_type = 'CM';
    public $qty_ltr;
    public $density_factor = 1.03;
    public $fat_pct;
    public $snf_pct;
    public $rate_per_ltr;
    public $amount;
    public $qty_kg;
    public $kg_fat;
    public $kg_snf;
    public $rate_status = MilkIntake::STATUS_CALCULATED;

    public Collection $centers;
    public bool $keepManualRate = false;
    public ?string $rateMessage = null;

    public function mount($milkIntake = null): void
    {
        $this->centers = Center::orderBy('name')->get();

        if ($milkIntake) {
            $record = MilkIntake::findOrFail($milkIntake);
            $this->authorize('milkintake.update');

            if ($record->is_locked) {
                abort(403, 'Milk intake is locked and cannot be edited.');
            }

            $this->milkIntakeId = $record->id;
            $this->fill($record->only([
                'center_id',
                'shift',
                'milk_type',
                'qty_ltr',
                'density_factor',
                'fat_pct',
                'snf_pct',
                'rate_per_ltr',
                'amount',
                'qty_kg',
                'kg_fat',
                'kg_snf',
                'rate_status',
            ]));
            $this->date = $record->date ? $record->date->toDateString() : null;

            $this->keepManualRate = $record->rate_status === MilkIntake::STATUS_MANUAL;
            $this->refreshDerived();
        } else {
            $this->authorize('milkintake.create');
            $this->date = now()->toDateString();
            $this->density_factor = $this->density_factor ?? 1.03;
        }
    }

    public function updated($property): void
    {
        if (in_array($property, ['qty_ltr', 'density_factor', 'fat_pct', 'snf_pct', 'rate_per_ltr'])) {
            $this->refreshDerived();
        }

        if (in_array($property, ['center_id', 'milk_type', 'date', 'fat_pct', 'snf_pct'])) {
            $this->refreshRate();
        }
    }

    public function save(MilkRateCalculator $calculator, InventoryService $inventoryService)
    {
        $this->authorize($this->milkIntakeId ? 'milkintake.update' : 'milkintake.create');
        $data = $this->validate($this->rules(), [], [
            'center_id' => 'Center',
            'qty_ltr' => 'Quantity (Liters)',
            'fat_pct' => 'FAT %',
            'snf_pct' => 'SNF %',
        ]);

        $rate = $this->keepManualRate ? ($this->rate_per_ltr !== null ? (float) $this->rate_per_ltr : null) : null;

        if (! $this->keepManualRate) {
            try {
                $calculated = $calculator->calculate(
                    (int) $data['center_id'],
                    $data['milk_type'],
                    $data['date'],
                    (float) $data['fat_pct'],
                    (float) $data['snf_pct'],
                );
                $rate = $calculated['final_rate'];
                $this->rateMessage = null;
            } catch (RuntimeException $e) {
                $rate = null;
                $this->rateMessage = $e->getMessage();
            }
        }

        $metrics = MilkIntake::computeMetrics(
            (float) $data['qty_ltr'],
            (float) $data['density_factor'],
            (float) $data['fat_pct'],
            (float) $data['snf_pct'],
            $rate
        );

        $payload = array_merge($data, $metrics, [
            'rate_per_ltr' => $rate,
            'rate_status' => $this->keepManualRate ? MilkIntake::STATUS_MANUAL : MilkIntake::STATUS_CALCULATED,
        ]);

        DB::transaction(function () use ($payload, $inventoryService) {
            if ($this->milkIntakeId) {
                $record = MilkIntake::findOrFail($this->milkIntakeId);

                if ($record->is_locked) {
                    abort(403, 'Milk intake is locked and cannot be edited.');
                }

                $record->update($payload);
                $inventoryService->reverseReference(MilkIntake::class, $record->id, 'Milk intake updated - reversal');
                $record->refresh();
                $this->postLedger($inventoryService, $record);
                session()->flash('success', 'Milk intake updated.');
            } else {
                $record = MilkIntake::create($payload);
                $this->milkIntakeId = $record->id;
                $this->postLedger($inventoryService, $record);
                session()->flash('success', 'Milk intake saved.');
            }
        });

        return redirect()->route('milk-intakes.view');
    }

    public function render()
    {
        return view('livewire.milk-intake.form')
            ->with(['title_name' => $this->title ?? 'Milk Intake']);
    }

    private function refreshRate(): void
    {
        if ($this->keepManualRate || ! $this->center_id || ! $this->date || ! $this->milk_type || $this->fat_pct === null || $this->snf_pct === null) {
            return;
        }

        try {
            $calculated = app(MilkRateCalculator::class)->calculate(
                (int) $this->center_id,
                $this->milk_type,
                $this->date,
                (float) $this->fat_pct,
                (float) $this->snf_pct,
            );
            $this->rate_per_ltr = $calculated['final_rate'];
            $this->rate_status = MilkIntake::STATUS_CALCULATED;
            $this->rateMessage = null;
        } catch (RuntimeException $e) {
            $this->rate_per_ltr = null;
            $this->rateMessage = $e->getMessage();
        }

        $this->refreshDerived();
    }

    private function refreshDerived(): void
    {
        if ($this->qty_ltr === null || $this->density_factor === null || $this->fat_pct === null || $this->snf_pct === null) {
            return;
        }

        $metrics = MilkIntake::computeMetrics(
            (float) $this->qty_ltr,
            (float) $this->density_factor,
            (float) $this->fat_pct,
            (float) $this->snf_pct,
            $this->rate_per_ltr !== null ? (float) $this->rate_per_ltr : null
        );

        $this->qty_kg = $metrics['qty_kg'];
        $this->kg_fat = $metrics['kg_fat'];
        $this->kg_snf = $metrics['kg_snf'];
        $this->amount = $metrics['amount'];
    }

    private function rules(): array
    {
        return [
            'center_id' => ['required', 'exists:centers,id'],
            'date' => ['required', 'date'],
            'shift' => ['required', Rule::in([MilkIntake::SHIFT_MORNING, MilkIntake::SHIFT_EVENING])],
            'milk_type' => ['required', Rule::in(['CM', 'BM'])],
            'qty_ltr' => ['required', 'numeric', 'gt:0'],
            'density_factor' => ['required', 'numeric', 'gt:0'],
            'fat_pct' => ['required', 'numeric', 'between:0,99.99'],
            'snf_pct' => ['required', 'numeric', 'between:0,99.99'],
        ];
    }

    private function postLedger(InventoryService $inventoryService, MilkIntake $intake): void
    {
        $product = $inventoryService->getMilkProduct($intake->milk_type);
        $dateString = $intake->date instanceof \Illuminate\Support\Carbon
            ? $intake->date->toDateString()
            : (string) $intake->date;
        $timestamp = $dateString.' '.($intake->shift === MilkIntake::SHIFT_EVENING ? '18:00:00' : '06:00:00');

        $inventoryService->postIn($product->id, (float) $intake->qty_ltr, StockLedger::TYPE_IN, [
            'txn_datetime' => $timestamp,
            'remarks' => 'Milk intake '.$intake->milk_type.' for center '.$intake->center_id,
            'reference_type' => MilkIntake::class,
            'reference_id' => $intake->id,
        ]);
    }
}
