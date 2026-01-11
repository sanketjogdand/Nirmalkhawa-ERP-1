<?php

namespace App\Livewire\Reports\SalesDispatch;

use App\Livewire\Reports\BaseReport;
use Illuminate\Support\Facades\DB;

class CustomerProfitability extends BaseReport
{
    public string $title = 'Customer Profitability';

    protected function columns(): array
    {
        return [
            'customer' => 'Customer',
            'sales_total' => 'Sales Total',
            'delivery_cost' => 'Delivery Cost',
            'net_contribution' => 'Net Contribution',
        ];
    }

    protected function applyFilters($query)
    {
        return $query;
    }

    protected function baseQuery()
    {
        $salesSub = DB::table('sales_invoices as si')
            ->selectRaw('si.customer_id, SUM(si.grand_total) as sales_total')
            ->whereNull('si.deleted_at')
            ->when($this->fromDate, fn ($q) => $q->whereDate('si.invoice_date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('si.invoice_date', '<=', $this->toDate))
            ->groupBy('si.customer_id');

        $dispatchCustomers = DB::table('dispatch_lines as dl')
            ->select('dl.dispatch_id', 'dl.customer_id')
            ->whereNull('dl.deleted_at')
            ->distinct();

        $deliverySub = DB::table('dispatch_delivery_expenses as dde')
            ->joinSub($dispatchCustomers, 'dc', 'dc.dispatch_id', '=', 'dde.dispatch_id')
            ->selectRaw('dc.customer_id, SUM(dde.amount) as delivery_cost')
            ->whereNull('dde.deleted_at')
            ->when($this->fromDate, fn ($q) => $q->whereDate('dde.expense_date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('dde.expense_date', '<=', $this->toDate))
            ->groupBy('dc.customer_id');

        return DB::table('customers as c')
            ->leftJoinSub($salesSub, 'sales', 'sales.customer_id', '=', 'c.id')
            ->leftJoinSub($deliverySub, 'delivery', 'delivery.customer_id', '=', 'c.id')
            ->selectRaw('c.name as customer')
            ->selectRaw('ROUND(COALESCE(sales.sales_total, 0), 2) as sales_total')
            ->selectRaw('ROUND(COALESCE(delivery.delivery_cost, 0), 2) as delivery_cost')
            ->selectRaw('ROUND(COALESCE(sales.sales_total, 0) - COALESCE(delivery.delivery_cost, 0), 2) as net_contribution')
            ->orderBy('c.name');
    }
}
