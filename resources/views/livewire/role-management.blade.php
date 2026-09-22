<div class="row g-4">
    <div class="col-xl-7">
        <div class="card card-outline card-primary shadow-sm h-100">
            <div class="card-header border-0 d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                <div>
                    <h2 class="h5 mb-1">Roles del sistema</h2>
                    <p class="mb-0 small text-body-secondary">Crea roles y define los permisos que pueden utilizar.</p>
                </div>
                <button type="button" class="btn btn-primary ms-sm-auto" wire:click="createRole">
                    <i class="bi bi-shield-plus me-1" aria-hidden="true"></i> Nuevo rol
                </button>
            </div>
            <div class="card-body overflow-visible p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="small text-body-secondary text-uppercase">
                            <th>Rol</th>
                            <th class="d-none d-sm-table-cell">Permisos asignados</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr wire:key="role-{{ $role->id }}">
                                <td class="fw-semibold">{{ $role->name }}</td>
                                <td class="d-none d-sm-table-cell">
                                    @forelse ($role->permissions as $permission)
                                        <span class="badge rounded-pill text-bg-info me-1">{{ $permission->display_name }}</span>
                                    @empty
                                        <span class="text-body-secondary">Sin permisos</span>
                                    @endforelse
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="editRole({{ $role->id }})" aria-label="Editar {{ $role->name }}">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-5 text-center text-body-secondary">
                                    <i class="bi bi-shield d-block fs-4 mb-2" aria-hidden="true"></i>
                                    No hay roles registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card card-outline card-secondary shadow-sm h-100">
            <div class="card-header border-0 d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                <div>
                    <h2 class="h5 mb-1">Catálogo de permisos</h2>
                    <p class="mb-0 small text-body-secondary">Organiza los permisos por módulo.</p>
                </div>
                <button type="button" class="btn btn-outline-primary ms-sm-auto" wire:click="createPermission">
                    <i class="bi bi-key me-1" aria-hidden="true"></i> Nuevo permiso
                </button>
            </div>
            <div class="card-body pt-0">
                @forelse ($permissionsByGroup as $group => $permissions)
                    <section class="border-top py-3" wire:key="permission-group-{{ $group }}">
                        <h3 class="h6 text-uppercase text-body-secondary mb-2">{{ $group }}</h3>
                        <div class="list-group list-group-flush">
                            @foreach ($permissions as $permission)
                                <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3 px-0 text-start" wire:click="editPermission({{ $permission->id }})" wire:key="permission-{{ $permission->id }}">
                                    <span class="text-break">{{ $permission->display_name }}</span>
                                    <i class="bi bi-pencil text-body-secondary" aria-hidden="true"></i>
                                </button>
                            @endforeach
                        </div>
                    </section>
                @empty
                    <div class="py-5 text-center text-body-secondary">
                        <i class="bi bi-key d-block fs-4 mb-2" aria-hidden="true"></i>
                        No hay permisos registrados.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @if ($showRoleModal)
        <div class="modal show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="role-modal-title">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable modal-fullscreen-sm-down" role="document">
                <form class="modal-content" wire:submit="saveRole">
                    <div class="modal-header">
                        <div>
                            <h2 id="role-modal-title" class="modal-title h5 mb-1">{{ $editingRoleId === null ? 'Nuevo rol' : 'Editar rol' }}</h2>
                            <p class="mb-0 small text-body-secondary">Selecciona los permisos que tendrá este rol.</p>
                        </div>
                        <button type="button" class="btn-close" wire:click="closeRoleModal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <label for="role-name" class="form-label">Nombre del rol</label>
                            <input id="role-name" type="text" class="form-control @error('roleName') is-invalid @enderror" wire:model="roleName" autofocus>
                            @error('roleName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <span class="form-label d-block mb-2">Permisos</span>
                            <div class="row g-3">
                                @forelse ($permissionsByGroup as $group => $permissions)
                                    <div class="col-md-6" wire:key="role-modal-group-{{ $group }}">
                                        <div class="border rounded h-100 p-3">
                                            <h3 class="h6 text-uppercase text-body-secondary mb-3">{{ $group }}</h3>
                                            @foreach ($permissions as $permission)
                                                <div class="form-check mb-2" wire:key="role-permission-{{ $permission->id }}">
                                                    <input id="role-permission-{{ $permission->id }}" class="form-check-input" type="checkbox" value="{{ $permission->id }}" wire:model="selectedPermissionIds">
                                                    <label class="form-check-label" for="role-permission-{{ $permission->id }}">{{ $permission->display_name }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-body-secondary">Primero crea un permiso para poder asignarlo.</div>
                                @endforelse
                            </div>
                            @error('selectedPermissionIds.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center">
                        <button type="button" class="btn btn-secondary" wire:click="closeRoleModal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveRole">
                            <span wire:loading.remove wire:target="saveRole"><i class="bi bi-floppy me-1" aria-hidden="true"></i> Guardar</span>
                            <span wire:loading wire:target="saveRole">Guardando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    @if ($showPermissionModal)
        <div class="modal show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="permission-modal-title">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down" role="document">
                <form class="modal-content" wire:submit="savePermission">
                    <div class="modal-header">
                        <div>
                            <h2 id="permission-modal-title" class="modal-title h5 mb-1">{{ $editingPermissionId === null ? 'Nuevo permiso' : 'Editar permiso' }}</h2>
                            <p class="mb-0 small text-body-secondary">Define un permiso reutilizable por los roles.</p>
                        </div>
                        <button type="button" class="btn-close" wire:click="closePermissionModal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="permission-name" class="form-label">Identificador</label>
                            <input id="permission-name" type="text" class="form-control @error('permissionName') is-invalid @enderror" wire:model="permissionName" placeholder="ejemplo.ver">
                            @error('permissionName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="permission-display-name" class="form-label">Nombre visible</label>
                            <input id="permission-display-name" type="text" class="form-control @error('permissionDisplayName') is-invalid @enderror" wire:model="permissionDisplayName" placeholder="Ver ejemplo">
                            @error('permissionDisplayName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label for="permission-group" class="form-label">Módulo</label>
                            <input id="permission-group" type="text" class="form-control @error('permissionGroup') is-invalid @enderror" wire:model="permissionGroup" placeholder="Ejemplos">
                            @error('permissionGroup') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center">
                        <button type="button" class="btn btn-secondary" wire:click="closePermissionModal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="savePermission">
                            <span wire:loading.remove wire:target="savePermission"><i class="bi bi-floppy me-1" aria-hidden="true"></i> Guardar</span>
                            <span wire:loading wire:target="savePermission">Guardando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
