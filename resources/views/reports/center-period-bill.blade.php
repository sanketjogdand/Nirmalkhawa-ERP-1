<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 6px 0; text-align: center;}
        h2 { font-size: 14px; margin: 16px 0 6px 0; }
        .meta { margin-bottom: 12px; }
        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td { border: none; padding: 0; }
        .meta-table .right { text-align: right; }
        .letterhead { border-bottom: 1px solid #333; padding-bottom: 8px; margin-bottom: 12px; }
        .company-name { font-size: 16px; font-weight: bold; }
        .company-details { font-size: 11px; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #333; padding: 5px 8px;}
        th { background: #f0f0f0; }
        .totals { font-weight: bold; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .grid td { border: 1px solid #333; padding: 5px 8px; }
    </style>
</head>
<body>
    @php
        $companyName = env('COMPANY_NAME');
        $companyAddress = env('COMPANY_ADDRESS');
        $companyPhone = env('COMPANY_PHONE');
        $companyEmail = env('COMPANY_EMAIL') ?: config('mail.from.address');
        $companyGstin = env('COMPANY_GSTIN');
        $periodFromDisplay = \Illuminate\Support\Carbon::parse($periodFrom)->format('d/m/Y');
        $periodToDisplay = \Illuminate\Support\Carbon::parse($periodTo)->format('d/m/Y');
    @endphp

    <div class="letterhead">
        <div class="company-name">{{ $companyName }}</div>
        <div class="company-details">
            @if($companyAddress)
                <div>{{ $companyAddress }}</div>
            @endif
            @if($companyPhone || $companyEmail)
                <div>
                    @if($companyPhone)
                        Phone: {{ $companyPhone }}
                    @endif
                    @if($companyEmail)
                        {{ $companyPhone ? ' | ' : '' }}Email: {{ $companyEmail }}
                    @endif
                </div>
            @endif
            @if($companyGstin)
                <div>GSTIN: {{ $companyGstin }}</div>
            @endif
        </div>
    </div>

    <h1>{{ $title }}</h1>
    <div class="meta">
        <table class="meta-table">
            <tr>
                <td>Supplier Name : {{ $center?->name ?? '—' }}</td>
                <td class="right">Duration : {{ $periodFromDisplay }} to {{ $periodToDisplay }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Shift</th>
                <th>Milk Type</th>
                <th>Qty</th>
                <th>FAT %</th>
                <th>SNF %</th>
                <th>Rate</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                    <td style="text-align: center;">{{ $row['shift'] }}</td>
                    <td style="text-align: center;">{{ $row['milk_type'] }}</td>
                    <td style="text-align: right;">{{ number_format($row['qty'], 2) }} {{ $row['qty_unit'] }}</td>
                    <td style="text-align: center;">{{ $row['fat_pct'] === null ? '-' : number_format($row['fat_pct'], 2) }}</td>
                    <td style="text-align: center;">{{ $row['snf_pct'] === null ? '-' : number_format($row['snf_pct'], 2) }}</td>
                    <td style="text-align: center;">{{ $row['rate'] === null ? '-' : number_format($row['rate'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format_indian($row['amount'] ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No milk intake rows found.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="totals">
                <td colspan="3">Totals</td>
                <td style="text-align: right;">{{ number_format_indian($totals['qty_ltr'] ?? 0, 2) }} L</td>
                <td colspan="3"></td>
                <td style="text-align: right;">{{ number_format_indian($totals['amount'] ?? 0, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <h2>Payable Details</h2>
    <table class="grid">
        <tr>
            <td>Gross Amount</td>
            <td style="text-align: right;">{{ number_format_indian($payable['gross_amount_total'] ?? 0, 2) }}</td>
            <td>Commission</td>
            <td style="text-align: right;">{{ number_format_indian($payable['commission_total'] ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td>Incentive</td>
            <td style="text-align: right;">{{ number_format_indian($payable['incentive_amount'] ?? 0, 2) }}</td>
            <td>Advance Given</td>
            <td style="text-align: right;">{{ number_format_indian($payable['advance_given'] ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td>Advance Deducted</td>
            <td style="text-align: right;">{{ number_format_indian($payable['advance_deducted'] ?? 0, 2) }}</td>
            <td>Short Adjustment</td>
            <td style="text-align: right;">{{ number_format_indian($payable['short_adjustment'] ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td>Other Deductions</td>
            <td style="text-align: right;">{{ number_format_indian($payable['other_deductions'] ?? 0, 2) }}</td>
            <td>Discount</td>
            <td style="text-align: right;">{{ number_format_indian($payable['discount_amount'] ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td>TDS</td>
            <td style="text-align: right;">{{ number_format_indian($payable['tds_amount'] ?? 0, 2) }}</td>
            <td style="font-weight: bold;">Net Total</td>
            <td style="font-weight: bold; text-align: right;">{{ number_format_indian($payable['net_total'] ?? 0, 2) }}</td>
        </tr>
    </table>

    <!-- <h2>Net Payable Till {{ $periodToDisplay }}</h2>
    <div style="font-size:16px; font-weight:bold;">{{ number_format_indian($netPayableTillEnd ?? 0, 2) }}</div>

    <h2>Advance Outstanding Till {{ $periodToDisplay }}</h2>
    <div style="font-size:16px; font-weight:bold;">{{ number_format_indian($advanceOutstandingTillEnd ?? 0, 2) }}</div> -->
</body>
</html>
