<div>
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-header border-0 d-flex flex-column flex-md-row align-items-md-center gap-3">
            <div>
                <h2 class="h5 mb-1">Empresas registradas</h2>
                <p class="mb-0 small text-body-secondary">Administra la información de las empresas vinculadas.</p>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2 ms-md-auto w-100" style="max-width: 34rem;">
                <div class="input-group">
                    <span class="input-group-text" id="company-search-icon"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input type="search" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre, NIT o contacto" aria-label="Buscar empresas" aria-describedby="company-search-icon">
                </div>
                @can('companies.create')
                    <button type="button" class="btn btn-primary text-nowrap" wire:click="createCompany">
                        <i class="bi bi-building-add me-1" aria-hidden="true"></i> Nueva empresa
                    </button>
                @endcan
            </div>
        </div>
        <div class="card-body overflow-visible p-0">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="small text-body-secondary text-uppercase">
                        <th>Empresa</th>
                        <th class="d-none d-md-table-cell">NIT</th>
                        <th class="d-none d-lg-table-cell">Teléfono</th>
                        <th class="d-none d-sm-table-cell">Contacto</th>
                        <th>Estado</th>
                        @canany(['companies.update', 'companies.disable', 'companies.enable'])
                            <th class="text-end">Acciones</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody>
                    @forelse ($companies as $company)
                        <tr wire:key="company-{{ $company->id }}">
                            <td class="text-break">
                                <span class="d-block fw-semibold">{{ $company->name }}</span>
                                <span class="d-md-none small text-body-secondary">NIT: {{ $company->nit }}</span>
                            </td>
                            <td class="d-none d-md-table-cell">{{ $company->nit }}</td>
                            <td class="d-none d-lg-table-cell">{{ $company->phone ?: 'Sin registrar' }}</td>
                            <td class="d-none d-sm-table-cell">
                                <span class="d-block">{{ $company->contact_person ?: 'Sin registrar' }}</span>
                                @if ($company->contact_phone)
                                    <span class="small text-body-secondary">{{ $company->contact_phone }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge rounded-pill {{ $company->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $company->is_active ? 'Activa' : 'Deshabilitada' }}
                                </span>
                            </td>
                            @canany(['companies.update', 'companies.disable', 'companies.enable'])
                                <td class="text-end text-nowrap">
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Acciones para {{ $company->name }}">
                                            <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @can('companies.update')
                                                <li>
                                                    <button type="button" class="dropdown-item" wire:click="editCompany({{ $company->id }})">
                                                        <i class="bi bi-pencil-square me-2" aria-hidden="true"></i> Editar
                                                    </button>
                                                </li>
                                            @endcan
                                            @can('companies.disable')
                                                @if ($company->is_active)
                                                    <li>
                                                        <button type="button" class="dropdown-item text-danger" wire:click="$dispatch('confirm-company-disabling', { companyId: {{ $company->id }} })">
                                                            <i class="bi bi-building-slash me-2" aria-hidden="true"></i> Deshabilitar
                                                        </button>
                                                    </li>
                                                @endif
                                            @endcan
                                            @can('companies.enable')
                                                @if (! $company->is_active)
                                                    <li>
                                                        <button type="button" class="dropdown-item text-success" wire:click="$dispatch('confirm-company-enabling', { companyId: {{ $company->id }} })">
                                                            <i class="bi bi-building-check me-2" aria-hidden="true"></i> Habilitar
                                                        </button>
                                                    </li>
                                                @endif
                                            @endcan
                                        </ul>
                                    </div>
                                </td>
                            @endcanany
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()?->canAny(['companies.update', 'companies.disable', 'companies.enable']) ? 6 : 5 }}" class="py-5 text-center text-body-secondary">
                                <i class="bi bi-buildings d-block mb-2 fs-4" aria-hidden="true"></i>
                                No se encontraron empresas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($companies->hasPages())
            <div class="card-footer">{{ $companies->links() }}</div>
        @endif
    </div>

    @if ($showCompanyModal)
        <div class="modal show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="company-modal-title">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable modal-fullscreen-sm-down" role="document">
                <form class="modal-content" wire:submit="saveCompany">
                    <div class="modal-header">
                        <div>
                            <h2 id="company-modal-title" class="modal-title h5 mb-1">{{ $editingCompanyId === null ? 'Nueva empresa' : 'Editar empresa' }}</h2>
                            <p class="mb-0 small text-body-secondary">Completa la información básica y del contacto principal.</p>
                        </div>
                        <button type="button" class="btn-close" wire:click="closeModal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="company-name" class="form-label">Nombre</label>
                                <input id="company-name" type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name" autofocus>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="company-nit" class="form-label">NIT</label>
                                <input id="company-nit" type="text" class="form-control @error('nit') is-invalid @enderror" wire:model="nit">
                                @error('nit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="company-phone" class="form-label">Teléfono</label>
                                <input id="company-phone" type="tel" class="form-control @error('phone') is-invalid @enderror" wire:model="phone">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="company-address" class="form-label">Dirección</label>
                                <input id="company-address" type="text" class="form-control @error('address') is-invalid @enderror" wire:model="address">
                                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="company-contact-person" class="form-label">Persona contacto</label>
                                <input id="company-contact-person" type="text" class="form-control @error('contactPerson') is-invalid @enderror" wire:model="contactPerson">
                                @error('contactPerson') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="company-contact-phone" class="form-label">Teléfono contacto</label>
                                <input id="company-contact-phone" type="tel" class="form-control @error('contactPhone') is-invalid @enderror" wire:model="contactPhone">
                                @error('contactPhone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveCompany">
                            <span wire:loading.remove wire:target="saveCompany"><i class="bi bi-floppy me-1" aria-hidden="true"></i> Guardar</span>
                            <span wire:loading wire:target="saveCompany">Guardando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
