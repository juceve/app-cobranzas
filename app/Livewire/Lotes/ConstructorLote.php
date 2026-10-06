<?php

namespace App\Livewire\Lotes;

use App\Models\Cartera;
use App\Models\Deudacartera;
use App\Models\Lote;
use App\Models\User;
use App\Services\RutaVisitaService;
use App\Support\EmpresaActual;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ConstructorLote extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    private const COORDENADA_VALIDA = '(coordenada_y BETWEEN -23 AND -9.5 AND coordenada_x BETWEEN -69.7 AND -57.4)';

    private const SORTABLES = ['cliente', 'total_deudas', 'total_saldo', 'saldo_vencido', 'antiguedad_max'];

    public Cartera $cartera;

    #[Url]
    public string $search = '';

    #[Url]
    public string $ciudad = '';

    #[Url]
    public bool $soloVencidas = false;

    #[Url]
    public bool $soloConCoordenadas = false;

    #[Url]
    public ?int $antiguedadMin = null;

    #[Url]
    public ?float $saldoMin = null;

    #[Url]
    public ?float $saldoMax = null;

    #[Url]
    public string $ordenarPor = 'saldo_vencido';

    #[Url]
    public string $ordenDir = 'desc';

    public int $perPage = 15;

    /** @var array<int, string> códigos de cliente */
    public array $seleccionados = [];

    public int $tamanoSugerido = 30;

    public float $radioMaxKm = 25;

    public string $nombreLote = '';

    public string $descripcionLote = '';

    public ?int $cobradorId = null;

    public ?string $fechaAsignacion = null;

    public ?string $fechaLimite = null;

    public bool $showModalCrear = false;

    /** @var array<string, mixed> valores del formulario, se aplican al pulsar Filtrar */
    public array $f = [];

    public function mount(Cartera $cartera): void
    {
        abort_unless(auth()->user()->can('lotes.create'), 403);
        abort_if(EmpresaActual::id() !== null && $cartera->company_id !== EmpresaActual::id(), 404);

        $this->cartera = $cartera;
        $this->fechaAsignacion = now()->toDateString();
        $this->copiarFiltrosAlFormulario();
    }

    public function filtrar(): void
    {
        $numero = fn (string $clave) => ($this->f[$clave] ?? '') === '' ? null : $this->f[$clave];

        $this->search = trim((string) ($this->f['search'] ?? ''));
        $this->ciudad = (string) ($this->f['ciudad'] ?? '');
        $this->soloVencidas = (bool) ($this->f['soloVencidas'] ?? false);
        $this->soloConCoordenadas = (bool) ($this->f['soloConCoordenadas'] ?? false);
        $this->antiguedadMin = $numero('antiguedadMin') === null ? null : (int) $numero('antiguedadMin');
        $this->saldoMin = $numero('saldoMin') === null ? null : (float) $numero('saldoMin');
        $this->saldoMax = $numero('saldoMax') === null ? null : (float) $numero('saldoMax');

        $this->resetPage();
    }

    private function copiarFiltrosAlFormulario(): void
    {
        $this->f = [
            'search' => $this->search,
            'ciudad' => $this->ciudad,
            'soloVencidas' => $this->soloVencidas,
            'soloConCoordenadas' => $this->soloConCoordenadas,
            'antiguedadMin' => $this->antiguedadMin,
            'saldoMin' => $this->saldoMin,
            'saldoMax' => $this->saldoMax,
        ];
    }

    public function updating(string $campo): void
    {
        if (in_array($campo, ['search', 'ciudad', 'soloVencidas', 'soloConCoordenadas', 'antiguedadMin', 'saldoMin', 'saldoMax'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Deudas disponibles: vigentes en la cartera, con saldo y sin lote activo.
     */
    protected function deudasDisponibles(): Builder
    {
        return Deudacartera::query()
            ->where('cartera_id', $this->cartera->id)
            ->where('ausente', false)
            ->where('saldo', '>', 0)
            ->whereNotNull('codigo_cliente')
            ->where('codigo_cliente', '<>', '')
            ->whereDoesntHave('lotes', fn ($q) => $q->whereIn('lotes.estado', Lote::ESTADOS_ABIERTOS));
    }

    /**
     * Una fila por deudor con sus totales y coordenada promedio válida.
     */
    protected function deudoresAgrupados(): Builder
    {
        $ok = self::COORDENADA_VALIDA;

        return $this->deudasDisponibles()
            ->selectRaw("
                codigo_cliente,
                MAX(cliente) AS cliente,
                MAX(ciu_nombre) AS ciudad,
                MAX(telefono) AS telefono,
                MAX(direccion) AS direccion,
                COUNT(*) AS total_deudas,
                SUM(saldo) AS total_saldo,
                SUM(CASE WHEN rango = 'VENCIDAS' THEN saldo ELSE 0 END) AS saldo_vencido,
                MAX(antiguedad) AS antiguedad_max,
                AVG(CASE WHEN {$ok} THEN coordenada_y END) AS lat,
                AVG(CASE WHEN {$ok} THEN coordenada_x END) AS lon
            ")
            ->groupBy('codigo_cliente');
    }

    protected function deudoresFiltrados(): Builder
    {
        $ok = self::COORDENADA_VALIDA;

        return $this->deudoresAgrupados()
            ->when($this->search !== '', function (Builder $q) {
                $q->where(function (Builder $sub) {
                    $sub->where('cliente', 'ilike', "%{$this->search}%")
                        ->orWhere('codigo_cliente', 'ilike', "%{$this->search}%")
                        ->orWhere('rut_id', 'ilike', "%{$this->search}%")
                        ->orWhere('telefono', 'ilike', "%{$this->search}%")
                        ->orWhere('direccion', 'ilike', "%{$this->search}%");
                });
            })
            ->when($this->ciudad !== '', fn (Builder $q) => $q->where('ciu_nombre', $this->ciudad))
            ->when($this->soloVencidas, fn (Builder $q) => $q->havingRaw("SUM(CASE WHEN rango = 'VENCIDAS' THEN saldo ELSE 0 END) > 0"))
            ->when($this->soloConCoordenadas, fn (Builder $q) => $q->havingRaw("AVG(CASE WHEN {$ok} THEN coordenada_y END) IS NOT NULL"))
            ->when($this->antiguedadMin !== null, fn (Builder $q) => $q->havingRaw('MAX(antiguedad) >= ?', [$this->antiguedadMin]))
            ->when($this->saldoMin !== null, fn (Builder $q) => $q->havingRaw('SUM(saldo) >= ?', [$this->saldoMin]))
            ->when($this->saldoMax !== null, fn (Builder $q) => $q->havingRaw('SUM(saldo) <= ?', [$this->saldoMax]));
    }

    #[Computed]
    public function deudores()
    {
        $orden = in_array($this->ordenarPor, self::SORTABLES, true) ? $this->ordenarPor : 'saldo_vencido';
        $dir = $this->ordenDir === 'asc' ? 'asc' : 'desc';

        return $this->deudoresFiltrados()
            ->orderBy($orden, $dir)
            ->orderBy('codigo_cliente')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function ciudades(): Collection
    {
        return $this->deudasDisponibles()->whereNotNull('ciu_nombre')->distinct()->orderBy('ciu_nombre')->pluck('ciu_nombre');
    }

    #[Computed]
    public function cobradores()
    {
        return User::role('Cobrador')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);
    }

    /**
     * Filas agrupadas de los deudores seleccionados.
     */
    protected function filasSeleccion(): Collection
    {
        if ($this->seleccionados === []) {
            return collect();
        }

        return $this->deudoresAgrupados()->whereIn('codigo_cliente', $this->seleccionados)->get();
    }

    #[Computed]
    public function totalesSeleccion(): array
    {
        $filas = $this->filasSeleccion();

        return [
            'deudores' => $filas->count(),
            'deudas' => (int) $filas->sum('total_deudas'),
            'saldo' => (float) $filas->sum('total_saldo'),
            'vencido' => (float) $filas->sum('saldo_vencido'),
            'sin_coordenadas' => $filas->filter(fn ($f) => $f->lat === null)->count(),
            'ciudades' => $filas->pluck('ciudad')->filter()->unique()->values()->all(),
        ];
    }

    public function sortBy(string $campo): void
    {
        if (! in_array($campo, self::SORTABLES, true)) {
            return;
        }

        $this->ordenDir = $this->ordenarPor === $campo && $this->ordenDir === 'desc' ? 'asc' : 'desc';
        $this->ordenarPor = $campo;
    }

    public function toggleSeleccion(string $codigo): void
    {
        if (in_array($codigo, $this->seleccionados, true)) {
            $this->seleccionados = array_values(array_diff($this->seleccionados, [$codigo]));
        } else {
            $this->seleccionados[] = $codigo;
        }
    }

    public function seleccionarPagina(): void
    {
        $codigos = $this->deudores->pluck('codigo_cliente')->all();
        $this->seleccionados = array_values(array_unique(array_merge($this->seleccionados, $codigos)));
    }

    public function seleccionarFiltrados(): void
    {
        $this->seleccionados = $this->deudoresFiltrados()->get()->pluck('codigo_cliente')->all();
        $this->dispatch('notify', type: 'info', message: count($this->seleccionados).' deudores seleccionados.');
    }

    public function limpiarSeleccion(): void
    {
        $this->seleccionados = [];
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'ciudad', 'soloVencidas', 'soloConCoordenadas', 'antiguedadMin', 'saldoMin', 'saldoMax']);
        $this->copiarFiltrosAlFormulario();
        $this->resetPage();
    }

    /**
     * Selecciona los N deudores filtrados más cercanos a la semilla (por defecto, el de mayor saldo vencido).
     */
    public function sugerirLote(?string $semilla = null): void
    {
        $tamano = max(1, min($this->tamanoSugerido, 200));
        $filas = $this->deudoresFiltrados()->get();

        $conCoordenadas = $filas->filter(fn ($f) => $f->lat !== null)->values();

        if ($conCoordenadas->isEmpty()) {
            $this->dispatch('notify', type: 'warning', message: 'No hay deudores con coordenadas válidas en el filtro actual.');

            return;
        }

        $radioMax = max(0.5, min($this->radioMaxKm, 1000));

        $origen = $semilla !== null
            ? $conCoordenadas->firstWhere('codigo_cliente', $semilla)
            : RutaVisitaService::mejorSemilla($conCoordenadas, $radioMax, $tamano);

        if ($origen === null) {
            $this->dispatch('notify', type: 'warning', message: 'El deudor elegido no tiene coordenadas válidas.');

            return;
        }

        $cercanos = RutaVisitaService::masCercanos($conCoordenadas, $origen, $tamano, $radioMax);

        $this->seleccionados = $cercanos->pluck('codigo_cliente')->all();

        $radio = number_format((float) $cercanos->max('distancia_km'), 1);
        $this->dispatch('notify', type: 'success', message: "{$cercanos->count()} deudores sugeridos en un radio de {$radio} km.");
    }

    public function abrirModalCrear(): void
    {
        if ($this->seleccionados === []) {
            $this->dispatch('notify', type: 'warning', message: 'Selecciona al menos un deudor.');

            return;
        }

        $ciudades = $this->totalesSeleccion['ciudades'];

        $this->nombreLote = (count($ciudades) === 1 ? $ciudades[0].' · ' : '').now()->format('d/m/Y H:i');
        $this->descripcionLote = '';
        $this->cobradorId = null;
        $this->fechaLimite = now()->addDays(30)->toDateString();
        $this->showModalCrear = true;
    }

    public function cerrarModalCrear(): void
    {
        $this->showModalCrear = false;
        $this->resetValidation();
    }

    public function crearLote(): void
    {
        abort_unless(auth()->user()->can('lotes.create') && auth()->user()->can('lotes.assign'), 403);
        $this->validate([
            'nombreLote' => 'required|string|max:120',
            'descripcionLote' => 'nullable|string|max:500',
            'cobradorId' => 'required|exists:users,id',
            'fechaAsignacion' => 'required|date',
            'fechaLimite' => 'nullable|date|after_or_equal:fechaAsignacion',
        ]);

        $cobrador = User::findOrFail($this->cobradorId);
        if (! $cobrador->hasRole('Cobrador')) {
            $this->addError('cobradorId', 'El usuario seleccionado no tiene rol Cobrador.');

            return;
        }

        $creado = DB::transaction(function () use ($cobrador): bool {
            $filas = $this->filasSeleccion();

            if ($filas->count() !== count($this->seleccionados)) {
                return false;
            }

            $orden = RutaVisitaService::ordenar($filas);
            $deudas = $this->deudasDisponibles()->whereIn('codigo_cliente', $this->seleccionados)->get();
            $ciudades = $filas->pluck('ciudad')->filter()->unique();

            $lote = Lote::create([
                'cartera_id' => $this->cartera->id,
                'company_id' => $this->cartera->company_id,
                'user_id' => $cobrador->id,
                'created_by' => auth()->id(),
                'codigo' => Lote::generarCodigo(),
                'nombre' => $this->nombreLote,
                'descripcion' => $this->descripcionLote,
                'ciudad' => $ciudades->count() === 1 ? $ciudades->first() : null,
                'estado' => 'asignado',
                'fecha_asignacion' => $this->fechaAsignacion,
                'fecha_limite' => $this->fechaLimite,
                'total_deudores' => $filas->count(),
                'total_deudas' => $deudas->count(),
                'total_saldo' => $deudas->sum('saldo'),
                'total_importe' => $deudas->sum('importe'),
            ]);

            $lote->deudas()->attach(
                $deudas->mapWithKeys(fn ($d) => [
                    $d->id => ['codigo_cliente' => $d->codigo_cliente, 'orden_visita' => $orden[$d->codigo_cliente] ?? null],
                ])->all()
            );

            return true;
        });

        if (! $creado) {
            $this->cerrarModalCrear();
            $this->seleccionados = [];
            $this->dispatch('notify', type: 'danger', message: 'Algunos deudores ya fueron asignados a otro lote. Vuelve a seleccionar.');

            return;
        }

        $this->dispatch('notify', type: 'success', message: 'Lote creado y asignado correctamente.');
        $this->cerrarModalCrear();
        $this->reset(['seleccionados']);
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.lotes.constructor-lote')
            ->extends('home')
            ->section('content_body');
    }
}
