<?php

namespace App\Livewire\Reports\SalesDispatch;

use App\Livewire\Reports\BaseReport;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class SalesInvoiceRegister extends BaseReport
{
    public string $title = 'Sales Invoice Register';
    public string $customerId = '';
    public array $customerOptions = [];

    protected string $dateField = 'si.invoice_date';
    protected array $filterFields = ['customerId'];

    protected function initFilters(): void
    {
        $this->customerOptions = Customer::orderBy('name')
            ->get()
            ->map(fn ($customer) => ['value' => (string) $customer->id, 'label' => $customer->name])
            ->all();
    }

    protected function filterConfig(): array
    {
        return [
            'customerId' => [
                'label' => 'Customer',
                'options' => $this->customerOptions,
            ],
        ];
    }

    protected function columns(): array
    {
        return [
            'invoice_no' => 'Invoice No',
            'date' => 'Date',
            'customer' => 'Customer',
            'product' => 'Product',
            'qty' => 'Qty',
            'taxable' => 'Taxable',
            'gst' => 'GST',
            'total' => 'Total',
        ];
    }

    protected function baseQuery()
    {
        return DB::table('sales_invoice_lines as sil')
            ->join('sales_invoices as si', 'si.id', '=', 'sil.sales_invoice_id')
            ->leftJoin('customers as c', 'c.id', '=', 'si.customer_id')
            ->leftJoin('products as p', 'p.id', '=', 'sil.product_id')
            ->whereNull('si.deleted_at')
            ->when($this->customerId, fn ($q) => $q->where('si.customer_id', $this->customerId))
            ->selectRaw('si.invoice_no as invoice_no')
            ->selectRaw('si.invoice_date as date')
            ->selectRaw('COALESCE(c.name, "-") as customer')
            ->selectRaw('COALESCE(p.name, "-") as product')
            ->selectRaw('ROUND(COALESCE(sil.computed_total_qty, sil.qty_bulk, 0), 3) as qty')
            ->selectRaw('ROUND(COALESCE(sil.taxable_amount, 0), 2) as taxable')
            ->selectRaw('ROUND(COALESCE(sil.gst_amount, 0), 2) as gst')
            ->selectRaw('ROUND(COALESCE(sil.line_total, 0), 2) as total')
            ->orderByDesc('si.invoice_date')
            ->orderBy('si.invoice_no');
    }
}
