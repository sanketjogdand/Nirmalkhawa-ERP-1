<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Production Batch #{{ $batch->id }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('productions.view') }}" class="btn-primary" wire:navigate>Back to list</a>
            @can('production.update')
                <a href="{{ route('productions.edit', $batch->id) }}" class="btn-primary" wire:navigate>Edit</a>
            @endcan
        </div>
    </div>

    <div class="form-grid" style="margin-top:1rem;">
        <div class="form-group">
            <label>Date</label>
            <div>{{ $batch->date?->toDateString() }}</div>
        </div>
        <div class="form-group">
            <label>Output Product</label>
            <div>{{ $batch->outputProduct->name ?? 'N/A' }}</div>
        </div>
        <div class="form-group">
            <label>Recipe</label>
            <div>{{ $batch->recipe->name ?? 'Recipe' }} @if($batch->recipe) v{{ $batch->recipe->version }} @endif</div>
        </div>
        <div class="form-group">
            <label>Actual Output</label>
            <div>{{ $batch->actual_output_qty }} {{ $batch->output_uom }}</div>
        </div>
        <div class="form-group">
            <label>Yield</label>
            <div>
                @if($batch->yield_ratio)
                    Ratio {{ $batch->yield_ratio }} ({{ $batch->yield_pct }}%)
                @else
                    Not available
                @endif
            </div>
        </div>
        <div class="form-group">
            <label>Locked</label>
            <div>{{ $batch->is_locked ? 'Yes' : 'No' }} @if($batch->is_locked && $batch->locked_at) — {{ $batch->locked_at->format('d M Y H:i') }} @endif</div>
        </div>
        <div class="form-group">
            <label>Created By</label>
            <div>{{ $batch->createdByUser->name ?? 'N/A' }}</div>
        </div>
        <div class="form-group span-2">
            <label>Remarks</label>
            <div>{{ $batch->remarks }}</div>
        </div>
    </div>

    <div class="form-group span-3" style="margin-top:1rem;">
        <h3 style="margin:0 0 6px 0;">Inputs</h3>
        <div class="table-wrapper">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">Material</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Planned Qty</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Actual Qty</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">UOM</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Yield Base</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($batch->inputs as $input)
                        <tr>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $input->materialProduct->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $input->planned_qty }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $input->actual_qty_used }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $input->uom }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $input->is_yield_base ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="form-group span-3" style="margin-top:1rem;">
        <h3 style="margin:0 0 6px 0;">Stock Ledger Entries</h3>
        <div class="table-wrapper">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">When</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Product</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Type</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Qty</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgers as $ledger)
                        <tr>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $ledger->txn_datetime?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $ledger->product->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $ledger->txn_type }} {{ $ledger->is_increase ? '(IN)' : '(OUT)' }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $ledger->qty }} {{ $ledger->uom }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $ledger->remarks }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No ledger entries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
