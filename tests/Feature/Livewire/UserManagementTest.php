<?php

namespace Tests\Feature\Livewire;

use App\Livewire\UserManagement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_the_users_page(): void
    {
        $this->actingAs($this->administrator());

        $this->get(route('users.index'))
            ->assertOk()
            ->assertSeeLivewire(UserManagement::class);
    }

    public function test_administrator_can_create_a_user_and_assign_a_role(): void
    {
        $this->actingAs($this->administrator());

        Livewire::test(UserManagement::class)
            ->call('createUser')
            ->set('name', 'Usuario de prueba')
            ->set('email', 'usuario@example.com')
            ->set('celular', '0991234567')
            ->set('direccion', 'Calle de prueba 123')
            ->set('password', 'password123')
            ->set('passwordConfirmation', 'password123')
            ->set('selectedRole', 'Guest')
            ->call('saveUser')
            ->assertHasNoErrors()
            ->assertDispatched('user-notification');

        $user = User::where('email', 'usuario@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('Guest'));
        $this->assertDatabaseHas('users', [
            'email' => 'usuario@example.com',
            'celular' => '0991234567',
        ]);
    }

    public function test_administrator_can_update_a_user_and_sync_roles(): void
    {
        $this->actingAs($this->administrator());
        $user = User::factory()->create();
        $user->assignRole('Guest');

        Livewire::test(UserManagement::class)
            ->call('editUser', $user->id)
            ->set('name', 'Nombre actualizado')
            ->set('email', $user->email)
            ->set('selectedRole', 'Admin')
            ->call('saveUser')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('Nombre actualizado', $user->name);
        $this->assertTrue($user->hasRole('Admin'));
        $this->assertFalse($user->hasRole('Guest'));
    }

    public function test_administrator_cannot_disable_their_own_user(): void
    {
        $administrator = $this->administrator();
        $this->actingAs($administrator);

        Livewire::test(UserManagement::class)
            ->call('disableUser', $administrator->id)
            ->assertDispatched('user-notification');

        $this->assertDatabaseHas('users', ['id' => $administrator->id, 'is_active' => true]);
    }

    public function test_administrator_can_disable_another_user_without_deleting_it(): void
    {
        $this->actingAs($this->administrator());
        $user = User::factory()->create();
        $user->assignRole('Guest');

        Livewire::test(UserManagement::class)
            ->call('disableUser', $user->id)
            ->assertDispatched('user-notification');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
    }

    public function test_disabled_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    private function administrator(): User
    {
        $permissions = collect([
            ['name' => 'users.view', 'display_name' => 'Ver usuarios'],
            ['name' => 'users.create', 'display_name' => 'Crear usuarios'],
            ['name' => 'users.update', 'display_name' => 'Editar usuarios'],
            ['name' => 'users.disable', 'display_name' => 'Deshabilitar usuarios'],
        ])->map(fn (array $permission): Permission => Permission::create([
            ...$permission,
            'group' => 'Usuarios',
        ]));

        $adminRole = Role::findOrCreate('Admin');
        $adminRole->syncPermissions($permissions);
        Role::findOrCreate('Guest');

        $administrator = User::factory()->create();
        $administrator->assignRole($adminRole);

        return $administrator;
    }
}
