<?php

namespace App\Http\Controllers\Reports;

use App\Services\CenterPeriodBillReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class CenterPeriodBillPdfController
{
    use AuthorizesRequests;

    public function __invoke(Request $request, CenterPeriodBillReportService $service)
    {
        $this->authorize('center_bill.view');

        $data = $request->validate([
            'center_id' => ['required', 'integer', 'exists:centers,id'],
            'month' => ['required', 'date_format:Y-m'],
            'template_id' => ['required', 'integer', 'exists:settlement_period_templates,id'],
        ]);

        [$from, $to] = $service->resolvePeriodRange($data['month'], (string) $data['template_id']);
        if (! $from || ! $to) {
            abort(422, 'Invalid period selection.');
        }

        $report = $service->buildReport((int) $data['center_id'], $from, $to);
        $filename = 'center-period-bill-'.now()->format('Ymd_His').'.pdf';

        $pdf = Pdf::loadView('reports.center-period-bill', [
            'title' => 'Milk Purchase Bill Report',
            'center' => $report['center'],
            'periodFrom' => $from,
            'periodTo' => $to,
            'rows' => $report['rows'],
            'totals' => $report['totals'],
            'payable' => $report['payable'],
            'netPayableTillEnd' => $report['netPayableTillEnd'],
            'advanceOutstandingTillEnd' => $report['advanceOutstandingTillEnd'],
            'hasSettlement' => $report['hasSettlement'],
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
