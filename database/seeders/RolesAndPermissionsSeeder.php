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
            'milkintake.view',
            'milkintake.create',
            'milkintake.update',
            'milkintake.delete',
            'milkintake.rate.override',
            'milkintake.apply_ratechart',
            'milkintake.lock',
            'milkintake.unlock',
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
            'inventory.view',
            'inventory.transfer',
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
            'inventory.view',
            'customer.view',
            'customer.create',
            'customer.update',
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
        ]);

        $storeKeeperRole = Role::firstOrCreate(['name' => 'Storekeeper']);
        $storeKeeperRole->syncPermissions([
            'packing.create',
            'unpacking.create',
            'packinventory.view',
            'packsize.view',
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
