<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $employeeId ? 'Edit Employee' : 'Add Employee' }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('employees.view') }}" class="btn-primary" wire:navigate>Back to list</a>
            @if($employeeId)
                @can('salary_rate.view')
                    <a href="{{ route('employee-salary-rates.view', $employeeId) }}" class="btn-secondary" wire:navigate>Salary Rates</a>
                @endcan
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif

    <form wire:submit.prevent="save" class="form-grid" style="margin-top: 1rem;">
        <div class="form-group">
            <label for="employee_code">Employee Code <span style="color:red;">*</span></label>
            <input id="employee_code" type="text" wire:model.live="employee_code" class="input-field" placeholder="EMP001">
            @error('employee_code') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="name">Name <span style="color:red;">*</span></label>
            <input id="name" type="text" wire:model.live="name" class="input-field" placeholder="Employee name">
            @error('name') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="mobile">Mobile</label>
            <input id="mobile" type="text" wire:model.live="mobile" class="input-field" placeholder="10-digit mobile">
            @error('mobile') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="department">Department</label>
            <select id="department" wire:model.live="department" class="input-field" wire:change="updateDepartment">
                <option value="">Select Department</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept }}">{{ $dept }}</option>
                @endforeach
            </select>
            @error('department') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="designation">Designation</label>
            <select id="designation" wire:model.live="designation" class="input-field" @disabled(empty($designationOptions))>
                <option value="">Select Designation</option>
                @foreach($designationOptions as $designationOption)
                    <option value="{{ $designationOption }}">{{ $designationOption }}</option>
                @endforeach
            </select>
            @error('designation') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        @if($employeeId)
            <div class="form-group">
                <label>First Joining</label>
                <input type="text" value="{{ $firstJoining ?? '—' }}" class="input-field" readonly>
            </div>
            <div class="form-group">
                <label>Last Resignation</label>
                <input type="text" value="{{ $lastResignation ?? '—' }}" class="input-field" readonly>
            </div>
        @endif

        <div class="form-group">
            <label for="state_id">State</label>
            <select id="state_id" wire:model.live="state_id" class="input-field" wire:change="updateStateId">
                <option value="">Select State</option>
                @foreach($states as $state)
                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                @endforeach
            </select>
            @error('state_id') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="district_id">District</label>
            <select id="district_id" wire:model.live="district_id" class="input-field" wire:change="updateDistrictId">
                <option value="">Select District</option>
                @foreach($districts as $district)
                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                @endforeach
            </select>
            @error('district_id') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="taluka_id">Taluka</label>
            <select id="taluka_id" wire:model.live="taluka_id" class="input-field" wire:change="updateTalukaId">
                <option value="">Select Taluka</option>
                @foreach($talukas as $taluka)
                    <option value="{{ $taluka->id }}">{{ $taluka->name }}</option>
                @endforeach
            </select>
            @error('taluka_id') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="village_id">Village</label>
            <select id="village_id" wire:model.live="village_id" class="input-field">
                <option value="">Select Village</option>
                @foreach($villages as $village)
                    <option value="{{ $village->id }}">{{ $village->name }}</option>
                @endforeach
            </select>
            @error('village_id') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group span-2">
            <label for="address_line">Address</label>
            <textarea id="address_line" wire:model.live="address_line" class="input-field" placeholder="Address"></textarea>
            @error('address_line') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group span-2">
            <label for="notes">Notes</label>
            <textarea id="notes" wire:model.live="notes" class="input-field" placeholder="Notes"></textarea>
            @error('notes') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-top:1rem; grid-column: 1 / -1; display:flex; gap:12px; flex-wrap:wrap;">
            <button type="submit" class="btn-submit">{{ $employeeId ? 'Update' : 'Save' }} Employee</button>
            <a href="{{ route('employees.view') }}" class="btn-secondary" wire:navigate>Cancel</a>
        </div>
    </form>

    @if($employeeId)
        <div style="margin-top:1rem;">
            <a href="{{ route('employees.employment-periods', $employeeId) }}" class="btn-secondary" wire:navigate>Manage Employment Periods</a>
        </div>
    @else
        <div style="margin-top:1rem; color:gray;">Save the employee to manage employment periods.</div>
    @endif
</div>
