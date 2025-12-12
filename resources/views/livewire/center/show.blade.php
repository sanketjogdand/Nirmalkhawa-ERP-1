<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Center Details</h2>
        <div style="display:flex; gap:10px;">
            <a href="{{ route('centers.view') }}" class="btn-primary" wire:navigate>Back to List</a>
            @can('ratechart.view')
                <a href="{{ route('rate-charts.view') }}" class="btn-primary" wire:navigate>Assignments / Rate Charts</a>
            @endcan
            @can('center.update')
                <a href="{{ route('centers.edit', $center->id) }}" class="btn-primary" wire:navigate>Edit Center</a>
            @endcan
        </div>
    </div>

    <div class="table-wrapper" style="margin-top:1rem;">
        <table class="product-table">
            <tbody>
                <tr><th class="px-4 py-2 border dark:border-zinc-700" style="width:200px;">Center Name</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $center->name }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Code</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $center->code }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Contact Person</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $center->contact_person }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Mobile</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $center->mobile }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Address</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $center->address }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Location</th><td class="px-4 py-2 border dark:border-zinc-700">
                    @if($center->village?->name)
                        {{ $center->village?->name }},
                        {{ $center->village?->taluka?->name }},
                        {{ $center->village?->taluka?->district?->name }},
                        {{ $center->village?->taluka?->district?->state?->name }}
                    @endif
                </td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Account Name</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $center->account_name }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Account Number</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $center->account_number }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">IFSC</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $center->ifsc }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Branch</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $center->branch }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Status</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $center->status }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Created At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $center->created_at?->format('d M Y, h:i A') }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Updated At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $center->updated_at?->format('d M Y, h:i A') }}</td></tr>
            </tbody>
        </table>
    </div>

    <div style="margin-top:2rem;">
        <h3 style="font-size:18px; font-weight:600; margin-bottom:10px;">Rate Chart Assignments</h3>
        <div class="table-wrapper">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">Rate Chart</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Milk Type</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Effective From</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Effective To</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Status</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Manage</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($center->rateChartAssignments->sortByDesc('effective_from') as $assignment)
                        <tr>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $assignment->rateChart?->name }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $assignment->rateChart?->milk_type }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $assignment->effective_from?->format('d M Y') }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $assignment->effective_to?->format('d M Y') ?? 'Ongoing' }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $assignment->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                @if($assignment->rateChart)
                                    <a href="{{ route('rate-charts.show', $assignment->rate_chart_id) }}" class="action-link" wire:navigate>View Chart</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No assignments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
