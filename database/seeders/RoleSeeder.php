<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $homePermission = Permission::firstOrCreate(
            ['name' => 'home', 'guard_name' => 'web'],
            ['group' => 'home', 'display_name' => 'Home'],
        );

        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $guestRole = Role::firstOrCreate(['name' => 'Guest', 'guard_name' => 'web']);

        $adminRole->syncPermissions([$homePermission]);
        $guestRole->syncPermissions([]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
