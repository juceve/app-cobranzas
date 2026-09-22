<?php

namespace Tests\Feature\Livewire;

use App\Livewire\CompanyManagement;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_companies_page(): void
    {
        $this->actingAs($this->administrator());

        $this->get(route('companies.index'))
            ->assertOk()
            ->assertSeeLivewire(CompanyManagement::class);
    }

    public function test_administrator_can_create_company(): void
    {
        $this->actingAs($this->administrator());

        Livewire::test(CompanyManagement::class)
            ->call('createCompany')
            ->set('name', 'Cobranza Andina')
            ->set('nit', '1234567890')
            ->set('phone', '022345678')
            ->set('address', 'Av. Principal 123')
            ->set('contactPerson', 'Ana Pérez')
            ->set('contactPhone', '0991234567')
            ->call('saveCompany')
            ->assertHasNoErrors()
            ->assertDispatched('company-notification');

        $this->assertDatabaseHas('companies', [
            'name' => 'Cobranza Andina',
            'nit' => '1234567890',
            'contact_person' => 'Ana Pérez',
        ]);
    }

    public function test_administrator_can_update_disable_and_enable_company(): void
    {
        $this->actingAs($this->administrator());
        $company = Company::factory()->create();

        Livewire::test(CompanyManagement::class)
            ->call('editCompany', $company->id)
            ->set('name', 'Empresa actualizada')
            ->set('nit', $company->nit)
            ->call('saveCompany')
            ->assertHasNoErrors()
            ->call('disableCompany', $company->id)
            ->assertDispatched('company-notification')
            ->call('enableCompany', $company->id)
            ->assertDispatched('company-notification');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Empresa actualizada',
            'is_active' => true,
        ]);
    }

    private function administrator(): User
    {
        $permissions = collect([
            ['name' => 'companies.view', 'display_name' => 'Ver empresas'],
            ['name' => 'companies.create', 'display_name' => 'Crear empresas'],
            ['name' => 'companies.update', 'display_name' => 'Editar empresas'],
            ['name' => 'companies.disable', 'display_name' => 'Deshabilitar empresas'],
            ['name' => 'companies.enable', 'display_name' => 'Habilitar empresas'],
        ])->map(fn (array $permission): Permission => Permission::create([
            ...$permission,
            'group' => 'Empresas',
        ]));

        $role = Role::findOrCreate('Admin');
        $role->syncPermissions($permissions);

        $administrator = User::factory()->create();
        $administrator->assignRole($role);

        return $administrator;
    }
}
