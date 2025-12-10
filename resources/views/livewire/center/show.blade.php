<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Center Details</h2>
        <div style="display:flex; gap:10px;">
            <a href="{{ route('centers.view') }}" class="btn-primary" wire:navigate>Back to List</a>
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
</div>
