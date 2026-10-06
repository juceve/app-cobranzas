<div>
    @php
        $total = max($this->deudores->count(), 1);
        $pct = (int) round($gestionados / $total * 100);
        $estadoUi = [
            'pendiente' => ['Pendiente', 'secondary'],
            'visitado' => ['Contactado', 'info'],
            'promesa_pago' => ['Promesa de pago', 'primary'],
            'no_contactado' => ['No contactado', 'warning'],
            'rechazado' => ['Se negó', 'danger'],
            'pagado' => ['Pagado', 'success'],
        ];
    @endphp

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between small mb-1">
                <span>{{ $gestionados }} de {{ $this->deudores->count() }} gestionados</span>
                <span class="fw-semibold">{{ $pct }}%</span>
            </div>
            <div class="progress" style="height: 10px;" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-success" style="width: {{ $pct }}%"></div>
            </div>
        </div>
    </div>

    <div class="btn-group w-100 mb-2" role="group" aria-label="Filtrar deudores">
        <button type="button" class="btn {{ $vista === 'pendientes' ? 'btn-primary' : 'btn-outline-primary' }} py-2" wire:click="$set('vista', 'pendientes')">
            Pendientes <span class="badge text-bg-light ms-1">{{ $pendientes }}</span>
        </button>
        <button type="button" class="btn {{ $vista === 'gestionados' ? 'btn-primary' : 'btn-outline-primary' }} py-2" wire:click="$set('vista', 'gestionados')">
            Gestionados <span class="badge text-bg-light ms-1">{{ $gestionados }}</span>
        </button>
        <button type="button" class="btn {{ $vista === 'todos' ? 'btn-primary' : 'btn-outline-primary' }} py-2" wire:click="$set('vista', 'todos')">
            Todos
        </button>
    </div>

    <div class="input-group mb-3">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="search" class="form-control form-control-lg" placeholder="Buscar deudor o dirección"
               wire:model.live.debounce.300ms="buscar" aria-label="Buscar">
    </div>

    @forelse($this->lista as $d)
        @php [$etiqueta, $color] = $estadoUi[$d['estado']] ?? $estadoUi['pendiente']; @endphp
        <div class="card border-0 shadow-sm mb-3" wire:key="deudor-{{ $d['codigo'] }}">
            <div class="card-body pb-2">
                <div class="d-flex align-items-start gap-2">
                    <span class="badge rounded-pill text-bg-dark fs-6">{{ $d['orden'] ?? '–' }}</span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold lh-sm">{{ $d['cliente'] }}</div>
                        <div class="small text-body-secondary">{{ $d['direccion'] ?: 'Sin dirección' }}@if($d['ciudad']) · {{ $d['ciudad'] }}@endif</div>
                    </div>
                    <span class="badge text-bg-{{ $color }} text-nowrap">{{ $etiqueta }}</span>
                </div>

                <div class="d-flex justify-content-between align-items-end mt-3">
                    <div class="small text-body-secondary">{{ $d['deudas']->count() }} {{ $d['deudas']->count() === 1 ? 'deuda' : 'deudas' }}</div>
                    <div class="text-end">
                        <div class="fs-5 fw-bold">{{ number_format($d['pendiente'], 2) }}</div>
                        @if($d['cobrado'] > 0)
                            <div class="small text-success">Cobrado {{ number_format($d['cobrado'], 2) }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-footer bg-transparent border-0 pt-0">
                <div class="row g-2 text-center">
                    <div class="col-3">
                        @if($d['tel'])
                            <a href="tel:+{{ $d['tel'] }}" class="btn btn-outline-success w-100 py-2 lh-1" aria-label="Llamar">
                                <i class="bi bi-telephone fs-5 d-block"></i><span class="small">Llamar</span>
                            </a>
                        @else
                            <span class="btn btn-outline-secondary w-100 py-2 lh-1 disabled"><i class="bi bi-telephone fs-5 d-block"></i><span class="small">Llamar</span></span>
                        @endif
                    </div>
                    <div class="col-3">
                        @if($d['tel'])
                            <a href="https://wa.me/{{ $d['tel'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success w-100 py-2 lh-1" aria-label="WhatsApp">
                                <i class="bi bi-whatsapp fs-5 d-block"></i><span class="small">WhatsApp</span>
                            </a>
                        @else
                            <span class="btn btn-outline-secondary w-100 py-2 lh-1 disabled"><i class="bi bi-whatsapp fs-5 d-block"></i><span class="small">WhatsApp</span></span>
                        @endif
                    </div>
                    <div class="col-3">
                        @if($d['mapa'])
                            <a href="{{ $d['mapa'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary w-100 py-2 lh-1" aria-label="Cómo llegar">
                                <i class="bi bi-geo-alt fs-5 d-block"></i><span class="small">Ir</span>
                            </a>
                        @else
                            <span class="btn btn-outline-secondary w-100 py-2 lh-1 disabled"><i class="bi bi-geo-alt fs-5 d-block"></i><span class="small">Ir</span></span>
                        @endif
                    </div>
                    <div class="col-3">
                        <button type="button" class="btn btn-primary w-100 py-2 lh-1" wire:click="abrirDeudor('{{ $d['codigo'] }}')" aria-label="Gestionar">
                            <i class="bi bi-clipboard-check fs-5 d-block"></i><span class="small">Gestionar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center text-body-secondary py-5">
            <i class="bi bi-check2-circle fs-1 d-block mb-2"></i>
            @if($vista === 'pendientes' && $buscar === '')
                ¡No te quedan deudores pendientes!
            @else
                No hay deudores con ese filtro.
            @endif
        </div>
    @endforelse

    @if($actual = $this->deudorActual)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-fullscreen-sm-down modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="text-truncate">
                            <h5 class="modal-title text-truncate">{{ $actual['cliente'] }}</h5>
                            <div class="small text-body-secondary">{{ $actual['deudas']->count() }} deudas · Pendiente {{ number_format($actual['pendiente'], 2) }}</div>
                        </div>
                        <button type="button" class="btn-close" wire:click="cerrarDeudor" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body">
                        <ul class="nav nav-pills nav-fill mb-3" role="tablist">
                            <li class="nav-item"><button type="button" class="nav-link py-2 {{ $pestana === 'gestion' ? 'active' : '' }}" wire:click="$set('pestana', 'gestion')">Gestión</button></li>
                            <li class="nav-item"><button type="button" class="nav-link py-2 {{ $pestana === 'cobro' ? 'active' : '' }}" wire:click="$set('pestana', 'cobro')">Cobro</button></li>
                            <li class="nav-item"><button type="button" class="nav-link py-2 {{ $pestana === 'historial' ? 'active' : '' }}" wire:click="$set('pestana', 'historial')">Historial</button></li>
                        </ul>

                        @if($pestana === 'gestion')
                            @can('cobrador.gestionar')
                                <div class="mb-3">
                                    <div class="fw-semibold mb-2">¿Cómo contactaste?</div>
                                    <div class="row g-2">
                                        @foreach(\App\Models\Gestion::TIPOS as $clave => $texto)
                                            <div class="col-6 col-sm-3">
                                                <input type="radio" class="btn-check" id="tipo-{{ $clave }}" value="{{ $clave }}" wire:model="tipo" autocomplete="off">
                                                <label class="btn btn-outline-primary w-100 py-2" for="tipo-{{ $clave }}">{{ $texto }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="fw-semibold mb-2">¿Cómo te fue?</div>
                                    <div class="d-grid gap-2">
                                        @foreach(collect(\App\Models\Gestion::RESULTADOS)->except('pago') as $clave => [$texto])
                                            <input type="radio" class="btn-check" id="res-{{ $clave }}" value="{{ $clave }}" wire:model.live="resultado" autocomplete="off">
                                            <label class="btn btn-outline-secondary text-start py-3" for="res-{{ $clave }}">{{ $texto }}</label>
                                        @endforeach
                                    </div>
                                    @error('resultado') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                @if($resultado === 'promesa_pago')
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small mb-1" for="fecha-promesa">Fecha prometida</label>
                                            <input id="fecha-promesa" type="date" class="form-control form-control-lg @error('fechaPromesa') is-invalid @enderror" wire:model="fechaPromesa" min="{{ today()->toDateString() }}">
                                            @error('fechaPromesa') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small mb-1" for="monto-promesa">Monto prometido</label>
                                            <input id="monto-promesa" type="number" inputmode="decimal" step="0.01" class="form-control form-control-lg @error('montoPrometido') is-invalid @enderror" wire:model="montoPrometido">
                                            @error('montoPrometido') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                @endif

                                <div class="mb-2">
                                    <label class="form-label small mb-1" for="observacion">Observación (opcional)</label>
                                    <textarea id="observacion" class="form-control" rows="3" wire:model="observacion" placeholder="Ej: pidió que llame el lunes"></textarea>
                                </div>
                            @else
                                <div class="alert alert-warning">No tienes permiso para registrar gestiones.</div>
                            @endcan
                        @elseif($pestana === 'cobro')
                            @can('cobrador.cobrar')
                                @php $totalCobro = 0; @endphp
                                @if($confirmandoCobro)
                                    @php $totalConfirmado = array_sum($resumenCobro); @endphp
                                    <div class="text-center mb-3">
                                        <i class="bi bi-question-circle fs-1 text-warning"></i>
                                        <div class="h5 mt-2 mb-1">¿Confirmas el cobro?</div>
                                        <div class="small text-body-secondary">Se registrará el pago y se emitirá un recibo. Esta acción no se puede deshacer.</div>
                                    </div>

                                    <div class="border rounded p-3 mb-3">
                                        <div class="d-flex justify-content-between mb-1"><span class="text-body-secondary">Cliente</span><span class="fw-semibold text-end">{{ $actual['cliente'] }}</span></div>
                                        <div class="d-flex justify-content-between mb-1"><span class="text-body-secondary">Forma de pago</span><span class="fw-semibold">{{ \App\Models\Cobro::FORMAS_PAGO[$formaPago] ?? $formaPago }}</span></div>
                                        @if($referencia !== '')
                                            <div class="d-flex justify-content-between mb-1"><span class="text-body-secondary">Referencia</span><span class="fw-semibold">{{ $referencia }}</span></div>
                                        @endif
                                        <hr class="my-2">
                                        @foreach($actual['deudas']->whereIn('id', array_keys($resumenCobro)) as $deuda)
                                            <div class="d-flex justify-content-between" wire:key="conf-{{ $deuda->id }}">
                                                <span>Doc. {{ $deuda->num_doc }}</span>
                                                <span>{{ number_format($resumenCobro[$deuda->id], 2) }}</span>
                                            </div>
                                        @endforeach
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between fs-4 fw-bold">
                                            <span>Total</span><span>{{ \App\Models\Recibo::MONEDA }} {{ number_format($totalConfirmado, 2) }}</span>
                                        </div>
                                    </div>
                                @else
                                <div class="fw-semibold mb-2">¿Qué deudas pagó?</div>
                                @error('cobrar') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                                @foreach($actual['deudas'] as $deuda)
                                    @php
                                        $pendiente = max((float) $deuda->saldo - (float) $deuda->pivot->monto_recuperado, 0);
                                        $marcada = ! empty($cobrar[$deuda->id]);
                                        if ($marcada) { $totalCobro += (float) str_replace(',', '.', (string) ($montos[$deuda->id] ?? 0)); }
                                    @endphp
                                    <div class="border rounded p-2 mb-2 {{ $pendiente <= 0 ? 'opacity-50' : '' }}" wire:key="cobro-{{ $deuda->id }}">
                                        <div class="form-check">
                                            <input class="form-check-input" style="width:1.4rem;height:1.4rem;" type="checkbox" id="cobrar-{{ $deuda->id }}"
                                                   wire:model.live="cobrar.{{ $deuda->id }}" @disabled($pendiente <= 0)>
                                            <label class="form-check-label ms-2 w-100" for="cobrar-{{ $deuda->id }}">
                                                <span class="d-flex justify-content-between">
                                                    <span class="fw-semibold">Doc. {{ $deuda->num_doc }}</span>
                                                    <span>{{ number_format($pendiente, 2) }}</span>
                                                </span>
                                                <span class="small text-body-secondary">Vence {{ $deuda->vence?->format('d/m/Y') ?? '—' }}@if($deuda->pivot->monto_recuperado > 0) · ya cobrado {{ number_format((float) $deuda->pivot->monto_recuperado, 2) }}@endif</span>
                                            </label>
                                        </div>
                                        @if($marcada)
                                            <div class="mt-2">
                                                <label class="form-label small mb-1" for="monto-{{ $deuda->id }}">Monto recibido</label>
                                                <input id="monto-{{ $deuda->id }}" type="number" inputmode="decimal" step="0.01"
                                                       class="form-control form-control-lg @error('montos.'.$deuda->id) is-invalid @enderror"
                                                       wire:model.live.debounce.400ms="montos.{{ $deuda->id }}">
                                                @error('montos.'.$deuda->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                                <div class="fw-semibold mt-3 mb-2">Forma de pago</div>
                                <div class="row g-2 mb-3">
                                    @foreach(\App\Models\Cobro::FORMAS_PAGO as $clave => $texto)
                                        <div class="col-4">
                                            <input type="radio" class="btn-check" id="pago-{{ $clave }}" value="{{ $clave }}" wire:model.live="formaPago" autocomplete="off">
                                            <label class="btn btn-outline-primary w-100 py-2" for="pago-{{ $clave }}">{{ $texto }}</label>
                                        </div>
                                    @endforeach
                                </div>

                                @if($formaPago !== 'efectivo')
                                    <div class="mb-3">
                                        <label class="form-label small mb-1" for="referencia">N° de referencia / comprobante</label>
                                        <input id="referencia" type="text" class="form-control form-control-lg @error('referencia') is-invalid @enderror" wire:model="referencia">
                                        @error('referencia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                @endif

                                <div class="mb-2">
                                    <label class="form-label small mb-1" for="nota-cobro">Nota (opcional)</label>
                                    <textarea id="nota-cobro" class="form-control" rows="2" wire:model="notaCobro"></textarea>
                                </div>
                                @endif
                            @else
                                <div class="alert alert-warning">No tienes permiso para registrar cobros.</div>
                            @endcan
                        @else
                            @forelse($this->historial as $g)
                                <div class="border-start border-3 border-primary ps-3 mb-3" wire:key="hist-{{ $g->id }}">
                                    <div class="fw-semibold">{{ $g->etiquetaResultado() }} · {{ \App\Models\Gestion::TIPOS[$g->tipo] ?? $g->tipo }}</div>
                                    <div class="small text-body-secondary">{{ $g->gestionado_en->format('d/m/Y H:i') }} · {{ $g->usuario?->name }}</div>
                                    @if($g->observacion)<div class="small mt-1">{{ $g->observacion }}</div>@endif
                                    @if($g->fecha_promesa)<div class="small">Promete {{ number_format((float) $g->monto_prometido, 2) }} para el {{ $g->fecha_promesa->format('d/m/Y') }}</div>@endif
                                    @if($g->cobros->isNotEmpty())
                                        <div class="small text-success">Cobrado {{ number_format((float) $g->cobros->sum('monto'), 2) }}</div>
                                        @if($g->cobros->first()->recibo_id)
                                            <button type="button" class="btn btn-outline-success btn-sm mt-1" wire:click="verRecibo({{ $g->cobros->first()->recibo_id }})">
                                                <i class="bi bi-receipt"></i> Ver recibo
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            @empty
                                <div class="text-center text-body-secondary py-4">Aún no hay gestiones con este deudor.</div>
                            @endforelse
                        @endif
                    </div>

                    @if($pestana === 'gestion')
                        @can('cobrador.gestionar')
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary btn-lg w-100" wire:click="guardarGestion" wire:loading.attr="disabled" wire:target="guardarGestion">
                                    <span wire:loading.remove wire:target="guardarGestion"><i class="bi bi-check2-circle me-1"></i> Guardar gestión</span>
                                    <span wire:loading wire:target="guardarGestion"><span class="spinner-border spinner-border-sm me-1"></span> Guardando…</span>
                                </button>
                            </div>
                        @endcan
                    @elseif($pestana === 'cobro')
                        @can('cobrador.cobrar')
                            <div class="modal-footer">
                                @if($confirmandoCobro)
                                    <button type="button" class="btn btn-success btn-lg w-100" wire:click="registrarCobro" wire:loading.attr="disabled" wire:target="registrarCobro">
                                        <span wire:loading.remove wire:target="registrarCobro"><i class="bi bi-check2-circle me-1"></i> Sí, registrar el cobro</span>
                                        <span wire:loading wire:target="registrarCobro"><span class="spinner-border spinner-border-sm me-1"></span> Registrando…</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary w-100" wire:click="volverACobro" wire:loading.attr="disabled">Volver a revisar</button>
                                @else
                                    <button type="button" class="btn btn-success btn-lg w-100" wire:click="revisarCobro" wire:loading.attr="disabled" wire:target="revisarCobro">
                                        <i class="bi bi-cash-coin me-1"></i> Revisar y registrar{{ $totalCobro > 0 ? ' · '.number_format($totalCobro, 2) : '' }}
                                    </button>
                                @endif
                            </div>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($recibo = $this->reciboActual)
        @php
            $url = $recibo->urlPublica();
            $textoRecibo = "Recibo {$recibo->numero} - {$recibo->empresa}\nRecibimos ".\App\Models\Recibo::MONEDA.' '.number_format((float) $recibo->monto_total, 2)." de {$recibo->cliente}.\nDetalle: {$url}";
            $telefonoCliente = $this->deudores->firstWhere('codigo', $recibo->codigo_cliente)['tel'] ?? null;
        @endphp
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-fullscreen-sm-down modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 3.5rem;"></i>
                        <div class="h4 mt-2 mb-0">Cobro registrado</div>
                        <div class="text-body-secondary">{{ $recibo->numero }}</div>
                        <div class="display-6 fw-bold my-3">{{ \App\Models\Recibo::MONEDA }} {{ number_format((float) $recibo->monto_total, 2) }}</div>
                        <div class="small text-body-secondary mb-3">{{ $recibo->cliente }} · {{ $recibo->etiquetaFormaPago() }}</div>

                        <div class="fw-semibold mb-2">Entregar recibo</div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <a href="https://wa.me/{{ $telefonoCliente }}?text={{ rawurlencode($textoRecibo) }}" target="_blank" rel="noopener noreferrer" class="btn btn-success w-100 py-3 lh-1">
                                    <i class="bi bi-whatsapp fs-4 d-block mb-1"></i>WhatsApp
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="mailto:?subject={{ rawurlencode('Recibo '.$recibo->numero) }}&body={{ rawurlencode($textoRecibo) }}" class="btn btn-primary w-100 py-3 lh-1">
                                    <i class="bi bi-envelope fs-4 d-block mb-1"></i>Correo
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ $recibo->urlTicket(true) }}" target="_blank" rel="noopener noreferrer" class="btn btn-dark w-100 py-3 lh-1">
                                    <i class="bi bi-printer fs-4 d-block mb-1"></i>Imprimir 58 mm
                                </a>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-secondary w-100 py-3 lh-1"
                                        x-data
                                        x-on:click="navigator.share ? navigator.share({ title: 'Recibo {{ $recibo->numero }}', text: @js($textoRecibo), url: @js($url) }) : navigator.clipboard.writeText(@js($url)).then(() => window.mostrarToast({ type: 'success', message: 'Enlace copiado' }))">
                                    <i class="bi bi-share fs-4 d-block mb-1"></i>Compartir
                                </button>
                            </div>
                        </div>

                        <div class="d-inline-block bg-white p-2 rounded border" style="width: 11rem;">{!! \App\Services\ReciboService::qrSvg($url, 160) !!}</div>
                        <div class="small text-body-secondary mt-1">El cliente puede escanearlo para ver su recibo.</div>
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="small d-block mt-2">Ver recibo como lo ve el cliente</a>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary btn-lg w-100" wire:click="cerrarRecibo">Listo</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
