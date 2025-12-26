<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title'=>$title_name ?? 'KCB Industries Pvt. Ltd.'])
    </head>
    {{-- STYLES --}}
    <style>
        .per-page-select {
            text-align: right;
            font-size: 14px;
        }

        .per-page-select select {
            padding: 4px 8px;
            border-radius: 4px;
            margin-left: 8px;
        }
        .per-page-select select option {
            background-color: #ffffff;
            color: #111111;
        }

        @media (prefers-color-scheme: dark) {
            .per-page-select select option {
                background-color: #262626;
                color: #ffffff;
            }
        }

        .per-page-select-left {
            text-align: left;
        }

        .product-container {
            padding: 1.5rem;
            color: inherit;
        }

        .page-heading {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 1rem;
            text-align: center;
        }

        .btn-primary {
            /* display: inline-block; */
            background-color: #2563eb;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #1e40af;
        }

        .btn-danger {
            display: inline-block;
            background-color: #eb2525;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-danger:hover {
            background-color: #af1e1e;
        }

        .table-wrapper {
            overflow-x: auto;
            /* border: 1px solid currentColor; */
            border-radius: 6px;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .product-table th{
            padding: 10px;
            /* border: 1px solid currentColor; */
            /* text-align: left; */
        }
        .product-table td {
            padding: 10px;
            /* border: 1px solid currentColor; */
            text-align: left;
        }

        .product-table thead th {
            background-color: #9c9c9c;
            color: #000;
        }

        .hover-highlight tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .action-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }

        .action-link:hover {
            text-decoration: underline;
        }

        .pagination-wrapper {
            margin-top: 1.5rem;
            text-align: center;
        }

        @media (prefers-color-scheme: dark) {
            .product-table thead th {
                background-color: #474747;
                color: #ffffff;
            }

            .product-table tbody td {
                color: #e5e5e5;
            }

            .hover-highlight tbody tr:hover {
                background-color: rgba(255, 255, 255, 0.08);
            }

            .btn-primary {
                background-color: #3b82f6;
                color: #ffffff;
            }

            .btn-primary:hover {
                background-color: #1d4ed8;
            }

            .btn-danger {
                background-color: #f63b3b;
                color: #ffffff;
            }

            .btn-danger:hover {
                background-color: #d81d1d;
            }

            .action-link {
                color: #93c5fd;
            }

            .action-link:hover {
                color: #bfdbfe;
            }
        }


        /* Input tags Start */
        .input-field {
            width: 100%;
            padding: 6px 10px;
            font-size: 14px;
            border: 1px solid #8f8e8e;
            border-radius: 4px;
            background-color: inherit;
            color: inherit;
        }
        select.input-field option {
            background-color: #ffffff;
            color: #111111;
        }
        @media (prefers-color-scheme: dark) {
            select.input-field option {
                background-color: #262626;
                color: #ffffff;
            }
        }
        .btn-submit {
            background-color: #007bff;
            color: white;
            padding: 6px 12px;
            font-size: 13px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-submit:hover {
            background-color: #0056b3;
        }

        .date-row {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
            margin-bottom: 1rem;
            font-size: 14px;
        }

        .input-field.short {
            width: 220px;
        }
        /* Input tags End */

        /* Employee Attendance Report Start */
        .summary-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 1.5rem;
            font-size: 14px;
            justify-content: flex-start;
        }

        .summary-card {
            flex: 1;
            min-width: 300px;
            background-color: #f9f9f9;
            padding: 16px;
            border: 1px solid #8f8e8e;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            /* background-color: #1f2937; */
            color: #000000;
        }

        .summary-heading {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 12px;
            text-align: center;
            padding: 6px;
            border-radius: 4px;
            background-color: #9c9c9c;
            color: #000000;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 6px 4px;
            vertical-align: top;
        }

        .label {
            font-weight: 600;
            padding-right: 12px;
            width: 140px;
        }

        @media (prefers-color-scheme: dark) {
            .summary-card {
                background-color: rgba(255, 255, 255, 0.03);
                color: #e5e7eb;
            }

            .summary-heading {
                background-color: #474747;
                color: #ffffff;
            }

            .label {
                color: #f3f4f6;
            }
        }
        /* Employee Attendance Report End */

        /* Toastr Start */
        .toastr {
            position: fixed;
            top: 20px;
            right: 20px;
            min-width: 250px;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
            opacity: 0;
            transform: translateY(-20px);
            animation: slideIn 0.4s forwards;
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .toastr.success {
            background-color: #e6ffed;
            color: #1a7f37;
            border-left: 5px solid #1a7f37;
        }

        .toastr.info {
            background-color: #e6f0ff;
            color: #1e4fc3;
            border-left: 5px solid #1e4fc3;
        }

        .toastr.warning {
            background-color: #fff6e0;
            color: #c07d00;
            border-left: 5px solid #c07d00;
        }

        .toastr.danger {
            background-color: #ffe6e6;
            color: #d72638;
            border-left: 5px solid #d72638;
        }

        @media (prefers-color-scheme: dark) {
            .toastr.success {
                background-color: #1f3d2b;
                color: #b6f2cd;
                border-left-color: #38d88a;
            }

            .toastr.info {
                background-color: #1f2d4d;
                color: #b3ccff;
                border-left-color: #6699ff;
            }

            .toastr.warning {
                background-color: #4b3e1a;
                color: #ffe680;
                border-left-color: #ffcc00;
            }

            .toastr.danger {
                background-color: #3b1e1e;
                color: #ffb3b3;
                border-left-color: #ff4c4c;
            }
        }
        /* Toastr End */

        /* Form Start */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.span-2 {
            grid-column: span 2;
        }
        .form-group.span-3 {
            grid-column: span 3;
        }
        /* Form End */

    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toasts = document.querySelectorAll('.toastr');
            toasts.forEach(toast => {
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-20px)';
                    setTimeout(() => toast.remove(), 300); // Cleanup after animation
                }, 2000); // 5 seconds
            });
        });
    </script>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')" class="grid">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            @if(auth()->user()->can('center.view') || auth()->user()->can('centersettlement.view') || auth()->user()->can('centerpayment.view'))
                <flux:navlist.group expandable :heading="__('Centers')" class="lg:grid" :expanded="request()->routeIs(['centers.*','milk-intakes.*','center-settlements.*','center-payments.*','settlement-templates'])">
                    @can('center.view')
                        <flux:navlist.item :href="route('centers.view')" :current="request()->routeIs('centers.*')" wire:navigate>{{ __('Centers') }}</flux:navlist.item>
                    @endcan
                    @can('milkintake.view')
                        <flux:navlist.item :href="route('milk-intakes.view')" :current="request()->routeIs('milk-intakes.*')" wire:navigate>{{ __('Milk Intake') }}</flux:navlist.item>
                    @endcan
                    @can('centersettlement.view')
                        <flux:navlist.item :href="route('center-settlements.view')" :current="request()->routeIs('center-settlements.*')" wire:navigate>{{ __('Center Settlements') }}</flux:navlist.item>
                    @endcan
                    @can('centerpayment.view')
                        <flux:navlist.item :href="route('center-payments.view')" :current="request()->routeIs('center-payments.*')" wire:navigate>{{ __('Center Payments') }}</flux:navlist.item>
                    @endcan
                    @can('settlementtemplate.manage')
                        <flux:navlist.item :href="route('settlement-templates')" :current="request()->routeIs('settlement-templates')" wire:navigate>{{ __('Settlement Period Templates') }}</flux:navlist.item>
                    @endcan
                </flux:navlist.group>
            @endif
            @canany(['ratechart.view', 'commissionpolicy.view'])
                <flux:navlist.group expandable :heading="__('Rates & Commission')" class="lg:grid" :expanded="request()->routeIs(['rate-charts.*','commission-*'])">
                    @can('ratechart.view')
                        <flux:navlist.item :href="route('rate-charts.view')" :current="request()->routeIs(['rate-charts.view', 'rate-charts.create', 'rate-charts.edit'])" wire:navigate>{{ __('Rate Charts') }}</flux:navlist.item>
                        <flux:navlist.item :href="route('rate-charts.calculator')" :current="request()->routeIs('rate-charts.calculator')" wire:navigate>{{ __('Rate Calculator') }}</flux:navlist.item>
                    @endcan
                    @can('commissionpolicy.view')
                        <flux:navlist.item :href="route('commission-policies.view')" :current="request()->routeIs('commission-policies.*')" wire:navigate>{{ __('Commission Policies') }}</flux:navlist.item>
                        <flux:navlist.item :href="route('commission-assignments')" :current="request()->routeIs('commission-assignments')" wire:navigate>{{ __('Commission Assignments') }}</flux:navlist.item>
                    @endcan
                </flux:navlist.group>
            @endcanany
            @canany(['product.view', 'recipe.view', 'production.view'])
                <flux:navlist.group expandable :heading="__('Production')" class="lg:grid" :expanded="request()->routeIs(['products.*', 'recipes.*', 'productions.*'])">
                    @can('product.view')
                        <flux:navlist.item :href="route('products.view')" :current="request()->routeIs('products.*')" wire:navigate>{{ __('Products') }}</flux:navlist.item>
                    @endcan
                    @can('recipe.view')
                        <flux:navlist.item :href="route('recipes.view')" :current="request()->routeIs('recipes.*')" wire:navigate>{{ __('Recipes') }}</flux:navlist.item>
                    @endcan
                    @can('production.view')
                        <flux:navlist.item :href="route('productions.view')" :current="request()->routeIs('productions.*')" wire:navigate>{{ __('Production') }}</flux:navlist.item>
                    @endcan
                </flux:navlist.group>
            @endcanany

            @canany(['inventory.view', 'inventory.adjust', 'inventory.transfer'])
                <flux:navlist.group expandable :heading="__('Inventory')" class="lg:grid" :expanded="request()->routeIs([
                    'inventory.stock-summary',
                    'inventory.stock-ledger',
                    'inventory.stock-adjustments',
                    'inventory.transfer-to-mix',
                ])">
                    @can('inventory.view')
                        <flux:navlist.item :href="route('inventory.stock-summary')" :current="request()->routeIs('inventory.stock-summary')" wire:navigate>{{ __('Stock Summary') }}</flux:navlist.item>
                        <flux:navlist.item :href="route('inventory.stock-ledger')" :current="request()->routeIs('inventory.stock-ledger')" wire:navigate>{{ __('Stock Ledger') }}</flux:navlist.item>
                    @endcan
                    @can('inventory.adjust')
                        <flux:navlist.item :href="route('inventory.stock-adjustments')" :current="request()->routeIs('inventory.stock-adjustments')" wire:navigate>{{ __('Stock Adjustments') }}</flux:navlist.item>
                    @endcan
                    @can('inventory.transfer')
                        <flux:navlist.item :href="route('inventory.transfer-to-mix')" :current="request()->routeIs('inventory.transfer-to-mix')" wire:navigate>{{ __('Transfer to Mix') }}</flux:navlist.item>
                    @endcan
                </flux:navlist.group>
            @endcanany

            @canany(['packsize.view', 'packing.create', 'unpacking.create', 'packinventory.view'])
                <flux:navlist.group expandable :heading="__('Packing')" class="lg:grid" :expanded="request()->routeIs([
                    'pack-sizes',
                    'packing',
                    'unpacking',
                    'pack-inventory',
                    'pack-history',
                ])">
                    @can('packsize.view')
                        <flux:navlist.item :href="route('pack-sizes')" :current="request()->routeIs('pack-sizes')" wire:navigate>{{ __('Pack Sizes') }}</flux:navlist.item>
                    @endcan
                    @can('packing.create')
                        <flux:navlist.item :href="route('packing')" :current="request()->routeIs('packing')" wire:navigate>{{ __('Packing') }}</flux:navlist.item>
                    @endcan
                    @can('unpacking.create')
                        <flux:navlist.item :href="route('unpacking')" :current="request()->routeIs('unpacking')" wire:navigate>{{ __('Unpacking') }}</flux:navlist.item>
                    @endcan
                    @can('packinventory.view')
                        <flux:navlist.item :href="route('pack-inventory')" :current="request()->routeIs('pack-inventory')" wire:navigate>{{ __('Pack Inventory') }}</flux:navlist.item>
                        <flux:navlist.item :href="route('pack-history')" :current="request()->routeIs('pack-history')" wire:navigate>{{ __('Pack History') }}</flux:navlist.item>
                    @endcan
                </flux:navlist.group>
            @endcanany
            @canany(['customer.view', 'receipt.view', 'salesinvoice.view', 'dispatch.view', 'dispatch.create', 'dispatch.update'])
                <flux:navlist.group expandable :heading="__('Customers')" class="lg:grid" :expanded="request()->routeIs(['customers.*', 'customer-receipts.*', 'sales-invoices.*', 'dispatches.*'])">
                    @can('customer.view')
                        <flux:navlist.item :href="route('customers.view')" :current="request()->routeIs('customers.*')" wire:navigate>{{ __('Customers') }}</flux:navlist.item>
                    @endcan
                    @can('receipt.view')
                        <flux:navlist.item :href="route('customer-receipts.view')" :current="request()->routeIs('customer-receipts.*')" wire:navigate>{{ __('Customer Receipts') }}</flux:navlist.item>
                    @endcan
                    @can('salesinvoice.view')
                        <flux:navlist.item :href="route('sales-invoices.view')" :current="request()->routeIs('sales-invoices.*')" wire:navigate>{{ __('Sales Invoices') }}</flux:navlist.item>
                    @endcan
                    @canany(['dispatch.view', 'dispatch.create', 'dispatch.update'])
                        <flux:navlist.item :href="route('dispatches.view')" :current="request()->routeIs('dispatches.*')" wire:navigate>{{ __('Dispatch') }}</flux:navlist.item>
                    @endcanany
                </flux:navlist.group>
            @endcanany
            @canany(['supplier.view', 'deliveryexpense.view', 'supplierpayment.view', 'purchase.view'])
                <flux:navlist.group expandable :heading="__('Supplier')" class="lg:grid" :expanded="request()->routeIs(['suppliers.*', 'delivery-expenses.*', 'supplier-payments.*', 'purchases.*'])">
                    @can('supplier.view')
                        <flux:navlist.item :href="route('suppliers.view')" :current="request()->routeIs('suppliers.*')" wire:navigate>{{ __('Suppliers') }}</flux:navlist.item>
                    @endcan
                    @can('deliveryexpense.view')
                        <flux:navlist.item :href="route('delivery-expenses.view')" :current="request()->routeIs('delivery-expenses.*')" wire:navigate>{{ __('Delivery Expenses') }}</flux:navlist.item>
                    @endcan
                    @can('purchase.view')
                        <flux:navlist.item :href="route('purchases.view')" :current="request()->routeIs('purchases.*')" wire:navigate>{{ __('Purchases') }}</flux:navlist.item>
                    @endcan
                    @can('supplierpayment.view')
                        <flux:navlist.item :href="route('supplier-payments.view')" :current="request()->routeIs('supplier-payments.*')" wire:navigate>{{ __('Supplier Payments') }}</flux:navlist.item>
                    @endcan
                </flux:navlist.group>
            @endcanany

            <flux:spacer />
            <!-- <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-git-2" href="https://github.com/sanketjogdand/Nirmal-Industries/" target="_blank">
                {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                {{ __('Documentation') }}
                </flux:navlist.item>
            </flux:navlist> -->

            <!-- Desktop User Menu -->
            <flux:dropdown position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->getRoleNames()->first() }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

    @if (session('success'))
        <div class="toastr success">{{ session('success') }}</div>
    @endif

    @if (session('info'))
        <div class="toastr info">{{ session('info') }}</div>
    @endif

    @if (session('warning'))
        <div class="toastr warning">{{ session('warning') }}</div>
    @endif

    @if (session('danger'))
        <div class="toastr danger">{{ session('danger') }}</div>
    @endif

        {{ $slot }}

        @fluxScripts
    </body>
</html>
