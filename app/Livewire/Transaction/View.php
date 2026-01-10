<?php

namespace App\Livewire\Transaction;

use Livewire\Component;
use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;
    public $perPage = 25;
    public ?int $confirmingDeleteId = null;

    public function mount(): void
    {
        $this->authorize('transaction.view');
    }

    public function update_perPage() { $this->resetPage(); }

    public function confirmDelete(int $transactionId): void
    {
        $this->authorize('transaction.delete');
        $this->confirmingDeleteId = $transactionId;
    }

    public function deleteTransaction(): void
    {
        if (! $this->confirmingDeleteId) {
            return;
        }

        $this->authorize('transaction.delete');
        $transaction = Transaction::findOrFail($this->confirmingDeleteId);
        $transaction->delete();
        session()->flash('success', 'Transaction deleted.');
        $this->confirmingDeleteId = null;
        $this->resetPage();
    }

    public function render()
    {
        $transactions = Transaction::orderBy('transaction_date')->paginate($this->perPage);
        return view('livewire.transaction.view', compact('transactions'));
    }
}
