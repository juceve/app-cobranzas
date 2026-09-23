<div>
    {{-- ─── Filtros globales ─── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label small mb-1">Cartera</label>
                    <select class="form-select form-select-sm" wire:model.live="carteraId">
                        <option value="">Todas las carteras</option>
                        @foreach ($carteras as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->company?->name }} — #{{ $c->id }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Rango</label>
                    <select class="form-select form-select-sm" wire:model.live="rango">
                        <option value="">Todos</option>
                        <option value="VENCIDAS">Vencidas</option>
                        <option value="VIGENTES">Vigentes</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Ciudad</label>
                    <select class="form-select form-select-sm" wire:model.live="ciudad">
                        <option value="">Todas</option>
                        @foreach ($ciudades as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Jefe de ventas</label>
                    <select class="form-select form-select-sm" wire:model.live="jefeVendedor">
                        <option value="">Todos</option>
                        @foreach ($jefes as $j)
                            <option value="{{ $j }}">{{ $j }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Vendedor</label>
                    <select class="form-select form-select-sm" wire:model.live="cobrador">
                        <option value="">Todos</option>
                        @foreach ($vendedores as $v)
                            <option value="{{ $v }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label small mb-1">Buscar</label>
                    <input type="search" class="form-control form-control-sm"
                           wire:model.live.debounce.300ms="busqueda"
                           placeholder="Cliente, RUT o documento">
                </div>
                <div class="col-6 col-md-2">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="solo-criticas" wire:model.live="soloCriticas">
                        <label class="form-check-label small" for="solo-criticas">
                            Solo críticas
                        </label>
                    </div>
                </div>
                <div class="col-6 col-md-2 text-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            wire:click="limpiarFiltros">
                        <i class="bi bi-x-circle me-1"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── KPIs ─── --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-body-secondary text-uppercase">Deudas</div>
                    <div class="h4 mb-0">{{ number_format($kpis['total_deudas']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-body-secondary text-uppercase">Saldo total</div>
                    <div class="h4 mb-0">Bs. {{ number_format($kpis['total_saldo'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-body-secondary text-uppercase">Saldo vencido</div>
                    <div class="h4 mb-0 text-danger">Bs. {{ number_format($kpis['saldo_vencido'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-body-secondary text-uppercase">Antigüedad prom.</div>
                    <div class="h4 mb-0">{{ round($kpis['antiguedad_promedio']) }} días</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Rankings ─── --}}
    <div class="row g-3 mb-3">
        {{-- Por jefe de ventas --}}
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent">
                    <h3 class="h6 mb-0">
                        <i class="bi bi-bar-chart me-1" aria-hidden="true"></i>
                        Top jefes de ventas por saldo vencido
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr class="small text-body-secondary text-uppercase">
                                    <th>Jefe</th>
                                    <th class="text-center">Deudas</th>
                                    <th class="text-end">Saldo vencido</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($porJefe as $row)
                                    <tr>
                                        <td class="text-break small">{{ $row->jefe }}</td>
                                        <td class="text-center small">{{ number_format($row->total_deudas) }}</td>
                                        <td class="text-end small fw-semibold text-danger">
                                            Bs. {{ number_format($row->saldo_vencido, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-3 small text-body-secondary">Sin datos</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Por vendedor --}}
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent">
                    <h3 class="h6 mb-0">
                        <i class="bi bi-person-badge me-1" aria-hidden="true"></i>
                        Top vendedores por saldo vencido
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr class="small text-body-secondary text-uppercase">
                                    <th>Vendedor</th>
                                    <th class="text-center">Deudas</th>
                                    <th class="text-end">Saldo vencido</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($porVendedor as $row)
                                    <tr>
                                        <td class="text-break small">{{ $row->vendedor }}</td>
                                        <td class="text-center small">{{ number_format($row->total_deudas) }}</td>
                                        <td class="text-end small fw-semibold text-danger">
                                            Bs. {{ number_format($row->saldo_vencido, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-3 small text-body-secondary">Sin datos</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Distribución por antigüedad ─── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent">
            <h3 class="h6 mb-0"><i class="bi bi-hourglass-split me-1"></i> Distribución por antigüedad</h3>
        </div>
        <div class="card-body">
            @php
                $maxSaldo = max(1, $porAntiguedad->max('saldo'));
            @endphp
            <div class="d-flex flex-column gap-2">
                @foreach ($porAntiguedad as $b)
                    <div class="d-flex align-items-center gap-2">
                        <div class="text-end small text-nowrap" style="width: 5rem;">{{ $b->bucket }} d</div>
                        <div class="progress flex-grow-1" style="height: 1.25rem;">
                            <div class="progress-bar
                                @class([
                                    'bg-success' => str_starts_with($b->bucket, '1') || str_starts_with($b->bucket, '31'),
                                    'bg-warning' => str_starts_with($b->bucket, '61') || str_starts_with($b->bucket, '91'),
                                    'bg-danger'  => (int) filter_var($b->bucket, FILTER_SANITIZE_NUMBER_INT) > 120,
                                ])"
                                role="progressbar"
                                style="width: {{ ($b->saldo / $maxSaldo) * 100 }}%"
                                aria-valuenow="{{ $b->saldo }}"
                                aria-valuemin="0"
                                aria-valuemax="{{ $maxSaldo }}"
                            >
                                <span class="small px-2 text-white fw-semibold">
                                    {{ number_format($b->total) }} · Bs. {{ number_format($b->saldo, 0) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ─── Listado priorizado ─── --}}
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-header border-0 d-flex flex-column flex-md-row align-items-md-center gap-3">
            <div>
                <h3 class="h5 mb-1">Deudas priorizadas</h3>
                <p class="mb-0 small text-body-secondary">
                    Ordenadas por score de prioridad (antigüedad + saldo + rango).
                </p>
            </div>
        </div>
        <div class="card-body overflow-visible p-0">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="small text-body-secondary text-uppercase">
                        <th>Cliente</th>
                        <th class="d-none d-md-table-cell">Documento</th>
                        <th class="d-none d-lg-table-cell">Vence</th>
                        <th class="d-none d-lg-table-cell">Vendedor</th>
                        <th class="text-center">Antigüedad</th>
                        <th class="text-end">Saldo</th>
                        <th class="text-center d-none d-md-table-cell">Score</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deudas as $deuda)
                        <tr wire:key="deuda-{{ $deuda->id }}">
                            <td class="text-break">
                                <span class="d-block fw-semibold">{{ $deuda->cliente }}</span>
                                <span class="small text-body-secondary">
                                    {{ $deuda->codigo_cliente }} · {{ $deuda->ciu_nombre }}
                                </span>
                            </td>
                            <td class="d-none d-md-table-cell small">
                                {{ $deuda->num_doc }}
                                <span class="d-block text-body-secondary">RUT: {{ $deuda->rut_id ?: '—' }}</span>
                            </td>
                            <td class="d-none d-lg-table-cell small">
                                {{ optional($deuda->vence)->format('d/m/Y') ?? '—' }}
                                <span class="d-block text-body-secondary">{{ $deuda->rango }}</span>
                            </td>
                            <td class="d-none d-lg-table-cell small text-break">
                                {{ $deuda->ent_nombre_vendedor ?: '—' }}
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill
                                    @class([
                                        'text-bg-success' => $deuda->antiguedad <= 30,
                                        'text-bg-warning' => $deuda->antiguedad > 30 && $deuda->antiguedad <= 90,
                                        'text-bg-danger'  => $deuda->antiguedad > 90,
                                    ])">
                                    {{ $deuda->antiguedad }} d
                                </span>
                            </td>
                            <td class="text-end fw-semibold small">
                                Bs. {{ number_format($deuda->saldo, 2) }}
                            </td>
                            <td class="text-center d-none d-md-table-cell">
                                <span class="badge rounded-pill
                                    @class([
                                        'text-bg-secondary' => $deuda->prioridad_score < 30,
                                        'text-bg-warning'   => $deuda->prioridad_score >= 30 && $deuda->prioridad_score < 60,
                                        'text-bg-danger'    => $deuda->prioridad_score >= 60,
                                    ])">
                                    {{ $deuda->prioridad_score }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-5 text-center text-body-secondary">
                                <i class="bi bi-inbox d-block mb-2 fs-4" aria-hidden="true"></i>
                                No hay deudas con los filtros aplicados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($deudas->hasPages())
            <div class="card-footer">{{ $deudas->links() }}</div>
        @endif
    </div>
</div>