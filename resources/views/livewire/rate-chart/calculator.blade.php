<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Rate Calculator</h2>
        <a href="{{ route('rate-charts.view') }}" class="btn-primary" wire:navigate>Back to Rate Charts</a>
    </div>

    <form wire:submit.prevent="calculate">
        <div class="form-grid">
            <div class="form-group">
                <label for="center_id">Center<span style="color:red;">*</span></label>
                <select id="center_id" wire:model="center_id" class="input-field" required>
                    <option value="">Select Center</option>
                    @foreach($centers as $center)
                        <option value="{{ $center->id }}">{{ $center->name }} ({{ $center->code }})</option>
                    @endforeach
                </select>
                @error('center_id')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="milk_type">Milk Type<span style="color:red;">*</span></label>
                <select id="milk_type" wire:model="milk_type" class="input-field" required>
                    <option value="CM">Cow Milk</option>
                    <option value="BM">Buffalo Milk</option>
                </select>
                @error('milk_type')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="date">Date<span style="color:red;">*</span></label>
                <input id="date" type="date" wire:model="date" class="input-field" required>
                @error('date')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="fat">FAT<span style="color:red;">*</span></label>
                <input id="fat" type="number" step="0.01" wire:model="fat" class="input-field" required>
                @error('fat')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="snf">SNF<span style="color:red;">*</span></label>
                <input id="snf" type="number" step="0.01" wire:model="snf" class="input-field" required>
                @error('snf')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
        </div>
        <div style="margin-top: 1rem; text-align:center;">
            <button type="submit" class="btn-submit">Calculate</button>
        </div>
    </form>

    @if($errorMessage)
        <div style="margin-top:1rem; padding:12px; border:1px solid #f87171; border-radius:6px; color:#b91c1c;">
            {{ $errorMessage }}
        </div>
    @endif

    @if($result)
        <div style="margin-top:1.5rem;" class="table-wrapper">
            <table class="product-table">
                <tbody>
                    <tr><th class="px-4 py-2 border dark:border-zinc-700" style="width:220px;">Rate Chart</th><td class="px-4 py-2 border dark:border-zinc-700">#{{ $result['rate_chart_id'] }}</td></tr>
                    <tr><th class="px-4 py-2 border dark:border-zinc-700">Base Rate</th><td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($result['base_rate'], 2) }}</td></tr>
                    <tr><th class="px-4 py-2 border dark:border-zinc-700">FAT Adjustment</th><td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($result['fat_adjustment'], 2) }}</td></tr>
                    <tr><th class="px-4 py-2 border dark:border-zinc-700">SNF Adjustment</th><td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($result['snf_adjustment'], 2) }}</td></tr>
                    <tr><th class="px-4 py-2 border dark:border-zinc-700">Final Rate</th><td class="px-4 py-2 border dark:border-zinc-700" style="font-weight:700;">₹{{ number_format($result['final_rate'], 2) }}</td></tr>
                </tbody>
            </table>
        </div>
    @endif
</div>
