<div class="container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">
                <i class="bi bi-boxes me-2 text-primary"></i>
                Constructor de lotes de cobranza
            </h1>
            <p class="mb-0 small text-body-secondary">
                {{ $cartera->company?->name }} · Cartera #{{ $cartera->id }}
                @if($cartera->ult_actualizacion)
                    · Actualizada: {{ \Carbon\Carbon::parse($cartera->ult_actualizacion)->format('d/m/Y H:i') }}
                @endif
                · El lote se arma por deudor, con toda su deuda vigente.
            </p>
        </div>
        <a href="{{ route('carteras') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row g-3">

        <div class="col-xl-9">

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <form wire:submit="filtrar">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Buscar</label>
                            <input type="text" class="form-control form-control-sm"
                                   placeholder="Cliente, código, RUT, teléfono, dirección..."
                                   wire:model="f.search">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Ciudad</label>
                            <select class="form-select form-select-sm" wire:model="f.ciudad">
                                <option value="">Todas</option>
                                @foreach($this->ciudades as $c)
                                    <option value="{{ $c }}">{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Antigüedad máx. ≥ (días)</label>
                            <input type="number" min="0" class="form-control form-control-sm"
                                   wire:model="f.antiguedadMin">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Saldo total ≥</label>
                            <input type="number" step="0.01" class="form-control form-control-sm"
                                   wire:model="f.saldoMin">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Saldo total ≤</label>
                            <input type="number" step="0.01" class="form-control form-control-sm"
                                   wire:model="f.saldoMax">
                        </div>
                    </div>

                    <div class="mt-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex gap-3 flex-wrap align-items-center">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="solo-vencidas"
                                       wire:model="f.soloVencidas">
                                <label class="form-check-label small" for="solo-vencidas">Con deuda vencida</label>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="solo-coords"
                                       wire:model="f.soloConCoordenadas">
                                <label class="form-check-label small" for="solo-coords">Con coordenadas</label>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-funnel me-1"></i> Filtrar
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearFilters">
                                <i class="bi bi-x-circle me-1"></i> Limpiar
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="seleccionarPagina">
                                <i class="bi bi-check2-square me-1"></i> Seleccionar página
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="seleccionarFiltrados">
                                <i class="bi bi-check2-all me-1"></i> Seleccionar filtrados
                            </button>
                        </div>
                        <div class="small text-body-secondary">
                            Mostrando {{ $this->deudores->count() }} de {{ $this->deudores->total() }} deudores
                        </div>
                    </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:36px;"></th>
                                <th role="button" wire:click="sortBy('cliente')">Deudor</th>
                                <th>Contacto</th>
                                <th role="button" wire:click="sortBy('total_deudas')" class="text-center">Deudas</th>
                                <th role="button" wire:click="sortBy('total_saldo')" class="text-end">Saldo</th>
                                <th role="button" wire:click="sortBy('saldo_vencido')" class="text-end">Vencido</th>
                                <th role="button" wire:click="sortBy('antiguedad_max')" class="text-center">Antig. máx.</th>
                                <th class="text-center">Ubicación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->deudores as $d)
                                @php $checked = in_array($d->codigo_cliente, $seleccionados, true); @endphp
                                <tr @if($checked) style="--bs-table-bg: var(--bs-primary-bg-subtle); --bs-table-hover-bg: var(--bs-primary-bg-subtle);" @endif wire:key="deudor-{{ $d->codigo_cliente }}">
                                    <td>
                                        <input type="checkbox" class="form-check-input"
                                               wire:click="toggleSeleccion('{{ $d->codigo_cliente }}')"
                                               @checked($checked)>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 240px;" title="{{ $d->cliente }}">{{ $d->cliente }}</div>
                                        <div class="small text-body-secondary">Cód: {{ $d->codigo_cliente }} · {{ $d->ciudad ?: 'Sin ciudad' }}</div>
                                    </td>
                                    <td class="small">
                                        <div class="text-truncate" style="max-width: 220px;" title="{{ $d->direccion }}">{{ $d->direccion ?: 'Sin dirección' }}</div>
                                        <div class="text-body-secondary">{{ $d->telefono && $d->telefono !== '0' ? $d->telefono : '—' }}</div>
                                    </td>
                                    <td class="text-center">{{ $d->total_deudas }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $d->total_saldo, 2) }}</td>
                                    <td class="text-end {{ (float) $d->saldo_vencido > 0 ? 'text-danger fw-semibold' : 'text-body-secondary' }}">
                                        {{ number_format((float) $d->saldo_vencido, 2) }}
                                    </td>
                                    <td class="text-center">{{ $d->antiguedad_max ?? '—' }}</td>
                                    <td class="text-center text-nowrap">
                                        @if($d->lat !== null)
                                            <a href="https://www.google.com/maps?q={{ $d->lat }},{{ $d->lon }}" target="_blank" rel="noopener noreferrer"
                                               class="badge rounded-pill text-bg-success text-decoration-none" title="Abrir en Google Maps">
                                                <i class="bi bi-geo-alt"></i>
                                            </a>
                                            <button type="button" class="btn btn-link btn-sm p-0 ms-1"
                                                    wire:click="sugerirLote('{{ $d->codigo_cliente }}')"
                                                    title="Sugerir lote con los deudores más cercanos a este">
                                                <i class="bi bi-bullseye"></i>
                                            </button>
                                        @else
                                            <span class="badge rounded-pill text-bg-secondary" title="Sin coordenadas válidas">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-body-secondary">
                                        No hay deudores disponibles con esos filtros.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent">
                    {{ $this->deudores->links() }}
                </div>
            </div>
        </div>

        <div class="col-xl-3">
            <div class="card border-0 shadow-sm position-sticky" style="top: 1rem;">
                <div class="card-header bg-body-tertiary fw-semibold">
                    <i class="bi bi-bullseye me-1"></i> Sugerir por cercanía
                </div>
                <div class="card-body">
                    <label class="form-label small mb-1" for="tamano-sugerido">Deudores por lote</label>
                    <div class="input-group input-group-sm mb-2">
                        <input id="tamano-sugerido" type="number" min="1" max="200" class="form-control" wire:model="tamanoSugerido">
                    </div>
                    <label class="form-label small mb-1" for="radio-max">Radio máximo (km)</label>
                    <div class="input-group input-group-sm mb-2">
                        <input id="radio-max" type="number" min="0.5" step="0.5" class="form-control" wire:model="radioMaxKm">
                        <button class="btn btn-outline-primary" wire:click="sugerirLote" wire:loading.attr="disabled" wire:target="sugerirLote">
                            Sugerir
                        </button>
                    </div>
                    <p class="small text-body-secondary mb-0">
                        Busca la zona más densa del filtro actual (más deudores dentro del radio) y toma los más cercanos. También puedes usar
                        <i class="bi bi-bullseye"></i> en una fila para elegir el punto de partida.
                    </p>
                </div>
                <div class="card-header bg-body-tertiary border-top d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-cart3 me-1"></i> Selección</span>
                    @if($this->totalesSeleccion['deudores'] > 0)
                        <button class="btn btn-sm btn-link text-danger p-0" wire:click="limpiarSeleccion">Limpiar</button>
                    @endif
                </div>
                <div class="card-body">
                    @if($this->totalesSeleccion['deudores'] === 0)
                        <p class="text-body-secondary small mb-0">Aún no hay deudores seleccionados.</p>
                    @else
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-body-secondary small">Deudores</span>
                            <span class="fw-semibold">{{ number_format($this->totalesSeleccion['deudores']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-body-secondary small">Deudas</span>
                            <span class="fw-semibold">{{ number_format($this->totalesSeleccion['deudas']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-body-secondary small">Saldo</span>
                            <span class="fw-semibold">{{ number_format($this->totalesSeleccion['saldo'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-body-secondary small">Vencido</span>
                            <span class="fw-semibold text-danger">{{ number_format($this->totalesSeleccion['vencido'], 2) }}</span>
                        </div>
                        @if($this->totalesSeleccion['ciudades'])
                            <div class="small text-body-secondary mb-1">
                                Ciudades: {{ implode(', ', $this->totalesSeleccion['ciudades']) }}
                            </div>
                        @endif
                        @if($this->totalesSeleccion['sin_coordenadas'] > 0)
                            <div class="small text-warning">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                {{ $this->totalesSeleccion['sin_coordenadas'] }} sin coordenadas (irán al final de la ruta)
                            </div>
                        @endif

                        <button class="btn btn-primary w-100 mt-3" wire:click="abrirModalCrear">
                            <i class="bi bi-plus-circle me-1"></i> Crear lote
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($showModalCrear)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-box-seam me-1"></i> Nuevo lote de cobranza</h5>
                        <button type="button" class="btn-close" wire:click="cerrarModalCrear"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Nombre del lote *</label>
                                <input type="text" class="form-control form-control-sm @error('nombreLote') is-invalid @enderror" wire:model="nombreLote">
                                @error('nombreLote') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Cobrador asignado *</label>
                                <select class="form-select form-select-sm @error('cobradorId') is-invalid @enderror" wire:model="cobradorId">
                                    <option value="">-- Seleccionar --</option>
                                    @foreach($this->cobradores as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->email }})</option>
                                    @endforeach
                                </select>
                                @error('cobradorId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Fecha asignación *</label>
                                <input type="date" class="form-control form-control-sm @error('fechaAsignacion') is-invalid @enderror" wire:model="fechaAsignacion">
                                @error('fechaAsignacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Fecha límite</label>
                                <input type="date" class="form-control form-control-sm @error('fechaLimite') is-invalid @enderror" wire:model="fechaLimite">
                                @error('fechaLimite') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">Descripción / instrucciones</label>
                                <textarea class="form-control form-control-sm" rows="3" wire:model="descripcionLote"
                                          placeholder="Ej: Zona norte, priorizar mora > 180 días..."></textarea>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="alert alert-primary mb-0">
                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                <span><strong>{{ number_format($this->totalesSeleccion['deudores']) }}</strong> deudores</span>
                                <span><strong>{{ number_format($this->totalesSeleccion['deudas']) }}</strong> deudas</span>
                                <span>Saldo: <strong>{{ number_format($this->totalesSeleccion['saldo'], 2) }}</strong></span>
                            </div>
                            <div class="small mt-1">La ruta de visitas se ordena por cercanía, empezando por el mayor saldo vencido.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" wire:click="cerrarModalCrear">Cancelar</button>
                        <button class="btn btn-primary" wire:click="crearLote" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="crearLote"><i class="bi bi-check2-circle me-1"></i> Crear y asignar</span>
                            <span wire:loading wire:target="crearLote"><span class="spinner-border spinner-border-sm me-1"></span> Procesando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

    @script
    <script>
        const mostrarToast = ({ type = 'info', message = '' }) => {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const colors = {
                success: 'text-bg-success',
                danger:  'text-bg-danger',
                warning: 'text-bg-warning',
                info:    'text-bg-primary',
            };

            const el = document.createElement('div');
            el.className = `toast align-items-center ${colors[type] || 'text-bg-secondary'} border-0`;
            el.setAttribute('role', 'alert');
            el.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>`;
            container.appendChild(el);

            const t = new bootstrap.Toast(el, { delay: 3500 });
            t.show();
            el.addEventListener('hidden.bs.toast', () => el.remove());
        };

        Livewire.on('notify', (payload) => {
            mostrarToast(Array.isArray(payload) ? payload[0] : payload);
        });
    </script>
    @endscript
</div>
