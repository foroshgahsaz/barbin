<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'inventory.upload',
            'inventory.confirm',
            'inventory.view',
            'inventory.history',
            'sales.upload',
            'sales.view_own',
            'sales.view_all',
            'sales.pdf',
            'sales.notify',
            'customers.manage_own',
            'customers.manage_all',
            'images.manage',
            'reports.analytics',
            'reports.warehouse_ops',
            'reports.readonly_exec',
            'settings.manage',
            'sms.manage',
            'messengers.manage',
            'users.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $rolePermissions = [
            RoleName::SuperAdmin->value => $permissions,
            RoleName::WarehouseManager->value => [
                'inventory.upload', 'inventory.confirm', 'inventory.view', 'inventory.history', 'reports.warehouse_ops',
            ],
            RoleName::SalesManager->value => [
                'sales.upload', 'sales.view_own', 'sales.pdf', 'sales.notify', 'customers.manage_own', 'images.manage',
            ],
            RoleName::SalesOperator->value => [
                'sales.upload', 'sales.view_own', 'sales.pdf', 'customers.manage_own',
            ],
            RoleName::OrganizationViewer->value => [
                'reports.analytics', 'reports.readonly_exec', 'inventory.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
            $role->syncPermissions($perms);
        }

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@barbin.local'],
            [
                'name' => 'مدیر کل',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
        );
        $admin->syncRoles([RoleName::SuperAdmin->value]);
    }
}