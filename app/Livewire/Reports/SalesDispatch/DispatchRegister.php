<?php

namespace App\Livewire\Reports\SalesDispatch;

use App\Livewire\Reports\BaseReport;
use App\Models\Dispatch;
use Illuminate\Support\Facades\DB;

class DispatchRegister extends BaseReport
{
    public string $title = 'Dispatch Register';
    public string $deliveryMode = '';

    protected string $dateField = 'd.dispatch_date';
    protected array $filterFields = ['deliveryMode'];

    protected function filterConfig(): array
    {
        return [
            'deliveryMode' => [
                'label' => 'Delivery Mode',
                'options' => [
                    ['value' => Dispatch::DELIVERY_SELF, 'label' => 'Self Pickup'],
                    ['value' => Dispatch::DELIVERY_COMPANY, 'label' => 'Company Delivery'],
                ],
            ],
        ];
    }

    protected function columns(): array
    {
        return [
            'dispatch_no' => 'Dispatch No',
            'date' => 'Date',
            'customer' => 'Customer',
            'product' => 'Product',
            'qty' => 'Bulk/Pack Qty',
        ];
    }

    protected function baseQuery()
    {
        return DB::table('dispatch_lines as dl')
            ->join('dispatches as d', 'd.id', '=', 'dl.dispatch_id')
            ->leftJoin('customers as c', 'c.id', '=', 'dl.customer_id')
            ->leftJoin('products as p', 'p.id', '=', 'dl.product_id')
            ->whereNull('d.deleted_at')
            ->when($this->deliveryMode, fn ($q) => $q->where('d.delivery_mode', $this->deliveryMode))
            ->selectRaw('d.dispatch_no as dispatch_no')
            ->selectRaw('d.dispatch_date as date')
            ->selectRaw('COALESCE(c.name, "-") as customer')
            ->selectRaw('COALESCE(p.name, "-") as product')
            ->selectRaw("CASE WHEN dl.sale_mode = 'BULK' THEN CONCAT(ROUND(COALESCE(dl.qty_bulk, 0), 3), ' ', COALESCE(dl.uom, '')) ELSE CONCAT(COALESCE(dl.pack_count, 0), ' packs') END as qty")
            ->orderByDesc('d.dispatch_date')
            ->orderBy('d.dispatch_no');
    }
}
