<div>
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-header border-0 d-flex flex-column flex-md-row align-items-md-center gap-3">
            <div>
                <h2 class="h5 mb-1">Usuarios registrados</h2>
                <p class="mb-0 small text-body-secondary">Administra los accesos y roles del sistema.</p>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-2 ms-md-auto w-100" style="max-width: 34rem;">
                <div class="input-group">
                    <span class="input-group-text" id="user-search-icon"><i class="bi bi-search"
                            aria-hidden="true"></i></span>
                    <input type="search" class="form-control" wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por nombre, correo o celular" aria-label="Buscar usuarios"
                        aria-describedby="user-search-icon">
                </div>
                @can('users.create')
                    <button type="button" class="btn btn-primary text-nowrap" wire:click="createUser">
                        <i class="bi bi-person-plus me-1" aria-hidden="true"></i> Nuevo usuario
                    </button>
                @endcan
            </div>
        </div>

        <div class="card-body overflow-visible p-0">
            <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="small text-body-secondary text-uppercase">
                            <th>Nombre</th>
                            <th class="d-none d-md-table-cell">Correo</th>
                            <th class="d-none d-lg-table-cell">Celular</th>
                            <th>Estado</th>
                            <th>Roles</th>
                            @canany(['users.update', 'users.disable'])
                                <th class="text-end">Acciones</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td class="text-break">
                                <span class="d-block fw-semibold">{{ $user->name }}</span>
                                <span class="d-md-none small text-body-secondary">{{ $user->email }}</span>
                            </td>
                            <td class="d-none d-md-table-cell text-break">{{ $user->email }}</td>
                            <td class="d-none d-lg-table-cell">{{ $user->celular ?: 'Sin registrar' }}</td>
                            <td class="d-none d-sm-table-cell">
                                <span class="badge rounded-pill {{ $user->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $user->is_active ? 'Activo' : 'Deshabilitado' }}
                                </span>
                            </td>
                            <td>
                                @forelse ($user->roles as $role)
                                <span class="badge rounded-pill text-bg-info">{{ $role->name }}</span>
                                @empty
                                <span class="text-body-secondary">Sin roles</span>
                                @endforelse
                            </td>
                            @canany(['users.update', 'users.disable'])
                                <td class="text-end text-nowrap">
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                            data-bs-toggle="dropdown" aria-expanded="false"
                                            aria-label="Acciones para {{ $user->name }}">
                                            <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @can('users.update')
                                                <li>
                                                    <button type="button" class="dropdown-item" wire:click="editUser({{ $user->id }})">
                                                        <i class="bi bi-pencil-square me-2" aria-hidden="true"></i> Editar
                                                    </button>
                                                </li>
                                            @endcan
                                            @can('users.disable')
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger" wire:click="$dispatch('confirm-user-disabling', { userId: {{ $user->id }} })" @disabled(! $user->is_active)>
                                                        <i class="bi bi-person-slash me-2" aria-hidden="true"></i> Deshabilitar
                                                    </button>
                                                </li>
                                            @endcan
                                        </ul>
                                    </div>
                                </td>
                            @endcanany
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()?->canAny(['users.update', 'users.disable']) ? 6 : 5 }}" class="py-5 text-center text-body-secondary">
                                <i class="bi bi-people d-block mb-2 fs-4" aria-hidden="true"></i>
                                No se encontraron usuarios.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
        </div>

        @if ($users->hasPages())
        <div class="card-footer">
            {{ $users->links() }}
        </div>
        @endif
    </div>

    @if ($showUserModal)
    <div class="modal show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="user-modal-title">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable modal-fullscreen-sm-down" role="document">
            <form class="modal-content" wire:submit="saveUser">
                <div class="modal-header">
                    <div>
                        <h2 id="user-modal-title" class="modal-title h5 mb-1">{{ $editingUserId === null ? 'Nuevo
                            usuario' : 'Editar usuario' }}</h2>
                        <p class="mb-0 small text-body-secondary">Completa los datos y define los roles de acceso.</p>
                    </div>
                    <button type="button" class="btn-close" wire:click="closeModal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div>
                                <label for="user-name">Nombre</label>
                                <input id="user-name" type="text"
                                    class="form-control @error('name') is-invalid @enderror" wire:model="name"
                                    autofocus>
                                @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <label for="user-email">Correo</label>
                                <input id="user-email" type="email"
                                    class="form-control @error('email') is-invalid @enderror" wire:model="email">
                                @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <label for="user-celular">Celular</label>
                                <input id="user-celular" type="text"
                                    class="form-control @error('celular') is-invalid @enderror" wire:model="celular">
                                @error('celular') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <label for="user-direccion">Dirección</label>
                                <input id="user-direccion" type="text"
                                    class="form-control @error('direccion') is-invalid @enderror"
                                    wire:model="direccion">
                                @error('direccion') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <label for="user-password">Contraseña {{ $editingUserId !== null ? '(opcional)' : ''
                                    }}</label>
                                <input id="user-password" type="password"
                                    class="form-control @error('password') is-invalid @enderror" wire:model="password">
                                @error('password') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <label for="user-password-confirmation">Confirmar contraseña</label>
                                <input id="user-password-confirmation" type="password"
                                    class="form-control @error('passwordConfirmation') is-invalid @enderror"
                                    wire:model="passwordConfirmation">
                                @error('passwordConfirmation') <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="user-role" class="form-label">Rol</label>
                            <select id="user-role" class="form-select @error('selectedRole') is-invalid @enderror" wire:model="selectedRole">
                                <option value="">Selecciona un rol</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            @error('selectedRole') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveUser">
                        <span wire:loading.remove wire:target="saveUser"><i class="bi bi-floppy me-1"
                                aria-hidden="true"></i> Guardar</span>
                        <span wire:loading wire:target="saveUser">Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div>