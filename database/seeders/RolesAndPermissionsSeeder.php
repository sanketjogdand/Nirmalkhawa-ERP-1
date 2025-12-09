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
        ]);

        // Ensure registration can attach the Accountant role without errors
        $accountantRole = Role::firstOrCreate(['name' => 'Accountant']);

        $defaultUser = User::first();

        if ($defaultUser && ! $defaultUser->hasAnyRole(['Admin', 'Milk Operator'])) {
            $defaultUser->assignRole($adminRole);
        }
    }
}
