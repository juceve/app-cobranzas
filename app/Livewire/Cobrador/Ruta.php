<?php

namespace App\Livewire\Cobrador;

use App\Models\Cobro;
use App\Models\Gestion;
use App\Models\Lote;
use App\Models\Recibo;
use App\Services\ReciboService;
use App\Support\EmpresaActual;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class Ruta extends Component
{
    private const PRIORIDAD_ESTADO = ['pagado' => 5, 'promesa_pago' => 4, 'rechazado' => 3, 'no_contactado' => 2, 'visitado' => 1, 'pendiente' => 0];

    public Lote $lote;

    #[Url]
    public string $vista = 'pendientes';

    #[Url]
    public string $buscar = '';

    public ?string $codigoDeudor = null;

    public string $pestana = 'gestion';

    public string $tipo = 'visita';

    public string $resultado = '';

    public string $observacion = '';

    public ?string $fechaPromesa = null;

    public ?string $montoPrometido = null;

    /** @var array<int|string, bool> */
    public array $cobrar = [];

    /** @var array<int|string, string> */
    public array $montos = [];

    public string $formaPago = 'efectivo';

    public string $referencia = '';

    public string $notaCobro = '';

    public bool $confirmandoCobro = false;

    /** @var array<int, float> deuda_id => monto, pendiente de confirmar */
    public array $resumenCobro = [];

    public ?int $reciboId = null;

    public function mount(Lote $lote): void
    {
        $usuario = auth()->user();

        abort_unless($usuario->can('cobrador.access'), 403);
        abort_unless($lote->user_id === $usuario->id, 403);
        abort_if(EmpresaActual::id() !== null && $lote->company_id !== EmpresaActual::id(), 404);
        abort_unless($lote->estaAbierto(), 403);

        $this->lote = $lote;
    }

    /**
     * Deudores del lote en orden de visita, con sus deudas y datos de contacto.
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function deudores(): Collection
    {
        return $this->lote->deudas()->get()
            ->groupBy('pivot.codigo_cliente')
            ->map(function (Collection $deudas, string $codigo) {
                $primera = $deudas->first();
                $saldo = (float) $deudas->sum('saldo');
                $cobrado = (float) $deudas->sum(fn ($d) => (float) $d->pivot->monto_recuperado);
                $estados = $deudas->pluck('pivot.estado_gestion');
                $estado = $estados->sortByDesc(fn ($e) => self::PRIORIDAD_ESTADO[$e] ?? 0)->first();

                $lat = (float) $primera->coordenada_y;
                $lon = (float) $primera->coordenada_x;
                $coordenadasValidas = $lat >= -23 && $lat <= -9.5 && $lon >= -69.7 && $lon <= -57.4;

                return [
                    'codigo' => $codigo,
                    'cliente' => $primera->cliente,
                    'ciudad' => $primera->ciu_nombre,
                    'direccion' => $primera->direccion,
                    'telefono' => $primera->telefono,
                    'orden' => $primera->pivot->orden_visita,
                    'deudas' => $deudas,
                    'saldo' => $saldo,
                    'cobrado' => $cobrado,
                    'pendiente' => max($saldo - $cobrado, 0),
                    'estado' => $estados->every(fn ($e) => $e === 'pagado') ? 'pagado' : $estado,
                    'gestionado' => $estados->contains(fn ($e) => $e !== 'pendiente'),
                    'tel' => $this->numeroTelefonico($primera->telefono),
                    'mapa' => $coordenadasValidas
                        ? "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lon}"
                        : ($primera->direccion ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($primera->direccion.' '.$primera->ciu_nombre) : null),
                ];
            })
            ->sortBy(fn (array $d) => $d['orden'] ?? PHP_INT_MAX)
            ->values();
    }

    #[Computed]
    public function lista(): Collection
    {
        $termino = mb_strtolower(trim($this->buscar));

        return $this->deudores
            ->when($this->vista === 'pendientes', fn ($c) => $c->filter(fn ($d) => ! $d['gestionado']))
            ->when($this->vista === 'gestionados', fn ($c) => $c->filter(fn ($d) => $d['gestionado']))
            ->when($termino !== '', fn ($c) => $c->filter(
                fn ($d) => str_contains(mb_strtolower($d['cliente'].' '.$d['codigo'].' '.$d['direccion']), $termino)
            ))
            ->values();
    }

    #[Computed]
    public function deudorActual(): ?array
    {
        return $this->codigoDeudor === null ? null : $this->deudores->firstWhere('codigo', $this->codigoDeudor);
    }

    #[Computed]
    public function historial(): Collection
    {
        if ($this->codigoDeudor === null) {
            return collect();
        }

        return Gestion::with(['usuario', 'cobros'])
            ->where('lote_id', $this->lote->id)
            ->where('codigo_cliente', $this->codigoDeudor)
            ->latest('gestionado_en')
            ->limit(20)
            ->get();
    }

    public function abrirDeudor(string $codigo, string $pestana = 'gestion'): void
    {
        if (! $this->deudores->contains('codigo', $codigo)) {
            return;
        }

        $this->limpiarFormularios();
        $this->codigoDeudor = $codigo;
        $this->pestana = $pestana;

        foreach ($this->deudorActual['deudas'] as $deuda) {
            $this->cobrar[$deuda->id] = false;
            $this->montos[$deuda->id] = number_format($this->pendienteDe($deuda), 2, '.', '');
        }
    }

    public function cerrarDeudor(): void
    {
        $this->limpiarFormularios();
        $this->codigoDeudor = null;
    }

    public function guardarGestion(): void
    {
        abort_unless(auth()->user()->can('cobrador.gestionar'), 403);
        abort_if($this->deudorActual === null, 404);

        $resultados = collect(Gestion::RESULTADOS)->except('pago')->keys()->all();

        $this->validate([
            'tipo' => 'required|in:'.implode(',', array_keys(Gestion::TIPOS)),
            'resultado' => 'required|in:'.implode(',', $resultados),
            'observacion' => 'nullable|string|max:500',
            'fechaPromesa' => 'required_if:resultado,promesa_pago|nullable|date|after_or_equal:today',
            'montoPrometido' => 'required_if:resultado,promesa_pago|nullable|numeric|gt:0',
        ], [
            'resultado.required' => 'Elige cómo te fue con el deudor.',
            'fechaPromesa.required_if' => 'Indica la fecha prometida.',
            'montoPrometido.required_if' => 'Indica el monto prometido.',
        ]);

        $esPromesa = $this->resultado === 'promesa_pago';

        DB::transaction(function () use ($esPromesa) {
            Gestion::create([
                'lote_id' => $this->lote->id,
                'codigo_cliente' => $this->codigoDeudor,
                'user_id' => auth()->id(),
                'tipo' => $this->tipo,
                'resultado' => $this->resultado,
                'observacion' => $this->observacion !== '' ? $this->observacion : null,
                'fecha_promesa' => $esPromesa ? $this->fechaPromesa : null,
                'monto_prometido' => $esPromesa ? $this->montoPrometido : null,
            ]);

            DB::table('lote_deuda')
                ->where('lote_id', $this->lote->id)
                ->where('codigo_cliente', $this->codigoDeudor)
                ->where('estado_gestion', '<>', 'pagado')
                ->update([
                    'estado_gestion' => Gestion::RESULTADOS[$this->resultado][1],
                    'fecha_gestion' => today(),
                    'observacion' => $this->observacion !== '' ? $this->observacion : null,
                    'gestionado_por' => auth()->id(),
                    'updated_at' => now(),
                ]);
        });

        $this->terminar('Gestión registrada.');
    }

    public function registrarCobro(): void
    {
        abort_unless(auth()->user()->can('cobrador.cobrar'), 403);
        abort_if($this->deudorActual === null, 404);

        if (! $this->confirmandoCobro) {
            $this->revisarCobro();

            return;
        }

        $cobros = $this->validarCobro();

        if ($cobros === null) {
            $this->confirmandoCobro = false;

            return;
        }

        $deudas = $this->deudorActual['deudas']->keyBy('id');
        $cliente = $this->deudorActual['cliente'];

        $recibo = DB::transaction(function () use ($cobros, $deudas, $cliente) {
            $gestion = Gestion::create([
                'lote_id' => $this->lote->id,
                'codigo_cliente' => $this->codigoDeudor,
                'user_id' => auth()->id(),
                'tipo' => $this->tipo,
                'resultado' => 'pago',
                'observacion' => $this->notaCobro !== '' ? $this->notaCobro : null,
            ]);

            $creados = collect();

            foreach ($cobros as $id => $monto) {
                $creados->push(Cobro::create([
                    'lote_id' => $this->lote->id,
                    'deudacartera_id' => $id,
                    'gestion_id' => $gestion->id,
                    'user_id' => auth()->id(),
                    'monto' => $monto,
                    'fecha_cobro' => today(),
                    'forma_pago' => $this->formaPago,
                    'referencia' => $this->referencia !== '' ? $this->referencia : null,
                ]));

                $deuda = $deudas->get($id);
                $recuperado = (float) $deuda->pivot->monto_recuperado + $monto;
                $completa = $recuperado >= (float) $deuda->saldo - 0.009;

                $this->lote->deudas()->updateExistingPivot($id, [
                    'monto_recuperado' => $recuperado,
                    'estado_gestion' => $completa ? 'pagado' : 'visitado',
                    'fecha_gestion' => today(),
                    'gestionado_por' => auth()->id(),
                ]);
            }

            return ReciboService::emitir($this->lote, $gestion, $creados, $cliente);
        });

        $this->terminar('Cobro registrado. Recibo '.$recibo->numero.'.');
        $this->reciboId = $recibo->id;
    }

    public function revisarCobro(): void
    {
        abort_unless(auth()->user()->can('cobrador.cobrar'), 403);
        abort_if($this->deudorActual === null, 404);

        $cobros = $this->validarCobro();

        if ($cobros === null) {
            return;
        }

        $this->resumenCobro = $cobros;
        $this->confirmandoCobro = true;
    }

    public function volverACobro(): void
    {
        $this->confirmandoCobro = false;
        $this->resumenCobro = [];
    }

    public function verRecibo(int $id): void
    {
        $existe = Recibo::where('id', $id)->where('lote_id', $this->lote->id)->where('user_id', auth()->id())->exists();

        $this->reciboId = $existe ? $id : null;
    }

    public function cerrarRecibo(): void
    {
        $this->reciboId = null;
    }

    #[Computed]
    public function reciboActual(): ?Recibo
    {
        if ($this->reciboId === null) {
            return null;
        }

        return Recibo::with('cobros.deuda')
            ->where('id', $this->reciboId)
            ->where('lote_id', $this->lote->id)
            ->where('user_id', auth()->id())
            ->first();
    }

    /**
     * Valida el formulario de cobro y devuelve deuda_id => monto, o null si hay errores.
     *
     * @return array<int, float>|null
     */
    private function validarCobro(): ?array
    {
        $this->validate([
            'formaPago' => 'required|in:'.implode(',', array_keys(Cobro::FORMAS_PAGO)),
            'referencia' => ($this->formaPago === 'efectivo' ? 'nullable' : 'required').'|string|max:100',
            'notaCobro' => 'nullable|string|max:500',
        ], ['referencia.required' => 'Indica el número de referencia o comprobante.']);

        $seleccion = collect($this->cobrar)->filter()->keys()->map(fn ($id) => (int) $id);

        if ($seleccion->isEmpty()) {
            $this->addError('cobrar', 'Marca al menos una deuda que pagó.');

            return null;
        }

        $deudas = $this->deudorActual['deudas']->keyBy('id');
        $cobros = [];

        foreach ($seleccion as $id) {
            $deuda = $deudas->get($id);
            $monto = (float) str_replace(',', '.', (string) ($this->montos[$id] ?? 0));

            if ($deuda === null) {
                return null;
            }

            $pendiente = $this->pendienteDe($deuda);

            if ($monto <= 0 || $monto > $pendiente + 0.009) {
                $this->addError("montos.{$id}", 'El monto debe ser mayor a 0 y no superar '.number_format($pendiente, 2).'.');

                return null;
            }

            $cobros[$id] = round($monto, 2);
        }

        return $cobros;
    }

    private function pendienteDe($deuda): float
    {
        return max((float) $deuda->saldo - (float) $deuda->pivot->monto_recuperado, 0);
    }

    private function terminar(string $mensaje): void
    {
        $this->cerrarDeudor();
        unset($this->deudores, $this->lista, $this->deudorActual, $this->historial, $this->reciboActual);
        $this->dispatch('notify', type: 'success', message: $mensaje);
    }

    private function limpiarFormularios(): void
    {
        $this->reset(['pestana', 'tipo', 'resultado', 'observacion', 'fechaPromesa', 'montoPrometido', 'cobrar', 'montos', 'formaPago', 'referencia', 'notaCobro', 'confirmandoCobro', 'resumenCobro']);
        $this->resetValidation();
    }

    private function numeroTelefonico(?string $telefono): ?string
    {
        $digitos = preg_replace('/\D+/', '', (string) $telefono);
        $digitos = ltrim($digitos, '0');

        if (strlen($digitos) < 7) {
            return null;
        }

        return strlen($digitos) <= 8 ? '591'.$digitos : $digitos;
    }

    public function render()
    {
        return view('livewire.cobrador.ruta', [
            'pendientes' => $this->deudores->where('gestionado', false)->count(),
            'gestionados' => $this->deudores->where('gestionado', true)->count(),
        ])->extends('layouts.cobrador', [
            'titulo' => $this->lote->nombre,
            'subtitulo' => $this->lote->codigo,
            'volver' => route('cobrador.lotes'),
        ])->section('content_body');
    }
}
