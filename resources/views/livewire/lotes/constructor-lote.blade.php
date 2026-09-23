<div class="container-fluid py-4">

    {{-- Encabezado --}}
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
            </p>
        </div>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row g-3">

        {{-- Columna izquierda --}}
        <div class="col-lg-9">

            {{-- Filtros --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Buscar</label>
                            <input type="text" class="form-control form-control-sm"
                                   placeholder="NumDoc, cliente, código, RUT, teléfono..."
                                   wire:model.live.debounce.400ms="search">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Estado</label>
                            <select class="form-select form-select-sm" wire:model.live="estado">
                                <option value="">Todos</option>
                                @foreach($this->estadosDisponibles as $e)
                                    <option value="{{ $e }}">{{ $e }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Rango</label>
                            <select class="form-select form-select-sm" wire:model.live="rango">
                                <option value="">Todos</option>
                                @foreach($this->rangosDisponibles as $r)
                                    <option value="{{ $r }}">{{ $r }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Tramo antigüedad</label>
                            <select class="form-select form-select-sm" wire:model.live="tramo">
                                <option value="">Todos</option>
                                <option value="01_30">01 - 30 días</option>
                                <option value="31_60">31 - 60 días</option>
                                <option value="61_90">61 - 90 días</option>
                                <option value="91_120">91 - 120 días</option>
                                <option value="121_150">121 - 150 días</option>
                                <option value="151_180">151 - 180 días</option>
                                <option value="181_210">181 - 210 días</option>
                                <option value="211_250">211 - 250 días</option>
                                <option value="mas_251">Más de 251 días</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small mb-1">Saldo ≥</label>
                            <input type="number" step="0.01" class="form-control form-control-sm"
                                   wire:model.live.debounce.500ms="saldoMin">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small mb-1">Saldo ≤</label>
                            <input type="number" step="0.01" class="form-control form-control-sm"
                                   wire:model.live.debounce.500ms="saldoMax">
                        </div>
                    </div>

                    <div class="mt-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-sm btn-outline-secondary" wire:click="clearFilters">
                                <i class="bi bi-x-circle me-1"></i> Limpiar filtros
                            </button>
                            <button class="btn btn-sm btn-outline-primary" wire:click="seleccionarPagina">
                                <i class="bi bi-check2-square me-1"></i> Seleccionar página
                            </button>
                            <button class="btn btn-sm btn-outline-primary" wire:click="seleccionarFiltradas">
                                <i class="bi bi-check2-all me-1"></i> Seleccionar todas las filtradas
                            </button>
                        </div>
                        <div class="small text-body-secondary">
                            Mostrando {{ $this->deudas->count() }} de {{ $this->deudas->total() }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabla --}}
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:36px;"></th>
                                <th role="button" wire:click="sortBy('num_doc')">NumDoc</th>
                                <th role="button" wire:click="sortBy('cliente')">Cliente</th>
                                <th role="button" wire:click="sortBy('importe')" class="text-end">Importe</th>
                                <th role="button" wire:click="sortBy('saldo')" class="text-end">Saldo</th>
                                <th class="text-end">% Cobrado</th>
                                <th role="button" wire:click="sortBy('vence')">Vence</th>
                                <th role="button" wire:click="sortBy('antiguedad')" class="text-center">Antig.</th>
                                <th>Tramo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->deudas as $d)
                                @php
                                    $tramoKey   = \App\Services\DeudaTramoService::tramo((int)$d->antiguedad);
                                    $tramoLabel = \App\Services\DeudaTramoService::etiqueta($tramoKey);
                                    $tramoColor = \App\Services\DeudaTramoService::color($tramoKey);
                                    $porcentaje = ((float)$d->importe) > 0
                                        ? round(((float)$d->saldo / (float)$d->importe) * 100, 2)
                                        : 0;
                                    $checked = in_array($d->id, $seleccionados, true);
                                @endphp
                                <tr class="{{ $checked ? 'table-primary' : '' }}"
                                    wire:key="deuda-{{ $d->id }}">
                                    <td>
                                        <input type="checkbox"
                                               class="form-check-input"
                                               wire:click="toggleSeleccion({{ $d->id }})"
                                               @checked($checked)>
                                    </td>
                                    <td class="fw-semibold">{{ $d->num_doc }}</td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="{{ $d->cliente }}">
                                            {{ $d->cliente }}
                                        </div>
                                        <div class="small text-body-secondary">{{ $d->codigo_cliente }}</div>
                                    </td>
                                    <td class="text-end">{{ number_format((float)$d->importe, 2) }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float)$d->saldo, 2) }}</td>
                                    <td class="text-end">
                                        @php
                                            $pctClass = $porcentaje > 50 ? 'danger' : ($porcentaje > 20 ? 'warning' : 'success');
                                        @endphp
                                        <span class="badge bg-{{ $pctClass }}-subtle text-{{ $pctClass }}">
                                            {{ $porcentaje }}%
                                        </span>
                                    </td>
                                    <td>{{ $d->vence ? \Carbon\Carbon::parse($d->vence)->format('d/m/Y') : '—' }}</td>
                                    <td class="text-center">{{ $d->antiguedad ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $tramoColor }}-subtle text-{{ $tramoColor }}">
                                            {{ $tramoLabel }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $d->estado === 'VENCIDAS' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}">
                                            {{ $d->estado }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-body-secondary">
                                        No se encontraron deudas con esos filtros.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent">
                    {{ $this->deudas->links() }}
                </div>
            </div>
        </div>

        {{-- Columna derecha: carrito --}}
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm position-sticky" style="top: 1rem;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">
                        <i class="bi bi-cart3 me-1"></i> Selección
                    </span>
                    @if($this->totalesSeleccion['count'] > 0)
                        <button class="btn btn-sm btn-link text-danger p-0" wire:click="limpiarSeleccion">
                            Limpiar
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    @if($this->totalesSeleccion['count'] === 0)
                        <p class="text-body-secondary small mb-0">
                            Aún no has seleccionado deudas. Usa los checkboxes de la tabla para armar el lote.
                        </p>
                    @else
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-body-secondary small">Deudas</span>
                            <span class="fw-semibold">{{ number_format($this->totalesSeleccion['count']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-body-secondary small">Saldo</span>
                            <span class="fw-semibold">{{ number_format($this->totalesSeleccion['saldo'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-body-secondary small">Importe</span>
                            <span class="fw-semibold">{{ number_format($this->totalesSeleccion['importe'], 2) }}</span>
                        </div>

                        <hr>

                        <div class="small text-body-secondary mb-2">Distribución por tramo</div>
                        @foreach($this->totalesSeleccion['tramos'] as $tKey => $cant)
                            @php
                                $tLabel = \App\Services\DeudaTramoService::etiqueta($tKey);
                                $tColor = \App\Services\DeudaTramoService::color($tKey);
                            @endphp
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-{{ $tColor }}-subtle text-{{ $tColor }}">{{ $tLabel }}</span>
                                <span class="small fw-semibold">{{ $cant }}</span>
                            </div>
                        @endforeach

                        <button class="btn btn-primary w-100 mt-3" wire:click="abrirModalCrear">
                            <i class="bi bi-plus-circle me-1"></i> Crear lote
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de creación --}}
    @if($showModalCrear)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-box-seam me-1"></i> Nuevo lote de cobranza
                        </h5>
                        <button type="button" class="btn-close" wire:click="cerrarModalCrear"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Nombre del lote *</label>
                                <input type="text" class="form-control form-control-sm @error('nombreLote') is-invalid @enderror"
                                       wire:model="nombreLote">
                                @error('nombreLote') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Cobrador asignado *</label>
                                <select class="form-select form-select-sm @error('cobradorId') is-invalid @enderror"
                                        wire:model="cobradorId">
                                    <option value="">-- Seleccionar --</option>
                                    @foreach($this->cobradores as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->email }})</option>
                                    @endforeach
                                </select>
                                @error('cobradorId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Fecha asignación *</label>
                                <input type="date" class="form-control form-control-sm @error('fechaAsignacion') is-invalid @enderror"
                                       wire:model="fechaAsignacion">
                                @error('fechaAsignacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Fecha límite</label>
                                <input type="date" class="form-control form-control-sm @error('fechaLimite') is-invalid @enderror"
                                       wire:model="fechaLimite">
                                @error('fechaLimite') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">Descripción / instrucciones</label>
                                <textarea class="form-control form-control-sm" rows="3"
                                          wire:model="descripcionLote"
                                          placeholder="Ej: Zona norte, priorizar mora > 180 días..."></textarea>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="alert alert-primary mb-0">
                            <div class="d-flex justify-content-between">
                                <span><strong>{{ number_format($this->totalesSeleccion['count']) }}</strong> deudas</span>
                                <span>Saldo: <strong>{{ number_format($this->totalesSeleccion['saldo'], 2) }}</strong></span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" wire:click="cerrarModalCrear">
                            Cancelar
                        </button>
                        <button class="btn btn-primary" wire:click="crearLote" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="crearLote">
                                <i class="bi bi-check2-circle me-1"></i> Crear y asignar
                            </span>
                            <span wire:loading wire:target="crearLote">
                                <span class="spinner-border spinner-border-sm me-1"></span> Procesando...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Contenedor de toasts --}}
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