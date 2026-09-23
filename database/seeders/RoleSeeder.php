<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ─────────────────────────────────────────────
        //  PERMISOS
        // ─────────────────────────────────────────────
        $permissions = collect([
            // Sistema
            ['name' => 'home',                 'group' => 'Sistema', 'display_name' => 'Acceder al inicio'],

            // Usuarios
            ['name' => 'users.view',           'group' => 'Usuarios', 'display_name' => 'Ver usuarios'],
            ['name' => 'users.create',         'group' => 'Usuarios', 'display_name' => 'Crear usuarios'],
            ['name' => 'users.update',         'group' => 'Usuarios', 'display_name' => 'Editar usuarios'],
            ['name' => 'users.disable',        'group' => 'Usuarios', 'display_name' => 'Deshabilitar usuarios'],

            // Roles
            ['name' => 'roles.view',           'group' => 'Roles', 'display_name' => 'Ver roles y permisos'],
            ['name' => 'roles.assign',         'group' => 'Roles', 'display_name' => 'Asignar roles'],

            // Empresas
            ['name' => 'companies.view',       'group' => 'Empresas', 'display_name' => 'Ver empresas'],
            ['name' => 'companies.create',     'group' => 'Empresas', 'display_name' => 'Crear empresas'],
            ['name' => 'companies.update',     'group' => 'Empresas', 'display_name' => 'Editar empresas'],
            ['name' => 'companies.disable',    'group' => 'Empresas', 'display_name' => 'Deshabilitar empresas'],
            ['name' => 'companies.enable',     'group' => 'Empresas', 'display_name' => 'Habilitar empresas'],

            // Carteras
            ['name' => 'carteras.view',        'group' => 'Carteras', 'display_name' => 'Ver carteras'],
            ['name' => 'carteras.create',      'group' => 'Carteras', 'display_name' => 'Crear carteras'],
            ['name' => 'carteras.update',      'group' => 'Carteras', 'display_name' => 'Editar carteras'],

            // Padrón de deudores
            ['name' => 'deudores.view',        'group' => 'Deudores', 'display_name' => 'Ver padrón de deudores'],
            ['name' => 'deudores.export',      'group' => 'Deudores', 'display_name' => 'Exportar padrón de deudores'],

            // Análisis de cartera
            ['name' => 'analisis.view',        'group' => 'Análisis', 'display_name' => 'Ver análisis de cartera'],

            // Lotes
            ['name' => 'lotes.view',           'group' => 'Lotes', 'display_name' => 'Ver lotes'],
            ['name' => 'lotes.create',         'group' => 'Lotes', 'display_name' => 'Crear lotes'],
            ['name' => 'lotes.update',         'group' => 'Lotes', 'display_name' => 'Editar lotes'],
            ['name' => 'lotes.assign',         'group' => 'Lotes', 'display_name' => 'Asignar lotes a cobradores'],
            ['name' => 'lotes.cancel',         'group' => 'Lotes', 'display_name' => 'Cancelar lotes'],
            ['name' => 'lotes.close',          'group' => 'Lotes', 'display_name' => 'Cerrar lotes'],
            ['name' => 'lotes.view_all',       'group' => 'Lotes', 'display_name' => 'Ver todos los lotes (no solo los propios)'],

            // Plataforma del cobrador (mobile/desktop)
            ['name' => 'cobrador.access',      'group' => 'Cobrador', 'display_name' => 'Acceder a la plataforma del cobrador'],
            ['name' => 'cobrador.gestionar',   'group' => 'Cobrador', 'display_name' => 'Registrar gestiones de cobro'],
        ])->map(fn(array $p) => Permission::firstOrCreate(
            ['name' => $p['name'], 'guard_name' => 'web'],
            ['group' => $p['group'], 'display_name' => $p['display_name']],
        ));

        // ─────────────────────────────────────────────
        //  ROLES
        // ─────────────────────────────────────────────
        $adminRole    = Role::firstOrCreate(['name' => 'Admin',         'guard_name' => 'web']);
        $jefeVenta = Role::firstOrCreate(['name' => 'JefeVentas', 'guard_name' => 'web']);
        $cobradorRole = Role::firstOrCreate(['name' => 'Cobrador',      'guard_name' => 'web']);
        $guestRole    = Role::firstOrCreate(['name' => 'Guest',         'guard_name' => 'web']);

        // ─────────────────────────────────────────────
        //  ASIGNACIÓN DE PERMISOS
        // ─────────────────────────────────────────────

        // Admin: todos los permisos
        $adminRole->syncPermissions($permissions);

        // Jefe de Ventas: opera sobre su empresa, no administra usuarios/roles
        $jefeVenta->syncPermissions([
            'home',
            'carteras.view',
            'deudores.view',
            'deudores.export',
            'analisis.view',
            'lotes.view',
            'lotes.view_all',   // ve todos los lotes de su empresa
            'lotes.create',
            'lotes.update',
            'lotes.assign',
            'lotes.cancel',
        ]);

        // Cobrador: solo plataforma de campo, ve sus propios lotes
        $cobradorRole->syncPermissions([
            'home',
            'cobrador.access',
            'cobrador.gestionar',
            'lotes.view',       // solo los suyos (se filtra por cobrador_id)
        ]);

        // Guest: sin permisos
        $guestRole->syncPermissions([]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
