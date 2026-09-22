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

        $permissions = collect([
            ['name' => 'home', 'group' => 'Home', 'display_name' => 'Acceder al inicio'],
            ['name' => 'users.view', 'group' => 'Usuarios', 'display_name' => 'Ver usuarios'],
            ['name' => 'users.create', 'group' => 'Usuarios', 'display_name' => 'Crear usuarios'],
            ['name' => 'users.update', 'group' => 'Usuarios', 'display_name' => 'Editar usuarios'],
            ['name' => 'users.disable', 'group' => 'Usuarios', 'display_name' => 'Deshabilitar usuarios'],
            ['name' => 'companies.view', 'group' => 'Empresas', 'display_name' => 'Ver empresas'],
            ['name' => 'companies.create', 'group' => 'Empresas', 'display_name' => 'Crear empresas'],
            ['name' => 'companies.update', 'group' => 'Empresas', 'display_name' => 'Editar empresas'],
            ['name' => 'companies.disable', 'group' => 'Empresas', 'display_name' => 'Deshabilitar empresas'],
            ['name' => 'companies.enable', 'group' => 'Empresas', 'display_name' => 'Habilitar empresas'],
        ])->map(fn (array $permission): Permission => Permission::firstOrCreate(
            ['name' => $permission['name'], 'guard_name' => 'web'],
            ['group' => $permission['group'], 'display_name' => $permission['display_name']],
        ));

        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $guestRole = Role::firstOrCreate(['name' => 'Guest', 'guard_name' => 'web']);

        $adminRole->syncPermissions($permissions);
        $guestRole->syncPermissions([]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
