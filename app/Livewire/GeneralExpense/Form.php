<?php

namespace App\Livewire\GeneralExpense;

use App\Models\ExpenseCategory;
use App\Models\GeneralExpense;
use App\Models\Supplier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public $title = 'General Expenses';

    public ?int $expenseId = null;
    public bool $isLocked = false;

    public $expense_date;
    public $supplier_id;
    public $invoice_no;
    public $remarks;
    public $attachment;
    public ?string $attachment_path = null;

    public float $taxable_total = 0;
    public float $gst_total = 0;
    public float $grand_total = 0;

    public array $lines = [];
    public array $gstRates = [0, 5, 18];

    public $suppliers;
    public $categories;

    public function mount($expense = null): void
    {
        $this->suppliers = Supplier::orderBy('name')->get();
        $this->categories = ExpenseCategory::orderBy('name')->get();

        if ($expense) {
            $record = GeneralExpense::with(['lines'])->findOrFail($expense);
            $this->authorize('general_expense.update');

            $this->expenseId = $record->id;
            $this->expense_date = $record->expense_date?->toDateString();
            $this->supplier_id = $record->supplier_id;
            $this->invoice_no = $record->invoice_no;
            $this->remarks = $record->remarks;
            $this->attachment_path = $record->attachment_path;
            $this->isLocked = (bool) $record->is_locked;

            $this->lines = $record->lines->map(function ($line) {
                return [
                    'category_id' => $line->category_id,
                    'description' => $line->description,
                    'qty' => (float) $line->qty,
                    'rate' => $line->rate !== null ? (float) $line->rate : null,
                    'taxable_amount' => (float) $line->taxable_amount,
                    'gst_rate' => $line->gst_rate !== null ? (float) $line->gst_rate : null,
                    'gst_amount' => (float) $line->gst_amount,
                    'total_amount' => (float) $line->total_amount,
                    'is_rcm_applicable' => (bool) $line->is_rcm_applicable,
                    'vendor_id' => $line->vendor_id,
                    'vendor_name' => $line->vendor_name,
                    'vendor_invoice_no' => $line->vendor_invoice_no,
                    'vendor_invoice_date' => $line->vendor_invoice_date?->toDateString(),
                    'vendor_gstin' => $line->vendor_gstin,
                ];
            })->toArray();
        } else {
            $this->authorize('general_expense.create');
            $this->expense_date = now()->toDateString();
            $this->lines = [
                $this->blankLine(),
            ];
        }

        $this->refreshTotals();
    }

    public function updated($name, $value): void
    {
        if (str_starts_with($name, 'lines.')) {
            $this->updatedLines($value, $name);
        }
    }

    public function updatedLines($value, $name): void
    {
        if (! str_starts_with($name, 'lines.')) {
            return;
        }

        $parts = explode('.', $name);
        $index = (int) ($parts[1] ?? 0);
        $field = $parts[2] ?? null;

        if (! isset($this->lines[$index])) {
            return;
        }

        if ($field === 'vendor_id') {
            $currentGstin = $this->lines[$index]['vendor_gstin'] ?? null;
            if ($currentGstin === null || $currentGstin === '') {
                $vendorId = $this->lines[$index]['vendor_id'] ?? null;
                if ($vendorId) {
                    $supplier = $this->suppliers->firstWhere('id', (int) $vendorId);
                    if ($supplier && $supplier->gstin) {
                        $this->lines[$index]['vendor_gstin'] = $supplier->gstin;
                    }
                }
            }
        }

        $this->recalculateLine($index, $field);
        $this->refreshTotals();
    }

    public function addLine(): void
    {
        if ($this->isLocked) {
            return;
        }

        $this->lines[] = $this->blankLine();
    }

    public function removeLine(int $index): void
    {
        if ($this->isLocked) {
            return;
        }

        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
        $this->refreshTotals();
    }

    public function save()
    {
        $this->authorize($this->expenseId ? 'general_expense.update' : 'general_expense.create');

        if ($this->isLocked) {
            abort(403, 'Expense is locked and cannot be edited.');
        }

        $validated = $this->validate($this->rules(), [], $this->attributes());

        $computedLines = [];
        $taxableTotal = 0;
        $gstTotal = 0;

        foreach ($validated['lines'] as $line) {
            $qty = $line['qty'] !== null ? (float) $line['qty'] : 1;
            $rate = $line['rate'] !== null ? (float) $line['rate'] : null;
            $taxable = round((float) $line['taxable_amount'], 2);
            $gstRate = $line['gst_rate'] !== null ? (float) $line['gst_rate'] : null;
            $gstAmount = round($taxable * (($gstRate ?? 0) / 100), 2);
            $totalAmount = round($taxable + $gstAmount, 2);

            $taxableTotal += $taxable;
            $gstTotal += $gstAmount;

            $computedLines[] = [
                'category_id' => (int) $line['category_id'],
                'description' => $line['description'] ?? null,
                'qty' => $qty,
                'rate' => $rate,
                'taxable_amount' => $taxable,
                'gst_rate' => $gstRate,
                'gst_amount' => $gstAmount,
                'total_amount' => $totalAmount,
                'place_of_supply_state_id' => null,
                'is_rcm_applicable' => (bool) ($line['is_rcm_applicable'] ?? false),
                'vendor_id' => $line['vendor_id'] ?? null,
                'vendor_name' => $line['vendor_name'] ?? null,
                'vendor_invoice_no' => $line['vendor_invoice_no'] ?? null,
                'vendor_invoice_date' => $line['vendor_invoice_date'] ?? null,
                'vendor_gstin' => $line['vendor_gstin'] ?? null,
            ];
        }

        $grandTotal = $taxableTotal + $gstTotal;
        $oldAttachment = $this->attachment_path;
        $newAttachment = $this->attachment
            ? $this->attachment->store('general-expenses', 'public')
            : $this->attachment_path;

        $isNew = ! $this->expenseId;

        DB::transaction(function () use ($validated, $computedLines, $taxableTotal, $gstTotal, $grandTotal, $newAttachment) {
            $payload = [
                'expense_date' => $validated['expense_date'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'invoice_no' => $validated['invoice_no'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'attachment_path' => $newAttachment,
            ];

            if ($this->expenseId) {
                $expense = GeneralExpense::findOrFail($this->expenseId);
                if ($expense->is_locked) {
                    abort(403, 'Expense is locked and cannot be edited.');
                }

                $expense->update($payload);
                $expense->lines()->delete();
                $expense->lines()->createMany($computedLines);
            } else {
                $payload['created_by'] = Auth::id();
                $expense = GeneralExpense::create($payload);
                $expense->lines()->createMany($computedLines);
                $this->expenseId = $expense->id;
            }
        });

        if ($this->attachment && $oldAttachment && $oldAttachment !== $newAttachment) {
            Storage::disk('public')->delete($oldAttachment);
        }

        $this->taxable_total = $taxableTotal;
        $this->gst_total = $gstTotal;
        $this->grand_total = $grandTotal;

        session()->flash('success', $isNew ? 'Expense created.' : 'Expense saved.');

        return redirect()->route('general-expenses.view');
    }

    public function render()
    {
        return view('livewire.general-expense.form')
            ->with(['title_name' => $this->title ?? 'General Expenses']);
    }

    private function rules(): array
    {
        return [
            'expense_date' => ['required', 'date'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'invoice_no' => ['nullable', 'string', 'max:120'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'max:5120'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.category_id' => ['required', 'exists:expense_categories,id'],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
            'lines.*.qty' => ['nullable', 'numeric', 'min:0'],
            'lines.*.rate' => ['nullable', 'numeric', 'min:0'],
            'lines.*.taxable_amount' => ['required', 'numeric', 'min:0'],
            'lines.*.gst_rate' => ['required', 'numeric', 'in:0,5,18'],
            'lines.*.is_rcm_applicable' => ['nullable', 'boolean'],
            'lines.*.vendor_id' => ['nullable', 'exists:suppliers,id'],
            'lines.*.vendor_name' => ['nullable', 'string', 'max:255'],
            'lines.*.vendor_invoice_no' => ['nullable', 'string', 'max:120'],
            'lines.*.vendor_invoice_date' => ['nullable', 'date'],
            'lines.*.vendor_gstin' => ['nullable', 'string', 'max:15'],
        ];
    }

    private function attributes(): array
    {
        return [
            'expense_date' => 'Expense date',
            'supplier_id' => 'Supplier',
            'invoice_no' => 'Invoice no',
            'lines.*.category_id' => 'Category',
            'lines.*.qty' => 'Quantity',
            'lines.*.rate' => 'Rate',
            'lines.*.taxable_amount' => 'Taxable amount',
            'lines.*.gst_rate' => 'GST rate',
            'lines.*.is_rcm_applicable' => 'RCM applicable',
            'lines.*.vendor_id' => 'Vendor',
            'lines.*.vendor_name' => 'Vendor name',
            'lines.*.vendor_invoice_no' => 'Vendor invoice no',
            'lines.*.vendor_invoice_date' => 'Vendor invoice date',
            'lines.*.vendor_gstin' => 'Vendor GSTIN',
        ];
    }

    private function blankLine(): array
    {
        return [
            'category_id' => '',
            'description' => '',
            'qty' => 1,
            'rate' => null,
            'taxable_amount' => null,
            'gst_rate' => 0,
            'gst_amount' => 0,
            'total_amount' => 0,
            'is_rcm_applicable' => false,
            'vendor_id' => null,
            'vendor_name' => '',
            'vendor_invoice_no' => '',
            'vendor_invoice_date' => null,
            'vendor_gstin' => '',
        ];
    }

    private function recalculateLine(int $index, ?string $field): void
    {
        $line = $this->lines[$index];
        $qty = (float) ($line['qty'] ?? 0);
        $rate = (float) ($line['rate'] ?? 0);

        $this->lines[$index]['taxable_amount'] = round($qty * $rate, 2);

        $taxable = (float) ($this->lines[$index]['taxable_amount'] ?? 0);
        $gstRate = (float) ($line['gst_rate'] ?? 0);
        $gstAmount = round($taxable * $gstRate / 100, 2);
        $totalAmount = round($taxable + $gstAmount, 2);

        $this->lines[$index]['gst_amount'] = $gstAmount;
        $this->lines[$index]['total_amount'] = $totalAmount;
    }

    private function refreshTotals(): void
    {
        $this->taxable_total = 0;
        $this->gst_total = 0;

        foreach ($this->lines as $line) {
            $this->taxable_total += (float) ($line['taxable_amount'] ?? 0);
            $this->gst_total += (float) ($line['gst_amount'] ?? 0);
        }

        $this->grand_total = $this->taxable_total + $this->gst_total;
    }
}
