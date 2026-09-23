<div class="container-fluid py-4">
    {{-- Encabezado --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">
                <i class="bi bi-list-ul me-2 text-primary"></i>
                Deudas de la cartera
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

    {{-- Totales --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-body-secondary small">Registros</div>
                    <div class="h4 mb-0">{{ number_format($totales['registros']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-body-secondary small">Saldo total</div>
                    <div class="h4 mb-0">{{ number_format($totales['saldo'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-body-secondary small">Importe total</div>
                    <div class="h4 mb-0">{{ number_format($totales['importe'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

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
                        @foreach($estados as $e)
                            <option value="{{ $e }}">{{ $e }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Rango</label>
                    <select class="form-select form-select-sm" wire:model.live="rango">
                        <option value="">Todos</option>
                        @foreach($rangos as $r)
                            <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Saldo mínimo</label>
                    <input type="number" step="0.01" class="form-control form-control-sm" wire:model.live.debounce.500ms="saldoMin">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Saldo máximo</label>
                    <input type="number" step="0.01" class="form-control form-control-sm" wire:model.live.debounce.500ms="saldoMax">
                </div>
            </div>
            <div class="mt-2 d-flex justify-content-between align-items-center">
                <button class="btn btn-sm btn-outline-secondary" wire:click="clearFilters">
                    <i class="bi bi-x-circle me-1"></i> Limpiar filtros
                </button>
                <div class="small text-body-secondary">
                    Mostrando {{ $deudas->count() }} de {{ $deudas->total() }}
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
                        <th role="button" wire:click="sortBy('num_doc')">NumDoc</th>
                        <th role="button" wire:click="sortBy('cliente')">Cliente</th>
                        <th role="button" wire:click="sortBy('importe')" class="text-end">Importe</th>
                        <th role="button" wire:click="sortBy('saldo')" class="text-end">Saldo</th>
                        <th role="button" wire:click="sortBy('vence')">Vence</th>
                        <th role="button" wire:click="sortBy('antiguedad')" class="text-center">Antigüedad</th>
                        <th>Rango</th>
                        <th>Estado</th>
                        <th>Ciudad</th>
                        <th>Vendedor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deudas as $d)
                        <tr>
                            <td class="fw-semibold">{{ $d->num_doc }}</td>
                            <td>
                                <div class="text-truncate" style="max-width: 220px;" title="{{ $d->cliente }}">
                                    {{ $d->cliente }}
                                </div>
                                <div class="small text-body-secondary">{{ $d->codigo_cliente }}</div>
                            </td>
                            <td class="text-end">{{ number_format((float) $d->importe, 2) }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $d->saldo, 2) }}</td>
                            <td>{{ $d->vence ? \Carbon\Carbon::parse($d->vence)->format('d/m/Y') : '—' }}</td>
                            <td class="text-center">{{ $d->antiguedad ?? '—' }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $d->rango }}</span></td>
                            <td>
                                <span class="badge {{ $d->estado === 'VENCIDAS' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}">
                                    {{ $d->estado }}
                                </span>
                            </td>
                            <td>{{ $d->ciu_nombre }}</td>
                            <td class="small">{{ $d->ent_nombre_vendedor }}</td>
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
            {{ $deudas->links() }}
        </div>
    </div>
</div>