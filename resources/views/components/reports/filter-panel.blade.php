@props(['filters' => []])

<div class="form-grid">
    <div class="form-group">
        <label for="fromDate">From Date</label>
        <input id="fromDate" type="date" wire:model.live="fromDate" class="input-field">
    </div>
    <div class="form-group">
        <label for="toDate">To Date</label>
        <input id="toDate" type="date" wire:model.live="toDate" class="input-field">
    </div>

    @foreach ($filters as $name => $config)
        <div class="form-group">
            <label for="{{ $name }}">{{ $config['label'] ?? ucfirst($name) }}</label>
            <select id="{{ $name }}" wire:model.live="{{ $name }}" class="input-field">
                <option value="">{{ $config['placeholder'] ?? 'All' }}</option>
                @foreach (($config['options'] ?? []) as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
        </div>
    @endforeach
</div>
