<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">
                <i class="bi bi-boxes me-2 text-primary"></i> Lotes de cobranza
            </h1>
            <p class="mb-0 small text-body-secondary">
                Administra y da seguimiento a los lotes asignados.
            </p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Buscar</label>
                    <input type="text" class="form-control form-control-sm"
                           wire:model.live.debounce.400ms="search"
                           placeholder="Código o nombre...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Estado</label>
                    <select class="form-select form-select-sm" wire:model.live="estado">
                        <option value="">Todos</option>
                        <option value="borrador">Borrador</option>
                        <option value="asignado">Asignado</option>
                        <option value="en_proceso">En proceso</option>
                        <option value="cerrado">Cerrado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Cobrador</th>
                        <th class="text-end">Deudas</th>
                        <th class="text-end">Saldo</th>
                        <th>Asignado</th>
                        <th>Límite</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lotes as $l)
                        <tr wire:key="lote-{{ $l->id }}">
                            <td class="fw-semibold">{{ $l->codigo }}</td>
                            <td>{{ $l->nombre }}</td>
                            <td>{{ $l->cobrador?->name }}</td>
                            <td class="text-end">{{ number_format($l->total_deudas) }}</td>
                            <td class="text-end">{{ number_format((float)$l->total_saldo, 2) }}</td>
                            <td>{{ $l->fecha_asignacion?->format('d/m/Y') }}</td>
                            <td>{{ $l->fecha_limite?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                <span class="badge bg-info-subtle text-info text-capitalize">
                                    {{ str_replace('_', ' ', $l->estado) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-body-secondary">
                                No hay lotes registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent">
            {{ $lotes->links() }}
        </div>
    </div>
</div>