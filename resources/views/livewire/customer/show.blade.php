<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Customer Details</h2>
        <div style="display:flex; gap:10px;">
            <a href="{{ route('customers.view') }}" class="btn-primary" wire:navigate>Back to List</a>
            @can('customer.update')
                <a href="{{ route('customers.edit', $customer->id) }}" class="btn-primary" wire:navigate>Edit Customer</a>
            @endcan
        </div>
    </div>

    <div class="table-wrapper" style="margin-top:1rem;">
        <table class="product-table">
            <tbody>
                <tr><th class="px-4 py-2 border dark:border-zinc-700" style="width:220px;">Name</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $customer->name }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Code</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $customer->customer_code }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Mobile</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $customer->mobile }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">GSTIN</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $customer->gstin }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Address Line</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $customer->address_line }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Pincode</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $customer->pincode }}</td></tr>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Location</th>
                    <td class="px-4 py-2 border dark:border-zinc-700">
                        @if($customer->village)
                            {{ $customer->village?->name }},
                            {{ $customer->village?->taluka?->name }},
                            {{ $customer->village?->taluka?->district?->name }},
                            {{ $customer->village?->taluka?->district?->state?->name }}
                        @elseif($customer->taluka)
                            -, {{ $customer->taluka?->name }}, {{ $customer->taluka?->district?->name }}, {{ $customer->taluka?->district?->state?->name }}
                        @elseif($customer->district)
                            -, -, {{ $customer->district?->name }}, {{ $customer->district?->state?->name }}
                        @elseif($customer->state)
                            -, -, -, {{ $customer->state?->name }}
                        @endif
                    </td>
                </tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Status</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $customer->is_active ? 'Active' : 'Inactive' }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Created At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $customer->created_at?->format('d M Y, h:i A') }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Updated At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $customer->updated_at?->format('d M Y, h:i A') }}</td></tr>
            </tbody>
        </table>
    </div>
</div>
