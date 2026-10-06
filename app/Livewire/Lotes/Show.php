<?php

namespace App\Livewire\Lotes;

use App\Models\Lote;
use App\Models\User;
use App\Support\EmpresaActual;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    public Lote $lote;

    public bool $showCancelar = false;

    public bool $showCerrar = false;

    public bool $showEditar = false;

    public ?string $codigoDeudor = null;

    public string $motivo = '';

    public string $nombre = '';

    public string $descripcion = '';

    public ?string $fechaLimite = null;

    public ?int $cobradorId = null;

    public function mount(Lote $lote): void
    {
        $usuario = auth()->user();

        abort_unless($usuario->can('lotes.view'), 403);
        abort_if(! $usuario->can('lotes.view_all') && $lote->user_id !== $usuario->id, 403);
        abort_if(EmpresaActual::id() !== null && $lote->company_id !== EmpresaActual::id(), 404);

        $this->lote = $lote;
    }

    /**
     * Deudores del lote en orden de visita, con sus deudas.
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function deudores(): Collection
    {
        return $this->lote->deudas()
            ->get()
            ->groupBy('pivot.codigo_cliente')
            ->map(function (Collection $deudas, string $codigo) {
                $primera = $deudas->first();

                return [
                    'codigo' => $codigo,
                    'cliente' => $primera->cliente,
                    'ciudad' => $primera->ciu_nombre,
                    'direccion' => $primera->direccion,
                    'telefono' => $primera->telefono,
                    'rut' => $primera->rut_id,
                    'jefe' => $primera->ent_nombre_jefe_vendedor,
                    'supervisor' => $primera->ent_nombre_supervisor,
                    'vendedor' => $primera->ent_nombre_vendedor,
                    'lat' => $primera->coordenada_y,
                    'lon' => $primera->coordenada_x,
                    'orden' => $primera->pivot->orden_visita,
                    'deudas' => $deudas,
                    'saldo' => (float) $deudas->sum('saldo'),
                    'gestionado' => $deudas->contains(fn ($d) => $d->pivot->estado_gestion !== 'pendiente' || (float) $d->pivot->monto_recuperado > 0),
                ];
            })
            ->sortBy(fn (array $d) => $d['orden'] ?? PHP_INT_MAX)
            ->values();
    }

    #[Computed]
    public function cobradores()
    {
        return User::role('Cobrador')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);
    }

    public function verDeudor(string $codigo): void
    {
        $this->codigoDeudor = $this->deudores->contains('codigo', $codigo) ? $codigo : null;
    }

    public function abrirCancelar(): void
    {
        abort_unless(auth()->user()->can('lotes.cancel'), 403);
        $this->motivo = '';
        $this->showCancelar = true;
    }

    public function abrirCerrar(): void
    {
        abort_unless(auth()->user()->can('lotes.close'), 403);
        $this->motivo = '';
        $this->showCerrar = true;
    }

    public function abrirEditar(): void
    {
        abort_unless(auth()->user()->can('lotes.update'), 403);

        $this->nombre = $this->lote->nombre;
        $this->descripcion = (string) $this->lote->descripcion;
        $this->fechaLimite = $this->lote->fecha_limite?->toDateString();
        $this->cobradorId = $this->lote->user_id;
        $this->showEditar = true;
    }

    public function cerrarModales(): void
    {
        $this->reset(['showCancelar', 'showCerrar', 'showEditar', 'motivo', 'codigoDeudor']);
        $this->resetValidation();
    }

    public function cancelar(): void
    {
        abort_unless(auth()->user()->can('lotes.cancel'), 403);
        $this->validate(['motivo' => 'required|string|min:5|max:500'], [], ['motivo' => 'motivo']);

        if (! $this->finalizar('cancelado', $this->motivo)) {
            return;
        }

        $this->notificar('success', 'Lote cancelado. Sus deudas quedaron disponibles para un nuevo lote.');
    }

    public function cerrar(): void
    {
        abort_unless(auth()->user()->can('lotes.close'), 403);
        $this->validate(['motivo' => 'nullable|string|max:500']);

        if (! $this->finalizar('cerrado', $this->motivo !== '' ? $this->motivo : null)) {
            return;
        }

        $this->notificar('success', 'Lote cerrado. Las deudas pendientes quedaron disponibles para un nuevo lote.');
    }

    public function guardarEdicion(): void
    {
        abort_unless(auth()->user()->can('lotes.update'), 403);

        if (! $this->lote->estaAbierto()) {
            $this->notificar('danger', 'Solo se pueden editar lotes abiertos.');

            return;
        }

        $this->validate([
            'nombre' => 'required|string|max:120',
            'descripcion' => 'nullable|string|max:500',
            'fechaLimite' => 'nullable|date|after_or_equal:'.$this->lote->fecha_asignacion?->toDateString(),
            'cobradorId' => 'required|exists:users,id',
        ]);

        $cambios = [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion !== '' ? $this->descripcion : null,
            'fecha_limite' => $this->fechaLimite ?: null,
        ];

        if ($this->cobradorId !== $this->lote->user_id) {
            abort_unless(auth()->user()->can('lotes.assign'), 403);

            if (! User::findOrFail($this->cobradorId)->hasRole('Cobrador')) {
                $this->addError('cobradorId', 'El usuario seleccionado no tiene rol Cobrador.');

                return;
            }

            $cambios['user_id'] = $this->cobradorId;
        }

        $this->lote->update($cambios);
        $this->refrescar();
        $this->cerrarModales();
        $this->notificar('success', 'Lote actualizado.');
    }

    public function quitarDeudor(string $codigo): void
    {
        abort_unless(auth()->user()->can('lotes.update'), 403);

        if (! $this->lote->estaAbierto()) {
            $this->notificar('danger', 'Solo se pueden modificar lotes abiertos.');

            return;
        }

        $deudor = $this->deudores->firstWhere('codigo', $codigo);

        if ($deudor === null) {
            return;
        }

        if ($this->deudores->count() === 1) {
            $this->notificar('warning', 'Es el único deudor del lote. Cancela el lote en su lugar.');

            return;
        }

        if ($deudor['gestionado']) {
            $this->notificar('warning', 'El deudor ya tiene gestiones registradas y no puede quitarse.');

            return;
        }

        DB::transaction(function () use ($codigo) {
            $this->lote->deudas()->wherePivot('codigo_cliente', $codigo)->detach();

            $deudas = $this->lote->deudas()->get();

            $this->lote->update([
                'total_deudores' => $deudas->pluck('pivot.codigo_cliente')->unique()->count(),
                'total_deudas' => $deudas->count(),
                'total_saldo' => $deudas->sum('saldo'),
                'total_importe' => $deudas->sum('importe'),
            ]);
        });

        $this->refrescar();
        $this->notificar('success', 'Deudor quitado del lote. Sus deudas quedaron disponibles.');
    }

    private function finalizar(string $estado, ?string $motivo): bool
    {
        if (! $this->lote->estaAbierto()) {
            $this->cerrarModales();
            $this->notificar('danger', 'El lote ya no está abierto.');

            return false;
        }

        $this->lote->update([
            'estado' => $estado,
            'cerrado_en' => now(),
            'cerrado_por' => auth()->id(),
            'motivo_cierre' => $motivo,
        ]);

        $this->refrescar();
        $this->cerrarModales();

        return true;
    }

    private function refrescar(): void
    {
        $this->lote->refresh();
        unset($this->deudores);
    }

    private function notificar(string $tipo, string $mensaje): void
    {
        $this->dispatch('notify', type: $tipo, message: $mensaje);
    }

    public function render()
    {
        $this->lote->loadMissing(['cobrador', 'creador', 'cerradoPor', 'cartera.company']);

        return view('livewire.lotes.show')
            ->extends('home')
            ->section('content_body');
    }
}
