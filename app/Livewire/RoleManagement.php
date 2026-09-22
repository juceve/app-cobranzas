<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleManagement extends Component
{
    public ?int $editingRoleId = null;

    public string $roleName = '';

    /** @var array<int, int> */
    public array $selectedPermissionIds = [];

    public bool $showRoleModal = false;

    public ?int $editingPermissionId = null;

    public string $permissionName = '';

    public string $permissionDisplayName = '';

    public string $permissionGroup = '';

    public bool $showPermissionModal = false;

    public function mount(): void
    {
        $this->currentAdministrator();
    }

    public function createRole(): void
    {
        $this->resetRoleForm();
        $this->showRoleModal = true;
    }

    public function editRole(int $roleId): void
    {
        $role = Role::with('permissions')->findOrFail($roleId);

        $this->editingRoleId = $role->id;
        $this->roleName = $role->name;
        $this->selectedPermissionIds = $role->permissions->pluck('id')->map(fn (int $id): int => $id)->all();
        $this->showRoleModal = true;
        $this->resetValidation();
    }

    public function saveRole(): void
    {
        $this->ensureAdministrator();

        $validated = $this->validate($this->roleRules());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function () use ($validated): void {
            $role = $this->editingRoleId === null
                ? Role::create(['name' => $validated['roleName'], 'guard_name' => 'web'])
                : tap(Role::findOrFail($this->editingRoleId))->update(['name' => $validated['roleName']]);

            $permissions = Permission::query()
                ->where('guard_name', 'web')
                ->whereKey($validated['selectedPermissionIds'])
                ->get();

            $role->syncPermissions($permissions);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $message = $this->editingRoleId === null
            ? 'El rol fue creado correctamente.'
            : 'El rol y sus permisos fueron actualizados correctamente.';

        $this->resetRoleForm();
        $this->dispatch('role-notification', icon: 'success', message: $message);
    }

    public function createPermission(): void
    {
        $this->resetPermissionForm();
        $this->showPermissionModal = true;
    }

    public function editPermission(int $permissionId): void
    {
        $permission = Permission::findOrFail($permissionId);

        $this->editingPermissionId = $permission->id;
        $this->permissionName = $permission->name;
        $this->permissionDisplayName = $permission->display_name;
        $this->permissionGroup = $permission->group;
        $this->showPermissionModal = true;
        $this->resetValidation();
    }

    public function savePermission(): void
    {
        $this->ensureAdministrator();

        $validated = $this->validate($this->permissionRules());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($this->editingPermissionId === null) {
            Permission::create([
                'name' => $validated['permissionName'],
                'display_name' => $validated['permissionDisplayName'],
                'group' => $validated['permissionGroup'],
                'guard_name' => 'web',
            ]);
        } else {
            Permission::findOrFail($this->editingPermissionId)->update([
                'name' => $validated['permissionName'],
                'display_name' => $validated['permissionDisplayName'],
                'group' => $validated['permissionGroup'],
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $message = $this->editingPermissionId === null
            ? 'El permiso fue creado correctamente.'
            : 'El permiso fue actualizado correctamente.';

        $this->resetPermissionForm();
        $this->dispatch('role-notification', icon: 'success', message: $message);
    }

    public function closeRoleModal(): void
    {
        $this->resetRoleForm();
    }

    public function closePermissionModal(): void
    {
        $this->resetPermissionForm();
    }

    public function render()
    {
        return view('livewire.role-management', [
            'roles' => Role::with('permissions')->orderBy('name')->get(),
            'permissionsByGroup' => Permission::query()
                ->orderBy('group')
                ->orderBy('display_name')
                ->get()
                ->groupBy('group'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function roleRules(): array
    {
        return [
            'roleName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->ignore($this->editingRoleId)
                    ->where('guard_name', 'web'),
            ],
            'selectedPermissionIds' => ['array'],
            'selectedPermissionIds.*' => [
                'integer',
                Rule::exists('permissions', 'id')->where('guard_name', 'web'),
            ],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function permissionRules(): array
    {
        return [
            'permissionName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')
                    ->ignore($this->editingPermissionId)
                    ->where('guard_name', 'web'),
            ],
            'permissionDisplayName' => ['required', 'string', 'max:255'],
            'permissionGroup' => ['required', 'string', 'max:255'],
        ];
    }

    private function resetRoleForm(): void
    {
        $this->reset(['editingRoleId', 'roleName', 'selectedPermissionIds', 'showRoleModal']);
        $this->resetValidation();
    }

    private function resetPermissionForm(): void
    {
        $this->reset([
            'editingPermissionId',
            'permissionName',
            'permissionDisplayName',
            'permissionGroup',
            'showPermissionModal',
        ]);
        $this->resetValidation();
    }

    private function ensureAdministrator(): void
    {
        $this->currentAdministrator();
    }

    private function currentAdministrator(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->hasRole('Admin'), 403);

        return $user;
    }
}
