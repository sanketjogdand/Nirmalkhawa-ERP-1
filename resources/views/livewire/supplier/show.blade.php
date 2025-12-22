<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Supplier Details</h2>
        <div style="display:flex; gap:10px;">
            <a href="{{ route('suppliers.view') }}" class="btn-primary" wire:navigate>Back to List</a>
            @can('supplier.update')
                <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn-primary" wire:navigate>Edit Supplier</a>
            @endcan
        </div>
    </div>

    <div class="table-wrapper" style="margin-top:1rem;">
        <table class="product-table">
            <tbody>
                <tr><th class="px-4 py-2 border dark:border-zinc-700" style="width:220px;">Name</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->name }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Code</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->supplier_code }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Contact Person</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->contact_person }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Mobile</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->mobile }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Email</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->email }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">GSTIN</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->gstin }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Address Line</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->address_line }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Pincode</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->pincode }}</td></tr>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Location</th>
                    <td class="px-4 py-2 border dark:border-zinc-700">
                        @if($supplier->village)
                            {{ $supplier->village?->name }},
                            {{ $supplier->village?->taluka?->name }},
                            {{ $supplier->village?->taluka?->district?->name }},
                            {{ $supplier->village?->taluka?->district?->state?->name }}
                        @elseif($supplier->taluka)
                            -, {{ $supplier->taluka?->name }}, {{ $supplier->taluka?->district?->name }}, {{ $supplier->taluka?->district?->state?->name }}
                        @elseif($supplier->district)
                            -, -, {{ $supplier->district?->name }}, {{ $supplier->district?->state?->name }}
                        @elseif($supplier->state)
                            -, -, -, {{ $supplier->state?->name }}
                        @endif
                    </td>
                </tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Account Name</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->account_name }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Account Number</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->account_number }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">IFSC</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->ifsc }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Bank Name</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->bank_name }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Branch</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->branch }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">UPI ID</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->upi_id }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Notes</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->notes }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Status</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Created At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->created_at?->format('d M Y, h:i A') }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Updated At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->updated_at?->format('d M Y, h:i A') }}</td></tr>
            </tbody>
        </table>
    </div>
</div>
