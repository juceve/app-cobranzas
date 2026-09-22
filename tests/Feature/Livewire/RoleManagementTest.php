<?php

namespace Tests\Feature\Livewire;

use App\Livewire\RoleManagement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_roles_and_permissions_page(): void
    {
        $this->actingAs($this->administrator());

        $this->get(route('roles.index'))
            ->assertOk()
            ->assertSeeLivewire(RoleManagement::class);
    }

    public function test_administrator_can_create_permission_and_assign_it_to_a_role(): void
    {
        $this->actingAs($this->administrator());

        Livewire::test(RoleManagement::class)
            ->call('createPermission')
            ->set('permissionName', 'customers.view')
            ->set('permissionDisplayName', 'Ver clientes')
            ->set('permissionGroup', 'Clientes')
            ->call('savePermission')
            ->assertHasNoErrors();

        $permission = Permission::where('name', 'customers.view')->firstOrFail();

        Livewire::test(RoleManagement::class)
            ->call('createRole')
            ->set('roleName', 'Cobrador')
            ->set('selectedPermissionIds', [(string) $permission->id])
            ->call('saveRole')
            ->assertHasNoErrors()
            ->assertDispatched('role-notification');

        $role = Role::where('name', 'Cobrador')->firstOrFail();

        $this->assertTrue($role->hasPermissionTo($permission));
    }

    public function test_administrator_can_edit_role_permissions(): void
    {
        $this->actingAs($this->administrator());
        $firstPermission = Permission::create([
            'name' => 'customers.view',
            'display_name' => 'Ver clientes',
            'group' => 'Clientes',
        ]);
        $secondPermission = Permission::create([
            'name' => 'customers.edit',
            'display_name' => 'Editar clientes',
            'group' => 'Clientes',
        ]);
        $role = Role::create(['name' => 'Cobrador']);
        $role->syncPermissions([$firstPermission]);

        Livewire::test(RoleManagement::class)
            ->call('editRole', $role->id)
            ->set('selectedPermissionIds', [(string) $secondPermission->id])
            ->call('saveRole')
            ->assertHasNoErrors();

        $this->assertFalse($role->fresh()->hasPermissionTo($firstPermission));
        $this->assertTrue($role->fresh()->hasPermissionTo($secondPermission));
    }

    private function administrator(): User
    {
        $role = Role::findOrCreate('Admin');
        $administrator = User::factory()->create();
        $administrator->assignRole($role);

        return $administrator;
    }
}
