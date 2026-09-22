<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UserManagement extends Component
{
    use WithPagination;

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public ?string $celular = null;

    public ?string $direccion = null;

    public string $password = '';

    public string $passwordConfirmation = '';

    public ?string $selectedRole = null;

    public string $search = '';

    public bool $showUserModal = false;

    protected string $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->ensurePermission('users.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function createUser(): void
    {
        $this->ensurePermission('users.create');

        $this->resetForm();
        $this->showUserModal = true;
    }

    public function editUser(int $userId): void
    {
        $this->ensurePermission('users.update');

        $user = User::findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->celular = $user->celular;
        $this->direccion = $user->direccion;
        $this->selectedRole = $user->getRoleNames()->first();
        $this->showUserModal = true;
        $this->resetValidation();
    }

    public function saveUser(): void
    {
        $this->ensurePermission($this->editingUserId === null ? 'users.create' : 'users.update');

        $validated = $this->validate();
        $attributes = Arr::except($validated, ['selectedRole', 'passwordConfirmation']);

        if ($this->editingUserId !== null && $attributes['password'] === null) {
            unset($attributes['password']);
        }

        $user = DB::transaction(function () use ($attributes, $validated): User {
            $user = $this->editingUserId === null
                ? User::create($attributes)
                : tap(User::findOrFail($this->editingUserId))->update($attributes);

            $user->syncRoles([$validated['selectedRole']]);

            return $user;
        });

        $message = $this->editingUserId === null
            ? 'El usuario fue creado correctamente.'
            : 'El usuario fue actualizado correctamente.';

        $this->resetForm();
        $this->dispatch('user-notification', icon: 'success', message: $message);
    }

    #[On('disable-user')]
    public function disableUser(int $userId): void
    {
        $this->ensurePermission('users.disable');

        $user = User::findOrFail($userId);

        if ($user->is($this->currentUser())) {
            $this->dispatch('user-notification', icon: 'error', message: 'No puedes deshabilitar tu propio usuario.');

            return;
        }

        $user->update(['is_active' => false]);

        $this->dispatch('user-notification', icon: 'success', message: 'El usuario fue deshabilitado correctamente.');
    }

    public function closeModal(): void
    {
        $this->resetForm();
    }

    public function render()
    {
        $users = User::query()
            ->with('roles')
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('name', 'ilike', "%{$this->search}%")
                        ->orWhere('email', 'ilike', "%{$this->search}%")
                        ->orWhere('celular', 'ilike', "%{$this->search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.user-management', [
            'roles' => Role::query()->orderBy('name')->get(),
            'users' => $users,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingUserId),
            ],
            'celular' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'password' => [
                Rule::requiredIf($this->editingUserId === null),
                'nullable',
                'string',
                'min:8',
                'max:255',
                'same:passwordConfirmation',
            ],
            'passwordConfirmation' => ['nullable', 'string', 'max:255'],
            'selectedRole' => ['required', 'string', Rule::exists('roles', 'name')],
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingUserId',
            'name',
            'email',
            'celular',
            'direccion',
            'password',
            'passwordConfirmation',
            'selectedRole',
            'showUserModal',
        ]);
        $this->resetValidation();
    }

    private function ensurePermission(string $permission): void
    {
        abort_unless($this->currentUser()->can($permission), 403);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
