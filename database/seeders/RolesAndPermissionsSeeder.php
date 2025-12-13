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
        ]);

        $defaultUser = User::first();

        if ($defaultUser && ! $defaultUser->hasAnyRole(['Admin', 'Milk Operator'])) {
            $defaultUser->assignRole($adminRole);
        }
    }
}
