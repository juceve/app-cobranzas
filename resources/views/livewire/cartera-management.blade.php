<div>
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-header border-0 d-flex flex-column flex-md-row align-items-md-center gap-3">
            <div>
                <h2 class="h5 mb-1">Carteras registradas</h2>
                <p class="mb-0 small text-body-secondary">Administra las carteras vinculadas a cada empresa.</p>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2 ms-md-auto w-100" style="max-width: 34rem;">
                <div class="input-group">
                    <span class="input-group-text" id="cartera-search-icon"><i class="bi bi-search"
                            aria-hidden="true"></i></span>
                    <input type="search" class="form-control" wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por empresa, NIT o fecha" aria-label="Buscar carteras"
                        aria-describedby="cartera-search-icon">
                </div>
                @can('carteras.create')
                <button type="button" class="btn btn-primary text-nowrap" wire:click="createCartera">
                    <i class="bi bi-folder-plus me-1" aria-hidden="true"></i> Nueva cartera
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
                        <th class="d-none d-lg-table-cell">Última actualización</th>
                        <th class="d-none d-sm-table-cell text-center">Deudas</th>
                        @can('carteras.update')
                        <th class="text-end">Acciones</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($carteras as $cartera)
                    <tr wire:key="cartera-{{ $cartera->id }}">
                        <td class="text-break">
                            <span class="d-block fw-semibold">{{ $cartera->company?->name ?? 'Sin empresa' }}</span>
                            <span class="d-md-none small text-body-secondary">NIT: {{ $cartera->company?->nit ?? '—'
                                }}</span>
                        </td>
                        <td class="d-none d-md-table-cell">{{ $cartera->company?->nit ?? '—' }}</td>
                        <td class="d-none d-lg-table-cell">
                            @if ($cartera->ult_actualizacion)
                            {{ \Illuminate\Support\Carbon::parse($cartera->ult_actualizacion)->format('d/m/Y H:i') }}
                            @else
                            <span class="text-body-secondary">Sin registrar</span>
                            @endif
                        </td>
                        <td class="d-none d-sm-table-cell text-center">
                            <span class="badge rounded-pill text-bg-info">
                                {{ $cartera->deudacarteras_count }}
                            </span>
                        </td>
                        {{-- @can('carteras.update')
                        <td class="text-end text-nowrap">
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                wire:click="editCartera({{ $cartera->id }})"
                                aria-label="Editar cartera de {{ $cartera->company?->name ?? 'esta empresa' }}">
                                <i class="bi bi-pencil-square" aria-hidden="true"></i>
                            </button>
                        </td>
                        @endcan --}}

                        @canany(['carteras.update', 'carteras.view'])
                        <td class="text-end text-nowrap">
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle-no-caret"
                                    data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-display="static"
                                    aria-expanded="false"
                                    aria-label="Acciones para {{ $cartera->company?->name ?? 'esta cartera' }}">
                                    <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li>
                                        <a href="{{ route('carteras', $cartera->id) }}" class="dropdown-item"
                                            wire:navigate>
                                            <i class="bi bi-people me-2" aria-hidden="true"></i> Deudas
                                        </a>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item"
                                            wire:click="openHistorialModal({{ $cartera->id }})" title="Ver historial">
                                            <i class="bi bi-clock-history"></i> Historial
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item"
                                            wire:click="verDeudas({{ $cartera->id }})" title="Ver deudas">
                                            <i class="bi bi-list-ul"></i> Ver deudas
                                        </button>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item"
                                            wire:click="openUploadModal({{ $cartera->id }})">
                                            <i class="bi bi-arrow-repeat me-2" aria-hidden="true"></i> Actualizar
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </td>
                        @endcanany
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()?->can('carteras.update') ? 5 : 4 }}"
                            class="py-5 text-center text-body-secondary">
                            <i class="bi bi-folder2-open d-block mb-2 fs-4" aria-hidden="true"></i>
                            No se encontraron carteras.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($carteras->hasPages())
        <div class="card-footer">{{ $carteras->links() }}</div>
        @endif
    </div>

    @if ($showCarteraModal)
    <div class="modal show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="cartera-modal-title">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable modal-fullscreen-sm-down"
            role="document">
            <form class="modal-content" wire:submit="saveCartera">
                <div class="modal-header">
                    <div>
                        <h2 id="cartera-modal-title" class="modal-title h5 mb-1">{{ $editingCarteraId === null ? 'Nueva
                            cartera' : 'Editar cartera' }}</h2>
                        <p class="mb-0 small text-body-secondary">Vincula la cartera a una empresa y registra la fecha
                            de actualización.</p>
                    </div>
                    <button type="button" class="btn-close" wire:click="closeModal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="cartera-company" class="form-label">Empresa</label>
                            <select id="cartera-company" class="form-select @error('companyId') is-invalid @enderror"
                                wire:model="companyId">
                                <option value="">Seleccione una empresa…</option>
                                @foreach ($companies as $company)
                                <option value="{{ $company->id }}">
                                    {{ $company->name }} — NIT: {{ $company->nit }}
                                </option>
                                @endforeach
                            </select>
                            @error('companyId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="cartera-ult-actualizacion" class="form-label">Última actualización</label>
                            <input id="cartera-ult-actualizacion" type="date"
                                class="form-control @error('ultActualizacion') is-invalid @enderror"
                                wire:model="ultActualizacion">
                            @error('ultActualizacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                        wire:target="saveCartera">
                        <span wire:loading.remove wire:target="saveCartera"><i class="bi bi-floppy me-1"
                                aria-hidden="true"></i> Guardar</span>
                        <span wire:loading wire:target="saveCartera">Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    @if ($showUploadModal)
    <div class="modal show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="upload-modal-title">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down"
            role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 id="upload-modal-title" class="modal-title h5 mb-1">
                            <i class="bi bi-cloud-arrow-up me-2 text-primary" aria-hidden="true"></i>
                            Actualizar cartera
                        </h2>
                        <p class="mb-0 small text-body-secondary">
                            Sube el archivo Excel con la cartera actualizada. El archivo original será archivado.
                        </p>
                    </div>
                    <button type="button" class="btn-close" wire:click="closeUploadModal" aria-label="Cerrar"
                        @disabled($uploading)></button>
                </div>

                <div class="modal-body">
                    {{-- Zona de drop --}}
                    <div class="border border-2 border-dashed rounded-3 p-4 text-center mb-3 position-relative
                               {{ $uploading ? 'opacity-50 pe-none' : 'cursor-pointer' }}" x-data="{ dragging: false }"
                        x-on:dragover.prevent="dragging = true" x-on:dragleave.prevent="dragging = false"
                        x-on:drop.prevent="
                            dragging = false;
                            const f = $event.dataTransfer.files[0];
                            if (f) {
                                const dt = new DataTransfer();
                                dt.items.add(f);
                                $refs.fileInput.files = dt.files;
                                $refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        " x-bind:class="dragging ? 'border-primary bg-primary-subtle' : 'border-secondary-subtle'"
                        x-on:click="$refs.fileInput.click()">
                        <input type="file" x-ref="fileInput" wire:model="excelFile" accept=".xlsx,.xls,.csv"
                            class="d-none" @disabled($uploading)>

                        <i class="bi bi-file-earmark-excel fs-1 text-success d-block mb-2" aria-hidden="true"></i>

                        @if ($excelFile)
                        <div class="fw-semibold text-truncate">{{ $uploadOriginalName }}</div>
                        <div class="small text-body-secondary">
                            {{ number_format($excelFile->getSize() / 1024, 1) }} KB · listo para procesar
                        </div>
                        @else
                        <div class="fw-semibold">Haz clic o arrastra tu archivo aquí</div>
                        <div class="small text-body-secondary">Formatos: XLSX, XLS, CSV · máx. 20 MB</div>
                        @endif
                    </div>

                    @error('excelFile')
                    <div class="alert alert-danger py-2 small mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> {{ $message }}
                    </div>
                    @enderror

                    {{-- Barra de progreso elegante --}}

                    <div class="progress-wrapper" x-data="{ uploading: $wire.uploading }" x-show="uploading"
                        x-transition.opacity>
                        <div class="d-flex justify-content-between align-items-center mb-1 small">
                            <span class="text-body-secondary">
                                <i class="bi bi-hourglass-split me-1"></i> Procesando archivo…
                            </span>
                        </div>
                        <div class="progress" style="height: 0.75rem; border-radius: .5rem;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                style="width: 100%;"></div>
                        </div>
                        <div class="text-center mt-3">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"
                                aria-hidden="true"></div>
                            <span class="small text-body-secondary">No cierres esta ventana…</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center">
                    <button type="button" class="btn btn-secondary" wire:click="closeUploadModal" @disabled($uploading)>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="processUpload"
                        wire:loading.attr="disabled" wire:target="processUpload" @disabled($uploading || ! $excelFile)>
                        <span wire:loading.remove wire:target="processUpload">
                            <i class="bi bi-cloud-arrow-up me-1" aria-hidden="true"></i> Procesar archivo
                        </span>
                        <span wire:loading wire:target="processUpload">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Procesando…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    @if ($showHistorialModal && $historialCartera)
    <div class="modal show d-block" tabindex="-1" role="dialog" aria-modal="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5 mb-1">
                            <i class="bi bi-clock-history me-2 text-info"></i>
                            Historial de actualizaciones
                        </h2>
                        <p class="mb-0 small text-body-secondary">
                            {{ $historialCartera->company?->name }}
                            @if($historialCartera->ult_actualizacion)
                            · Última actualización: {{
                            \Carbon\Carbon::parse($historialCartera->ult_actualizacion)->format('d/m/Y H:i') }}
                            @endif
                        </p>
                    </div>
                    <button type="button" class="btn-close" wire:click="closeHistorialModal"
                        aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Estado</label>
                            <select class="form-select form-select-sm" wire:model.live="historialEstado">
                                <option value="">Todos</option>
                                <option value="procesado">Procesado</option>
                                <option value="error">Error</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th class="text-center">Cambios</th>
                                    <th class="text-center">Nuevos</th>
                                    <th class="text-center">Salidas</th>
                                    <th>Archivo</th>
                                    <th>Usuario</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($historial as $item)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($item->fecha)->format('d/m/Y H:i') }}</td>
                                    <td class="text-center">{{ $item->cambios }}</td>
                                    <td class="text-center text-success fw-semibold">{{ $item->nuevos }}</td>
                                    <td class="text-center text-danger fw-semibold">{{ $item->salidas }}</td>
                                    <td class="small text-truncate" style="max-width: 220px;"
                                        title="{{ $item->doc_original }}">
                                        {{ $item->doc_original }}
                                    </td>
                                    <td class="small">{{ $item->user?->name ?? '—' }}</td>
                                    <td>
                                        <span
                                            class="badge {{ $item->estado === 'procesado' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                            {{ ucfirst($item->estado) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-body-secondary py-4">
                                        No hay registros de historial.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-2">
                        {{ $historial->links() }}
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeHistorialModal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div>
@push('css')
<style>
    .border-dashed {
        border-style: dashed !important;
    }

    .progress-wrapper {
        transition: opacity .25s ease-in-out;
    }

    .progress {
        background-color: rgba(0, 0, 0, .08);
    }

    [data-bs-theme="dark"] .progress,
    .dark-mode .progress {
        background-color: rgba(255, 255, 255, .12);
    }

    .cursor-pointer {
        cursor: pointer;
    }
</style>
@endpush