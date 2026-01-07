<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <h2 class="page-heading" style="margin-bottom: 0;">Employees</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('employee.create')
                <a href="{{ route('employees.create') }}" class="btn-primary" wire:navigate>Add Employee</a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif

    <div class="form-grid" style="margin: 1rem 0;">
        <div class="form-group">
            <label for="search">Search</label>
            <input id="search" type="text" wire:model.live="search" class="input-field" placeholder="Name / Code / Mobile">
        </div>
        <div class="form-group">
            <label for="employmentFilter">Employment</label>
            <select id="employmentFilter" wire:model.live="employmentFilter" class="input-field">
                <option value="active">Active Today</option>
                <option value="resigned">Show Resigned</option>
                <option value="all">Show All</option>
            </select>
        </div>
        <div class="form-group">
            <label for="perPage">Records per page</label>
            <select id="perPage" wire:model.live="perPage" class="input-field">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>

    <div class="table-wrapper">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Code</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Name</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Mobile</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Designation</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Department</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Employment</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $employee)
                    @php
                        $active = $employee->employmentPeriods->first(function ($period) use ($today) {
                            return $period->start_date?->toDateString() <= $today
                                && ($period->end_date === null || $period->end_date?->toDateString() >= $today);
                        });
                        $hasPeriods = $employee->employmentPeriods->isNotEmpty();
                    @endphp
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $employee->employee_code }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $employee->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $employee->mobile }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $employee->designation }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $employee->department }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $employee->joining_date?->format('d M Y') ?? '—' }}
                            @if($employee->resignation_date)
                                to {{ $employee->resignation_date->format('d M Y') }}
                            @elseif($active)
                                (Active)
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="white-space:nowrap;">
                            @php $actions = []; @endphp
                            @can('employee.update')
                                @php $actions[] = '<a href="'.route('employees.edit', $employee->id).'" class="action-link" wire:navigate>Edit</a>'; @endphp
                            @endcan
                            @can('employment_period.manage')
                                @php $actions[] = '<a href="'.route('employees.employment-periods', ['employee' => $employee->id, 'add' => $hasPeriods ? 0 : 1]).'" class="action-link" wire:navigate>'.($hasPeriods ? 'Manage Employment Periods' : 'Add Joining Period').'</a>'; @endphp
                            @endcan
                            @can('salary_rate.view')
                                @php $actions[] = '<a href="'.route('employee-salary-rates.view', $employee->id).'" class="action-link" wire:navigate>Salary Rates</a>'; @endphp
                            @endcan
                            @can('employee.delete')
                                @php $actions[] = '<button type="button" class="action-link" wire:click="deleteEmployee('.$employee->id.')" style="border:none; background:transparent; padding:0;">Delete</button>'; @endphp
                            @endcan
                            <span style="display:inline-flex; align-items:center; gap:8px; white-space:nowrap;">
                                @foreach($actions as $index => $action)
                                    {!! $action !!}
                                    @if($index < count($actions) - 1)
                                        <span aria-hidden="true">|</span>
                                    @endif
                                @endforeach
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align: center;" class="px-4 py-2 border dark:border-zinc-700">No Record Found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $employees->links() }}
    </div>
</div>
