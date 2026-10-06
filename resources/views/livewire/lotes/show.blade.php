<div class="container-fluid py-4">

    @php
        $colores = ['borrador' => 'secondary', 'asignado' => 'info', 'en_proceso' => 'primary', 'cerrado' => 'success', 'cancelado' => 'danger'];
        $color = $colores[$lote->estado] ?? 'secondary';
        $abierto = $lote->estaAbierto();
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">
                <i class="bi bi-box-seam me-2 text-primary"></i>
                {{ $lote->codigo }} · {{ $lote->nombre }}
                <span class="badge bg-{{ $color }}-subtle text-{{ $color }} text-capitalize ms-2">{{ str_replace('_', ' ', $lote->estado) }}</span>
            </h1>
            <p class="mb-0 small text-body-secondary">
                {{ $lote->cartera?->company?->name }} · Cobrador: {{ $lote->cobrador?->name ?? '—' }}
                · Creado por {{ $lote->creador?->name ?? '—' }}
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('lotes.index') }}" class="btn btn-outline-secondary" wire:navigate>
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            @if($abierto)
                @can('lotes.update')
                    <button type="button" class="btn btn-outline-primary" wire:click="abrirEditar">
                        <i class="bi bi-pencil-square me-1"></i> Modificar
                    </button>
                @endcan
                @can('lotes.close')
                    <button type="button" class="btn btn-success" wire:click="abrirCerrar">
                        <i class="bi bi-check2-circle me-1"></i> Cerrar lote
                    </button>
                @endcan
                @can('lotes.cancel')
                    <button type="button" class="btn btn-outline-danger" wire:click="abrirCancelar">
                        <i class="bi bi-x-octagon me-1"></i> Cancelar lote
                    </button>
                @endcan
            @endif
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="small text-body-secondary text-uppercase">Deudores</div>
                <div class="h4 mb-0">{{ number_format($lote->total_deudores) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="small text-body-secondary text-uppercase">Deudas</div>
                <div class="h4 mb-0">{{ number_format($lote->total_deudas) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="small text-body-secondary text-uppercase">Saldo</div>
                <div class="h4 mb-0">{{ number_format((float) $lote->total_saldo, 2) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="small text-body-secondary text-uppercase">Plazo</div>
                <div class="fw-semibold">
                    {{ $lote->fecha_asignacion?->format('d/m/Y') }} → {{ $lote->fecha_limite?->format('d/m/Y') ?? 'sin límite' }}
                </div>
                @if($abierto && $lote->fecha_limite && $lote->fecha_limite->isPast())
                    <span class="badge text-bg-warning">Vencido</span>
                @endif
            </div></div>
        </div>
    </div>

    @if($lote->descripcion)
        <div class="alert alert-secondary py-2 small"><strong>Instrucciones:</strong> {{ $lote->descripcion }}</div>
    @endif

    @if(! $abierto)
        <div class="alert alert-{{ $color }} py-2 small">
            Lote {{ $lote->estado }} el {{ $lote->cerrado_en?->format('d/m/Y H:i') }}
            por {{ $lote->cerradoPor?->name ?? '—' }}.
            @if($lote->motivo_cierre) Motivo: {{ $lote->motivo_cierre }} @endif
            Sus deudas están disponibles para nuevos lotes.
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-body-tertiary fw-semibold">
            <i class="bi bi-signpost-split me-1"></i> Ruta de visitas
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width:60px;">#</th>
                        <th>Deudor</th>
                        <th>Contacto</th>
                        <th class="text-center">Deudas</th>
                        <th class="text-end">Saldo</th>
                        <th class="text-center">Ubicación</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->deudores as $d)
                        <tr wire:key="deudor-{{ $d['codigo'] }}">
                            <td class="text-center fw-semibold">{{ $d['orden'] ?? '—' }}</td>
                            <td>
                                <div class="fw-semibold">{{ $d['cliente'] }}</div>
                                <div class="small text-body-secondary">Cód: {{ $d['codigo'] }} · {{ $d['ciudad'] ?: 'Sin ciudad' }}</div>
                            </td>
                            <td class="small">
                                <div>{{ $d['direccion'] ?: 'Sin dirección' }}</div>
                                <div class="text-body-secondary">{{ $d['telefono'] && $d['telefono'] !== '0' ? $d['telefono'] : '—' }}</div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-link btn-sm p-0" wire:click="verDeudor('{{ $d['codigo'] }}')">
                                    {{ $d['deudas']->count() }} <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                </button>
                            </td>
                            <td class="text-end fw-semibold">{{ number_format($d['saldo'], 2) }}</td>
                            <td class="text-center">
                                @if($d['lat'] && $d['lon'])
                                    <a href="https://www.google.com/maps?q={{ $d['lat'] }},{{ $d['lon'] }}" target="_blank" rel="noopener noreferrer"
                                       class="badge rounded-pill text-bg-success text-decoration-none" title="Abrir en Google Maps">
                                        <i class="bi bi-geo-alt"></i>
                                    </a>
                                @else
                                    <span class="badge rounded-pill text-bg-secondary">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($abierto)
                                    @can('lotes.update')
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                wire:click="quitarDeudor('{{ $d['codigo'] }}')"
                                                wire:confirm="¿Quitar a este deudor y todas sus deudas del lote?"
                                                title="Quitar del lote">
                                            <i class="bi bi-person-dash"></i>
                                        </button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-body-secondary">El lote no tiene deudas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($codigoDeudor && ($sel = $this->deudores->firstWhere('codigo', $codigoDeudor)))
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $sel['cliente'] }}</h5>
                        <button type="button" class="btn-close" wire:click="cerrarModales"></button>
                    </div>
                    <div class="modal-body">
                        <dl class="row small mb-4">
                            <dt class="col-sm-3">Código cliente</dt>
                            <dd class="col-sm-9">{{ $sel['codigo'] }}</dd>
                            <dt class="col-sm-3">RUT</dt>
                            <dd class="col-sm-9">{{ $sel['rut'] ?: '—' }}</dd>
                            <dt class="col-sm-3">Ciudad</dt>
                            <dd class="col-sm-9">{{ $sel['ciudad'] ?: '—' }}</dd>
                            <dt class="col-sm-3">Dirección</dt>
                            <dd class="col-sm-9">{{ $sel['direccion'] ?: 'Sin dirección' }}</dd>
                            <dt class="col-sm-3">Teléfono</dt>
                            <dd class="col-sm-9">{{ $sel['telefono'] && $sel['telefono'] !== '0' ? $sel['telefono'] : '—' }}</dd>
                            <dt class="col-sm-3">Jefe / Supervisor / Vendedor</dt>
                            <dd class="col-sm-9">{{ $sel['jefe'] ?: '—' }} / {{ $sel['supervisor'] ?: '—' }} / {{ $sel['vendedor'] ?: '—' }}</dd>
                            @if($sel['lat'] && $sel['lon'])
                                <dt class="col-sm-3">Ubicación</dt>
                                <dd class="col-sm-9">
                                    <a href="https://www.google.com/maps?q={{ $sel['lat'] }},{{ $sel['lon'] }}" target="_blank" rel="noopener noreferrer">Ver en Google Maps</a>
                                </dd>
                            @endif
                        </dl>

                        <h6>Deudas ({{ $sel['deudas']->count() }})</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead>
                                    <tr class="small text-body-secondary text-uppercase">
                                        <th>N° Doc</th>
                                        <th>Vence</th>
                                        <th class="text-center">Antig.</th>
                                        <th class="text-end">Importe</th>
                                        <th class="text-end">Saldo</th>
                                        <th>Rango</th>
                                        <th>Gestión</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sel['deudas'] as $deuda)
                                        <tr wire:key="deuda-{{ $deuda->id }}">
                                            <td>{{ $deuda->num_doc }}</td>
                                            <td>{{ $deuda->vence?->format('d/m/Y') ?? '—' }}</td>
                                            <td class="text-center">{{ $deuda->antiguedad ?? '—' }}</td>
                                            <td class="text-end">{{ number_format((float) $deuda->importe, 2) }}</td>
                                            <td class="text-end">{{ number_format((float) $deuda->saldo, 2) }}</td>
                                            <td>{{ $deuda->rango ?? '—' }}</td>
                                            <td class="text-capitalize">{{ str_replace('_', ' ', $deuda->pivot->estado_gestion) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-semibold">
                                        <td colspan="4" class="text-end">Total saldo</td>
                                        <td class="text-end">{{ number_format($sel['saldo'], 2) }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" wire:click="cerrarModales">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($showCancelar || $showCerrar)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $showCancelar ? 'Cancelar lote' : 'Cerrar lote' }}</h5>
                        <button type="button" class="btn-close" wire:click="cerrarModales"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">
                            Se liberarán las deudas del lote ({{ number_format($lote->total_deudas) }}) para poder incluirlas en un lote nuevo.
                        </p>
                        <label class="form-label small mb-1">Motivo {{ $showCancelar ? '*' : '(opcional)' }}</label>
                        <textarea class="form-control @error('motivo') is-invalid @enderror" rows="3" wire:model="motivo"></textarea>
                        @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" wire:click="cerrarModales">Volver</button>
                        @if($showCancelar)
                            <button class="btn btn-danger" wire:click="cancelar" wire:loading.attr="disabled">Cancelar lote</button>
                        @else
                            <button class="btn btn-success" wire:click="cerrar" wire:loading.attr="disabled">Cerrar lote</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($showEditar)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modificar lote</h5>
                        <button type="button" class="btn-close" wire:click="cerrarModales"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Nombre *</label>
                                <input type="text" class="form-control form-control-sm @error('nombre') is-invalid @enderror" wire:model="nombre">
                                @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Fecha límite</label>
                                <input type="date" class="form-control form-control-sm @error('fechaLimite') is-invalid @enderror" wire:model="fechaLimite">
                                @error('fechaLimite') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @can('lotes.assign')
                                <div class="col-12">
                                    <label class="form-label small mb-1">Cobrador asignado *</label>
                                    <select class="form-select form-select-sm @error('cobradorId') is-invalid @enderror" wire:model="cobradorId">
                                        @foreach($this->cobradores as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->email }})</option>
                                        @endforeach
                                    </select>
                                    @error('cobradorId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endcan
                            <div class="col-12">
                                <label class="form-label small mb-1">Descripción / instrucciones</label>
                                <textarea class="form-control form-control-sm" rows="3" wire:model="descripcion"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" wire:click="cerrarModales">Cancelar</button>
                        <button class="btn btn-primary" wire:click="guardarEdicion" wire:loading.attr="disabled">Guardar</button>
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

            const colors = { success: 'text-bg-success', danger: 'text-bg-danger', warning: 'text-bg-warning', info: 'text-bg-primary' };

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
