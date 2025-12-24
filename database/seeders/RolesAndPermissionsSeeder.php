<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed roles and permissions for centers.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'center.view',
            'center.create',
            'center.update',
            'center.delete',
            'ratechart.view',
            'ratechart.create',
            'ratechart.update',
            'ratechart.delete',
            'ratechart.assign',
            'commissionpolicy.view',
            'commissionpolicy.create',
            'commissionpolicy.update',
            'commissionpolicy.delete',
            'commissionassignment.view',
            'commissionassignment.create',
            'commissionassignment.update',
            'milkintake.view',
            'milkintake.create',
            'milkintake.update',
            'milkintake.delete',
            'milkintake.rate.override',
            'milkintake.apply_ratechart',
            'milkintake.lock',
            'milkintake.unlock',
            'centersettlement.view',
            'centersettlement.create',
            'centersettlement.update',
            'centersettlement.delete',
            'centersettlement.lock',
            'centersettlement.unlock',
            'settlementtemplate.manage',
            'product.view',
            'product.create',
            'product.update',
            'product.delete',
            'recipe.view',
            'recipe.create',
            'recipe.update',
            'recipe.delete',
            'recipe.activate',
            'recipe.override_output_product',
            'inventory.view',
            'inventory.adjust',
            'inventory.transfer',
            'production.view',
            'production.create',
            'production.update',
            'production.delete',
            'production.lock',
            'production.unlock',
            'packsize.view',
            'packsize.create',
            'packsize.update',
            'packsize.delete',
            'packing.create',
            'unpacking.create',
            'packinventory.view',
            'customer.view',
            'customer.create',
            'customer.update',
            'customer.delete',
            'receipt.view',
            'receipt.create',
            'receipt.update',
            'receipt.delete',
            'deliveryexpense.view',
            'deliveryexpense.create',
            'deliveryexpense.update',
            'deliveryexpense.delete',
            'supplier.view',
            'supplier.create',
            'supplier.update',
            'supplier.delete',
            'supplierpayment.view',
            'supplierpayment.create',
            'supplierpayment.update',
            'supplierpayment.delete',
            'dispatch.view',
            'dispatch.create',
            'dispatch.update',
            'dispatch.delete',
            'dispatch.post',
            'dispatch.lock',
            'dispatch.unlock',
            'salesinvoice.view',
            'salesinvoice.create',
            'salesinvoice.update',
            'salesinvoice.delete',
            'salesinvoice.post',
            'salesinvoice.lock',
            'salesinvoice.unlock',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->givePermissionTo($permissions);

        $milkOperatorRole = Role::firstOrCreate(['name' => 'Milk Operator']);
        $milkOperatorRole->syncPermissions([
            'center.view',
            'center.create',
            'ratechart.view',
            'milkintake.view',
            'milkintake.create',
            'centersettlement.view',
            'inventory.view',
            'inventory.transfer',
            'dispatch.view',
        ]);

        // Ensure registration can attach the Accountant role without errors
        $accountantRole = Role::firstOrCreate(['name' => 'Accountant']);
        $accountantRole->syncPermissions([
            'center.view',
            'center.create',
            'center.update',
            'ratechart.view',
            'ratechart.create',
            'ratechart.assign',
            'milkintake.view',
            'milkintake.create',
            'milkintake.rate.override',
            'milkintake.apply_ratechart',
            'milkintake.lock',
            'centersettlement.view',
            'centersettlement.create',
            'centersettlement.update',
            'centersettlement.lock',
            'inventory.view',
            'customer.view',
            'customer.create',
            'customer.update',
            'receipt.view',
            'receipt.create',
            'receipt.update',
            'receipt.delete',
            'deliveryexpense.view',
            'deliveryexpense.create',
            'deliveryexpense.update',
            'supplier.view',
            'supplier.create',
            'dispatch.view',
            'dispatch.create',
            'dispatch.update',
            'dispatch.post',
            'dispatch.lock',
            'salesinvoice.view',
            'salesinvoice.create',
            'salesinvoice.update',
            'salesinvoice.post',
            'salesinvoice.lock',
            'supplierpayment.view',
            'supplierpayment.create',
            'supplierpayment.update',
            'supplierpayment.delete',
        ]);

        $productionSupervisorRole = Role::firstOrCreate(['name' => 'Production Supervisor']);
        $productionSupervisorRole->syncPermissions([
            'recipe.view',
            'recipe.create',
            'recipe.update',
            'production.view',
            'production.create',
            'production.update',
            'production.lock',
            'dispatch.view',
        ]);

        $storeKeeperRole = Role::firstOrCreate(['name' => 'Storekeeper']);
        $storeKeeperRole->syncPermissions([
            'packing.create',
            'unpacking.create',
            'packinventory.view',
            'packsize.view',
            'dispatch.view',
            'dispatch.create',
            'dispatch.update',
            'dispatch.post',
        ]);

        $accountantRole->givePermissionTo([
            'production.view',
            'production.lock',
        ]);

        $defaultUser = User::first();

        if ($defaultUser && ! $defaultUser->hasAnyRole(['Admin', 'Milk Operator'])) {
            $defaultUser->assignRole($adminRole);
        }
    }
}
