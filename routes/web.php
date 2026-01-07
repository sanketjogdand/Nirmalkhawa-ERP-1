<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Center\Form as CenterForm;
use App\Livewire\Center\Show as CenterShow;
use App\Livewire\Center\View as CenterView;
use App\Livewire\CenterPayment\Form as CenterPaymentForm;
use App\Livewire\CenterPayment\Show as CenterPaymentShow;
use App\Livewire\CenterPayment\View as CenterPaymentView;
use App\Livewire\Customer\Form as CustomerForm;
use App\Livewire\Customer\Show as CustomerShow;
use App\Livewire\Customer\View as CustomerView;
use App\Livewire\CustomerReceipt\Form as CustomerReceiptForm;
use App\Livewire\CustomerReceipt\Show as CustomerReceiptShow;
use App\Livewire\CustomerReceipt\View as CustomerReceiptView;
use App\Livewire\Supplier\Form as SupplierForm;
use App\Livewire\Supplier\Show as SupplierShow;
use App\Livewire\Supplier\View as SupplierView;
use App\Livewire\MilkIntake\Form as MilkIntakeForm;
use App\Livewire\MilkIntake\View as MilkIntakeView;
use App\Livewire\Product\Form as ProductForm;
use App\Livewire\Product\View as ProductView;
use App\Livewire\Inventory\StockAdjustment\Form as StockAdjustmentForm;
use App\Livewire\Inventory\StockAdjustment\Show as StockAdjustmentShow;
use App\Livewire\Inventory\StockAdjustment\View as StockAdjustmentView;
use App\Livewire\Inventory\StockLedger as InventoryStockLedger;
use App\Livewire\Inventory\StockSummary as InventoryStockSummary;
use App\Livewire\Inventory\TransferToMix as InventoryTransferToMix;
use App\Livewire\Packing\History as PackHistory;
use App\Livewire\Packing\InventorySummary as PackInventory;
use App\Livewire\Packing\PackSizes;
use App\Livewire\Packing\PackingForm;
use App\Livewire\Packing\PackingView;
use App\Livewire\Packing\UnpackingForm;
use App\Livewire\Packing\UnpackingView;
use App\Livewire\Production\Form as ProductionForm;
use App\Livewire\Production\Show as ProductionShow;
use App\Livewire\Production\View as ProductionView;
use App\Livewire\MaterialConsumption\Form as MaterialConsumptionForm;
use App\Livewire\MaterialConsumption\Show as MaterialConsumptionShow;
use App\Livewire\MaterialConsumption\View as MaterialConsumptionView;
use App\Livewire\Dispatch\View as DispatchView;
use App\Livewire\Dispatch\Form as DispatchForm;
use App\Livewire\Dispatch\Show as DispatchShow;
use App\Livewire\DeliveryExpense\View as DeliveryExpenseView;
use App\Livewire\DeliveryExpense\Form as DeliveryExpenseForm;
use App\Livewire\SupplierPayment\Form as SupplierPaymentForm;
use App\Livewire\SupplierPayment\Show as SupplierPaymentShow;
use App\Livewire\SupplierPayment\View as SupplierPaymentView;
use App\Livewire\Purchase\Form as PurchaseForm;
use App\Livewire\Purchase\Show as PurchaseShow;
use App\Livewire\Purchase\View as PurchaseView;
use App\Livewire\Grn\Form as GrnForm;
use App\Livewire\Grn\Show as GrnShow;
use App\Livewire\Grn\View as GrnView;
use App\Livewire\Employee\Form as EmployeeForm;
use App\Livewire\Employee\View as EmployeeView;
use App\Livewire\EmployeeAttendance\Form as EmployeeAttendanceForm;
use App\Livewire\EmployeeAttendance\View as EmployeeAttendanceView;
use App\Livewire\EmployeeIncentive\Form as EmployeeIncentiveForm;
use App\Livewire\EmployeeIncentive\View as EmployeeIncentiveView;
use App\Livewire\EmployeePayment\Form as EmployeePaymentForm;
use App\Livewire\EmployeePayment\View as EmployeePaymentView;
use App\Livewire\EmployeePayroll\Form as EmployeePayrollForm;
use App\Livewire\EmployeePayroll\View as EmployeePayrollView;
use App\Livewire\EmployeeSalaryRate\View as EmployeeSalaryRateView;
use App\Livewire\EmploymentPeriod\View as EmploymentPeriodView;
use App\Livewire\RateChart\Calculator as RateChartCalculator;
use App\Livewire\RateChart\Form as RateChartForm;
use App\Livewire\RateChart\Show as RateChartShow;
use App\Livewire\RateChart\View as RateChartView;
use App\Livewire\CenterSettlement\View as CenterSettlementView;
use App\Livewire\CenterSettlement\Form as CenterSettlementForm;
use App\Livewire\CenterSettlement\Show as CenterSettlementShow;
use App\Livewire\SettlementTemplate\View as SettlementTemplateView;
use App\Livewire\CommissionPolicy\View as CommissionPolicyView;
use App\Livewire\CommissionPolicy\Form as CommissionPolicyForm;
use App\Livewire\CommissionPolicy\Assign as CommissionAssignment;
use App\Livewire\Recipe\Form as RecipeForm;
use App\Livewire\Recipe\Show as RecipeShow;
use App\Livewire\Recipe\View as RecipeView;
use App\Livewire\SalesInvoice\Form as SalesInvoiceForm;
use App\Livewire\SalesInvoice\Show as SalesInvoiceShow;
use App\Livewire\SalesInvoice\View as SalesInvoiceView;
use App\Livewire\Setup;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::middleware('permission:ratechart.view')->group(function () {
        Route::get('rate-charts', RateChartView::class)->name('rate-charts.view');
        Route::get('rate-charts/calculator', RateChartCalculator::class)->name('rate-charts.calculator');
        Route::get('rate-charts/{rateChart}', RateChartShow::class)
            ->whereNumber('rateChart')
            ->name('rate-charts.show');
    });

    Route::middleware('permission:commissionpolicy.view')->group(function () {
        Route::get('commission-policies', CommissionPolicyView::class)->name('commission-policies.view');
    });
    Route::get('commission-policies/create', CommissionPolicyForm::class)
        ->middleware('permission:commissionpolicy.create')
        ->name('commission-policies.create');
    Route::get('commission-policies/{policy}/edit', CommissionPolicyForm::class)
        ->middleware('permission:commissionpolicy.update')
        ->whereNumber('policy')
        ->name('commission-policies.edit');
    Route::get('commission-assignments', CommissionAssignment::class)
        ->middleware('permission:commissionassignment.view')
        ->name('commission-assignments');

    Route::get('rate-charts/create', RateChartForm::class)
        ->middleware('permission:ratechart.create')
        ->name('rate-charts.create');

    Route::get('rate-charts/{rateChart}/edit', RateChartForm::class)
        ->middleware('permission:ratechart.update')
        ->whereNumber('rateChart')
        ->name('rate-charts.edit');

    Route::get('centers/create', CenterForm::class)
        ->middleware('permission:center.create')
        ->name('centers.create');

    Route::middleware('permission:center.view')->group(function () {
        Route::get('centers', CenterView::class)->name('centers.view');
        Route::get('centers/{center}', CenterShow::class)
            ->whereNumber('center')
            ->name('centers.show');
    });

    Route::get('centers/{center}/edit', CenterForm::class)
        ->middleware('permission:center.update')
        ->whereNumber('center')
        ->name('centers.edit');

    Route::middleware('permission:milkintake.view')->group(function () {
        Route::get('milk-intakes', MilkIntakeView::class)->name('milk-intakes.view');
    });

    Route::get('milk-intakes/create', MilkIntakeForm::class)
        ->middleware('permission:milkintake.create')
        ->name('milk-intakes.create');

    Route::get('milk-intakes/{milkIntake}/edit', MilkIntakeForm::class)
        ->middleware('permission:milkintake.update')
        ->whereNumber('milkIntake')
        ->name('milk-intakes.edit');

    Route::get('center-settlements/create', CenterSettlementForm::class)
        ->middleware('permission:centersettlement.create')
        ->name('center-settlements.create');

    Route::get('center-settlements/{settlement}/edit', CenterSettlementForm::class)
        ->middleware('permission:centersettlement.update')
        ->whereNumber('settlement')
        ->name('center-settlements.edit');

    Route::middleware('permission:centersettlement.view')->group(function () {
        Route::get('center-settlements', CenterSettlementView::class)->name('center-settlements.view');
        Route::get('center-settlements/{settlement}', CenterSettlementShow::class)
            ->whereNumber('settlement')
            ->name('center-settlements.show');
    });

    Route::middleware('permission:centerpayment.view')->group(function () {
        Route::get('center-payments', CenterPaymentView::class)->name('center-payments.view');
        Route::get('center-payments/{payment}', CenterPaymentShow::class)
            ->whereNumber('payment')
            ->name('center-payments.show');
    });
    Route::get('center-payments/create', CenterPaymentForm::class)
        ->middleware('permission:centerpayment.create')
        ->name('center-payments.create');
    Route::get('center-payments/{payment}/edit', CenterPaymentForm::class)
        ->middleware('permission:centerpayment.update')
        ->whereNumber('payment')
        ->name('center-payments.edit');

    Route::middleware('permission:employee.view')->group(function () {
        Route::get('employees', EmployeeView::class)->name('employees.view');
    });
    Route::get('employees/create', EmployeeForm::class)
        ->middleware('permission:employee.create')
        ->name('employees.create');
    Route::get('employees/{employee}/edit', EmployeeForm::class)
        ->middleware('permission:employee.update')
        ->whereNumber('employee')
        ->name('employees.edit');
    Route::get('employees/{employee}/employment-periods', EmploymentPeriodView::class)
        ->middleware('permission:employment_period.manage')
        ->whereNumber('employee')
        ->name('employees.employment-periods');
    Route::get('employees/{employee}/salary-rates', EmployeeSalaryRateView::class)
        ->middleware('permission:salary_rate.view')
        ->whereNumber('employee')
        ->name('employee-salary-rates.view');

    Route::middleware('permission:attendance.view')->group(function () {
        Route::get('employee-attendance', EmployeeAttendanceView::class)->name('employee-attendance.view');
    });
    Route::get('employee-attendance/create', EmployeeAttendanceForm::class)
        ->middleware('permission:attendance.create')
        ->name('employee-attendance.create');
    Route::get('employee-attendance/{attendance}/edit', EmployeeAttendanceForm::class)
        ->middleware('permission:attendance.update')
        ->whereNumber('attendance')
        ->name('employee-attendance.edit');

    Route::middleware('permission:incentive.view')->group(function () {
        Route::get('employee-incentives', EmployeeIncentiveView::class)->name('employee-incentives.view');
    });
    Route::get('employee-incentives/create', EmployeeIncentiveForm::class)
        ->middleware('permission:incentive.create')
        ->name('employee-incentives.create');
    Route::get('employee-incentives/{incentive}/edit', EmployeeIncentiveForm::class)
        ->middleware('permission:incentive.update')
        ->whereNumber('incentive')
        ->name('employee-incentives.edit');

    Route::middleware('permission:employee_payment.view')->group(function () {
        Route::get('employee-payments', EmployeePaymentView::class)->name('employee-payments.view');
    });
    Route::get('employee-payments/create', EmployeePaymentForm::class)
        ->middleware('permission:employee_payment.create')
        ->name('employee-payments.create');
    Route::get('employee-payments/{payment}/edit', EmployeePaymentForm::class)
        ->middleware('permission:employee_payment.update')
        ->whereNumber('payment')
        ->name('employee-payments.edit');

    Route::middleware('permission:payroll.view')->group(function () {
        Route::get('employee-payrolls', EmployeePayrollView::class)->name('employee-payrolls.view');
    });
    Route::get('employee-payrolls/create', EmployeePayrollForm::class)
        ->middleware('permission:payroll.create')
        ->name('employee-payrolls.create');
    Route::get('employee-payrolls/{payroll}/edit', EmployeePayrollForm::class)
        ->middleware('permission:payroll.update')
        ->whereNumber('payroll')
        ->name('employee-payrolls.edit');

    Route::get('settlement-templates', SettlementTemplateView::class)
        ->middleware('permission:settlementtemplate.manage')
        ->name('settlement-templates');

    Route::middleware('permission:product.view')->group(function () {
        Route::get('products', ProductView::class)->name('products.view');
    });

    Route::get('products/create', ProductForm::class)
        ->middleware('permission:product.create')
        ->name('products.create');

    Route::get('products/{product}/edit', ProductForm::class)
        ->middleware('permission:product.update')
        ->whereNumber('product')
        ->name('products.edit');

    Route::middleware('permission:supplier.view')->group(function () {
        Route::get('suppliers', SupplierView::class)->name('suppliers.view');
        Route::get('suppliers/{supplier}', SupplierShow::class)
            ->whereNumber('supplier')
            ->name('suppliers.show');
    });

    Route::get('suppliers/create', SupplierForm::class)
        ->middleware('permission:supplier.create')
        ->name('suppliers.create');

    Route::get('suppliers/{supplier}/edit', SupplierForm::class)
        ->middleware('permission:supplier.update')
        ->whereNumber('supplier')
        ->name('suppliers.edit');

    Route::middleware('permission:customer.view')->group(function () {
        Route::get('customers', CustomerView::class)->name('customers.view');
        Route::get('customers/{customer}', CustomerShow::class)
            ->whereNumber('customer')
            ->name('customers.show');
    });

    Route::get('customers/create', CustomerForm::class)
        ->middleware('permission:customer.create')
        ->name('customers.create');

    Route::get('customers/{customer}/edit', CustomerForm::class)
        ->middleware('permission:customer.update')
        ->whereNumber('customer')
        ->name('customers.edit');

    Route::middleware('permission:recipe.view')->group(function () {
        Route::get('recipes', RecipeView::class)->name('recipes.view');
        Route::get('recipes/{recipe}', RecipeShow::class)
            ->middleware('permission:recipe.view')
            ->whereNumber('recipe')
            ->name('recipes.show');
    });

    Route::get('recipes/create', RecipeForm::class)
        ->middleware('permission:recipe.create')
        ->name('recipes.create');

    Route::get('recipes/{recipe}/edit', RecipeForm::class)
        ->middleware('permission:recipe.update')
        ->whereNumber('recipe')
        ->name('recipes.edit');

    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('inventory/stock-summary', InventoryStockSummary::class)->name('inventory.stock-summary');
        Route::get('inventory/stock-ledger', InventoryStockLedger::class)->name('inventory.stock-ledger');
    });

    Route::middleware('permission:stockadjustment.view')->group(function () {
        Route::get('inventory/stock-adjustments', StockAdjustmentView::class)
            ->name('inventory.stock-adjustments');
        Route::get('inventory/stock-adjustments/{stockAdjustment}', StockAdjustmentShow::class)
            ->whereNumber('stockAdjustment')
            ->name('inventory.stock-adjustments.show');
    });

    Route::get('inventory/stock-adjustments/create', StockAdjustmentForm::class)
        ->middleware('permission:stockadjustment.create')
        ->name('inventory.stock-adjustments.create');

    Route::get('inventory/stock-adjustments/{stockAdjustment}/edit', StockAdjustmentForm::class)
        ->middleware('permission:stockadjustment.update')
        ->whereNumber('stockAdjustment')
        ->name('inventory.stock-adjustments.edit');

    Route::get('inventory/transfer-to-mix', InventoryTransferToMix::class)
        ->middleware('permission:inventory.transfer')
        ->name('inventory.transfer-to-mix');

    Route::get('pack-sizes', PackSizes::class)
        ->middleware('permission:packsize.view')
        ->name('pack-sizes');

    Route::get('packings', PackingView::class)
        ->middleware('permission:packing.view')
        ->name('packings.view');
    Route::get('packings/create', PackingForm::class)
        ->middleware('permission:packing.create')
        ->name('packings.create');
    Route::get('packings/{packing}', PackingForm::class)
        ->middleware('permission:packing.view')
        ->whereNumber('packing')
        ->name('packings.show');
    Route::get('packings/{packing}/edit', PackingForm::class)
        ->middleware('permission:packing.update')
        ->whereNumber('packing')
        ->name('packings.edit');

    Route::get('unpackings', UnpackingView::class)
        ->middleware('permission:unpacking.view')
        ->name('unpackings.view');
    Route::get('unpackings/create', UnpackingForm::class)
        ->middleware('permission:unpacking.create')
        ->name('unpackings.create');
    Route::get('unpackings/{unpacking}', UnpackingForm::class)
        ->middleware('permission:unpacking.view')
        ->whereNumber('unpacking')
        ->name('unpackings.show');
    Route::get('unpackings/{unpacking}/edit', UnpackingForm::class)
        ->middleware('permission:unpacking.update')
        ->whereNumber('unpacking')
        ->name('unpackings.edit');

    Route::get('pack-inventory', PackInventory::class)
        ->middleware('permission:packinventory.view')
        ->name('pack-inventory');

    Route::get('pack-history', PackHistory::class)
        ->middleware('permission:packinventory.view')
        ->name('pack-history');

    Route::get('dispatches/create', DispatchForm::class)
        ->middleware('permission:dispatch.create')
        ->name('dispatches.create');

    Route::get('dispatches/{dispatch}/edit', DispatchForm::class)
        ->middleware('permission:dispatch.update')
        ->whereNumber('dispatch')
        ->name('dispatches.edit');

    Route::get('delivery-expenses', DeliveryExpenseView::class)
        ->middleware('permission:deliveryexpense.view')
        ->name('delivery-expenses.view');
    Route::get('delivery-expenses/create', DeliveryExpenseForm::class)
        ->middleware('permission:deliveryexpense.create')
        ->name('delivery-expenses.create');
    Route::get('delivery-expenses/{expense}/edit', DeliveryExpenseForm::class)
        ->middleware('permission:deliveryexpense.update')
        ->whereNumber('expense')
        ->name('delivery-expenses.edit');

    Route::middleware('permission:supplierpayment.view')->group(function () {
        Route::get('supplier-payments', SupplierPaymentView::class)->name('supplier-payments.view');
        Route::get('supplier-payments/{payment}', SupplierPaymentShow::class)
            ->whereNumber('payment')
            ->name('supplier-payments.show');
    });
    Route::get('supplier-payments/create', SupplierPaymentForm::class)
        ->middleware('permission:supplierpayment.create')
        ->name('supplier-payments.create');
    Route::get('supplier-payments/{payment}/edit', SupplierPaymentForm::class)
        ->middleware('permission:supplierpayment.update')
        ->whereNumber('payment')
        ->name('supplier-payments.edit');

    Route::middleware('permission:purchase.view')->group(function () {
        Route::get('purchases', PurchaseView::class)->name('purchases.view');
        Route::get('purchases/{purchase}', PurchaseShow::class)
            ->whereNumber('purchase')
            ->name('purchases.show');
    });
    Route::get('purchases/create', PurchaseForm::class)
        ->middleware('permission:purchase.create')
        ->name('purchases.create');
    Route::get('purchases/{purchase}/edit', PurchaseForm::class)
        ->middleware('permission:purchase.update')
        ->whereNumber('purchase')
        ->name('purchases.edit');

    Route::middleware('permission:grn.view')->group(function () {
        Route::get('grns', GrnView::class)->name('grns.view');
        Route::get('grns/{grn}', GrnShow::class)
            ->whereNumber('grn')
            ->name('grns.show');
    });
    Route::get('grns/create', GrnForm::class)
        ->middleware('permission:grn.create')
        ->name('grns.create');
    Route::get('grns/{grn}/edit', GrnForm::class)
        ->middleware('permission:grn.update')
        ->whereNumber('grn')
        ->name('grns.edit');

    Route::middleware('permission:dispatch.view')->group(function () {
        Route::get('dispatches', DispatchView::class)->name('dispatches.view');
        Route::get('dispatches/{dispatch}', DispatchShow::class)
            ->middleware('permission:dispatch.view')
            ->whereNumber('dispatch')
            ->name('dispatches.show');
    });

    Route::get('sales-invoices/create', SalesInvoiceForm::class)
        ->middleware('permission:salesinvoice.create')
        ->name('sales-invoices.create');

    Route::get('sales-invoices/{salesInvoice}/edit', SalesInvoiceForm::class)
        ->middleware('permission:salesinvoice.update')
        ->whereNumber('salesInvoice')
        ->name('sales-invoices.edit');

    Route::middleware('permission:salesinvoice.view')->group(function () {
        Route::get('sales-invoices', SalesInvoiceView::class)->name('sales-invoices.view');
        Route::get('sales-invoices/{salesInvoice}', SalesInvoiceShow::class)
            ->middleware('permission:salesinvoice.view')
            ->whereNumber('salesInvoice')
            ->name('sales-invoices.show');
    });

    Route::middleware('permission:receipt.view')->group(function () {
        Route::get('customer-receipts', CustomerReceiptView::class)->name('customer-receipts.view');
        Route::get('customer-receipts/{receipt}', CustomerReceiptShow::class)
            ->whereNumber('receipt')
            ->name('customer-receipts.show');
    });
    Route::get('customer-receipts/create', CustomerReceiptForm::class)
        ->middleware('permission:receipt.create')
        ->name('customer-receipts.create');
    Route::get('customer-receipts/{receipt}/edit', CustomerReceiptForm::class)
        ->middleware('permission:receipt.update')
        ->whereNumber('receipt')
        ->name('customer-receipts.edit');

    Route::middleware('permission:production.view')->group(function () {
        Route::get('productions', ProductionView::class)->name('productions.view');
    });

    Route::get('productions/create', ProductionForm::class)
        ->middleware('permission:production.create')
        ->name('productions.create');

    Route::get('productions/{production}', ProductionShow::class)
        ->middleware('permission:production.view')
        ->whereNumber('production')
        ->name('productions.show');

    Route::get('productions/{production}/edit', ProductionForm::class)
        ->middleware('permission:production.update')
        ->whereNumber('production')
        ->name('productions.edit');

    Route::middleware('permission:materialconsumption.view')->group(function () {
        Route::get('material-consumptions', MaterialConsumptionView::class)->name('material-consumptions.view');
        Route::get('material-consumptions/{materialConsumption}', MaterialConsumptionShow::class)
            ->whereNumber('materialConsumption')
            ->name('material-consumptions.show');
    });

    Route::get('material-consumptions/create', MaterialConsumptionForm::class)
        ->middleware('permission:materialconsumption.create')
        ->name('material-consumptions.create');

    Route::get('material-consumptions/{materialConsumption}/edit', MaterialConsumptionForm::class)
        ->middleware('permission:materialconsumption.update')
        ->whereNumber('materialConsumption')
        ->name('material-consumptions.edit');

    Route::get('/setup', Setup::class)->name('setup');
    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

require __DIR__.'/auth.php';
