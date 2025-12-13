<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Transfer to Mix</h2>
    </div>

    <div class="summary-container">
        <div class="summary-card">
            <div class="summary-heading">Available Stock</div>
            <table class="summary-table">
                <tr>
                    <td class="label">Cow Milk (LTR):</td>
                    <td>{{ number_format($currentCm, 3) }}</td>
                </tr>
                <tr>
                    <td class="label">Buffalo Milk (LTR):</td>
                    <td>{{ number_format($currentBm, 3) }}</td>
                </tr>
            </table>
        </div>
    </div>

    <form wire:submit.prevent="save" class="form-grid">
        <div class="form-group">
            <label for="date">Date</label>
            <input id="date" type="date" wire:model.live="date" class="input-field">
            @error('date') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="qty_cm_ltr">Cow Milk Qty (LTR)</label>
            <input id="qty_cm_ltr" type="number" step="0.001" wire:model.live="qty_cm_ltr" class="input-field" placeholder="0.000">
            @error('qty_cm_ltr') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="qty_bm_ltr">Buffalo Milk Qty (LTR)</label>
            <input id="qty_bm_ltr" type="number" step="0.001" wire:model.live="qty_bm_ltr" class="input-field" placeholder="0.000">
            @error('qty_bm_ltr') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group span-2">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" wire:model.live="remarks" class="input-field" rows="2" placeholder="Optional remarks"></textarea>
            @error('remarks') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-top:1rem; grid-column: 1 / -1;">
            <button type="submit" class="btn-submit">Transfer</button>
        </div>
    </form>

    @if (session('success'))
        <div class="toastr success">{{ session('success') }}</div>
    @endif
    @if (session('danger'))
        <div class="toastr danger">{{ session('danger') }}</div>
    @endif
</div>
