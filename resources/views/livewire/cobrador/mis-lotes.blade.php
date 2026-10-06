<div>
    <div class="row g-2 mb-3 text-center">
        <div class="col-6">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div class="small text-body-secondary">Gestiones hoy</div>
                <div class="fs-3 fw-bold">{{ $gestionesHoy }}</div>
            </div></div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div class="small text-body-secondary">Cobrado hoy</div>
                <div class="fs-3 fw-bold text-success">{{ number_format($cobradoHoy, 2) }}</div>
            </div></div>
        </div>
    </div>

    @forelse($lotes as $lote)
        @php
            $total = max($lote->total_deudores, 1);
            $hechos = (int) ($gestionados[$lote->id] ?? 0);
            $pct = (int) round($hechos / $total * 100);
            $dias = $lote->fecha_limite ? (int) today()->diffInDays($lote->fecha_limite, false) : null;
        @endphp
        <a href="{{ route('cobrador.ruta', $lote) }}" wire:navigate wire:key="lote-{{ $lote->id }}"
           class="card border-0 shadow-sm mb-3 text-decoration-none text-body">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                    <div class="fw-semibold fs-5">{{ $lote->nombre }}</div>
                    @if($dias !== null)
                        <span class="badge {{ $dias < 0 ? 'text-bg-danger' : ($dias <= 3 ? 'text-bg-warning' : 'text-bg-secondary') }} text-nowrap">
                            {{ $dias < 0 ? 'Vencido' : ($dias === 0 ? 'Vence hoy' : $dias.' d') }}
                        </span>
                    @endif
                </div>
                <div class="small text-body-secondary mb-3">
                    {{ $lote->codigo }} · {{ $lote->cartera?->company?->name }}@if($lote->ciudad) · {{ $lote->ciudad }}@endif
                </div>

                <div class="d-flex justify-content-between small mb-1">
                    <span>{{ $hechos }} de {{ $lote->total_deudores }} deudores gestionados</span>
                    <span class="fw-semibold">{{ $pct }}%</span>
                </div>
                <div class="progress mb-3" style="height: 10px;" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-success" style="width: {{ $pct }}%"></div>
                </div>

                <div class="d-flex justify-content-between">
                    <div>
                        <div class="small text-body-secondary">Por cobrar</div>
                        <div class="fw-semibold">{{ number_format((float) $lote->total_saldo, 2) }}</div>
                    </div>
                    <div class="text-end">
                        <div class="small text-body-secondary">Recuperado</div>
                        <div class="fw-semibold text-success">{{ number_format((float) ($recuperado[$lote->id] ?? 0), 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent text-primary fw-semibold text-center py-3">
                Ver ruta de visitas <i class="bi bi-chevron-right"></i>
            </div>
        </a>
    @empty
        <div class="text-center text-body-secondary py-5">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            No tienes lotes asignados por ahora.
        </div>
    @endforelse
</div>
