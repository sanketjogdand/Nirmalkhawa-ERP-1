<?php

namespace App\Livewire\ExpenseCategory;

use App\Models\ExpenseCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class View extends Component
{
    use AuthorizesRequests;

    public $title = 'Expense Categories';
    public ?int $categoryId = null;
    public $name = '';

    public $categories;

    public function mount(): void
    {
        $this->authorize('expense_category.view');
        $this->loadCategories();
    }

    public function edit(int $categoryId): void
    {
        $this->authorize('expense_category.update');
        $category = ExpenseCategory::findOrFail($categoryId);
        $this->categoryId = $category->id;
        $this->name = $category->name;
    }

    public function save(): void
    {
        $this->authorize($this->categoryId ? 'expense_category.update' : 'expense_category.create');

        $data = $this->validate($this->rules(), [], $this->attributes());

        $payload = [
            'name' => $data['name'],
        ];

        if ($this->categoryId) {
            $category = ExpenseCategory::findOrFail($this->categoryId);
            $category->update($payload);
            session()->flash('success', 'Category updated.');
        } else {
            ExpenseCategory::create($payload);
            session()->flash('success', 'Category created.');
        }

        $this->resetForm();
        $this->loadCategories();
    }

    public function delete(int $categoryId): void
    {
        $this->authorize('expense_category.delete');
        $category = ExpenseCategory::findOrFail($categoryId);
        $category->delete();

        session()->flash('success', 'Category deleted.');
        $this->loadCategories();
    }

    public function render()
    {
        return view('livewire.expense-category.view')
            ->with(['title_name' => $this->title ?? 'Expense Categories']);
    }

    private function loadCategories(): void
    {
        $this->categories = ExpenseCategory::orderBy('name')->get();
    }

    private function resetForm(): void
    {
        $this->categoryId = null;
        $this->name = '';
    }

    private function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('expense_categories', 'name')->ignore($this->categoryId),
            ],
        ];
    }

    private function attributes(): array
    {
        return [
            'name' => 'Category name',
        ];
    }
}
