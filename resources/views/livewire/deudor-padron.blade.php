<div>
    {{-- Resumen --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span
                        class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center"
                        style="width: 3rem; height: 3rem;">
                        <i class="bi bi-people fs-4" aria-hidden="true"></i>
                    </span>
                    <div>
                        <div class="small text-body-secondary text-uppercase">Deudores registrados</div>
                        <div class="h4 mb-0">{{ number_format($totalGeneral) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span
                        class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center"
                        style="width: 3rem; height: 3rem;">
                        <i class="bi bi-geo-alt fs-4" aria-hidden="true"></i>
                    </span>
                    <div>
                        <div class="small text-body-secondary text-uppercase">Con coordenadas</div>
                        <div class="h4 mb-0">{{ number_format($totalConCoordenadas) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span
                        class="rounded-circle bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center"
                        style="width: 3rem; height: 3rem;">
                        <i class="bi bi-geo fs-4" aria-hidden="true"></i>
                    </span>
                    <div>
                        <div class="small text-body-secondary text-uppercase">Sin coordenadas</div>
                        <div class="h4 mb-0">{{ number_format($totalGeneral - $totalConCoordenadas) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla + filtros --}}
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-header border-0 d-flex flex-column flex-md-row align-items-md-center gap-3">
            <div>
                <h2 class="h5 mb-1">Padrón de deudores</h2>
                <p class="mb-0 small text-body-secondary">Catálogo único de personas con sus datos de contacto y
                    ubicación.</p>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2 ms-md-auto w-100" style="max-width: 42rem;">
                <div class="input-group">
                    <span class="input-group-text" id="deudor-search-icon"><i class="bi bi-search"
                            aria-hidden="true"></i></span>
                    <input type="search" class="form-control" wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por nombre, RUT, código, teléfono o dirección" aria-label="Buscar deudores"
                        aria-describedby="deudor-search-icon">
                </div>
                @can('deudores.export')
                <button type="button" class="btn btn-outline-success text-nowrap" wire:click="exportCsv"
                    wire:loading.attr="disabled" wire:target="exportCsv">
                    <span wire:loading.remove wire:target="exportCsv">
                        <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> Exportar
                    </span>
                    <span wire:loading wire:target="exportCsv">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        Generando…
                    </span>
                </button>
                @endcan
            </div>
        </div>

        {{-- Filtros secundarios --}}
        <div class="card-body border-bottom py-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="deudor-ciudad" class="form-label small mb-1">Ciudad</label>
                    <select id="deudor-ciudad" class="form-select form-select-sm" wire:model.live="ciudad">
                        <option value="">Todas las ciudades</option>
                        @foreach ($ciudades as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="deudor-coords"
                            wire:model.live="soloConCoordenadas">
                        <label class="form-check-label small" for="deudor-coords">
                            Solo con coordenadas
                        </label>
                    </div>
                </div>
                <div class="col-12 col-md-4 col-lg-3 ms-lg-auto text-md-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearFilters"
                        @disabled($search==='' && $ciudad==='' && ! $soloConCoordenadas)>
                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i> Limpiar filtros
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body overflow-visible p-0">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="small text-body-secondary text-uppercase">
                        <th>Deudor</th>
                        <th class="d-none d-md-table-cell">RUT</th>
                        <th class="d-none d-lg-table-cell">Ciudad</th>
                        <th class="d-none d-lg-table-cell">Dirección</th>
                        <th class="d-none d-xl-table-cell">Teléfono</th>
                        <th class="d-none d-xl-table-cell text-center">Coords</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deudores as $deudor)
                    <tr wire:key="deudor-{{ $loop->index }}-{{ md5($deudor->rut_id . $deudor->direccion) }}">
                        <td class="text-break">
                            <span class="d-block fw-semibold">{{ $deudor->cliente }}</span>
                            <span class="d-md-none small text-body-secondary">RUT: {{ $deudor->rut_id ?: '—' }}</span>
                            <span class="d-lg-none small text-body-secondary d-block">
                                {{ $deudor->ciu_nombre ?: 'Sin ciudad' }}
                            </span>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <span class="d-block">{{ $deudor->rut_id ?: '—' }}</span>
                            <span class="small text-body-secondary">Cód: {{ $deudor->codigo_cliente ?: '—' }}</span>
                        </td>
                        <td class="d-none d-lg-table-cell">
                            <span class="d-block">{{ $deudor->ciu_nombre ?: '—' }}</span>
                            @if ($deudor->cli_lugar)
                            <span class="small text-body-secondary">{{ $deudor->cli_lugar }}</span>
                            @endif
                        </td>
                        <td class="d-none d-lg-table-cell text-break">
                            {{ $deudor->direccion ?: 'Sin dirección' }}
                        </td>
                        <td class="d-none d-xl-table-cell">
                            {{ $deudor->telefono ?: '—' }}
                        </td>
                        <td class="d-none d-xl-table-cell text-center">
                            @if ($deudor->coordenada_x && $deudor->coordenada_y)
                            <a href="https://www.google.com/maps?q={{ $deudor->coordenada_y }},{{ $deudor->coordenada_x }}"
                                target="_blank" rel="noopener noreferrer"
                                class="badge rounded-pill text-bg-success text-decoration-none"
                                title="Abrir en Google Maps">
                                <i class="bi bi-geo-alt me-1" aria-hidden="true"></i> Ver
                            </a>
                            @else
                            <span class="badge rounded-pill text-bg-secondary">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-5 text-center text-body-secondary">
                            <i class="bi bi-person-x d-block mb-2 fs-4" aria-hidden="true"></i>
                            No se encontraron deudores con los filtros aplicados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($deudores->hasPages())
        <div class="card-footer">{{ $deudores->links() }}</div>
        @endif
    </div>
</div>