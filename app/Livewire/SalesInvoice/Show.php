<?php

namespace App\Livewire\SalesInvoice;

use App\Models\SalesInvoice;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'Sales Invoice';

    public SalesInvoice $invoice;

    public function mount(SalesInvoice $salesInvoice): void
    {
        $this->authorize('salesinvoice.view');

        $this->invoice = $salesInvoice->load(
            'customer',
            'lines.product',
            'lines.packSize',
            'lines.dispatch',
            'lines.dispatchLine',
            'createdBy',
            'lockedBy'
        );
    }

    public function render()
    {
        return view('livewire.sales-invoice.show', [
            'invoice' => $this->invoice,
        ])->with(['title_name' => $this->title ?? 'Sales Invoice']);
    }
}
