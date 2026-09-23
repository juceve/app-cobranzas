<?php

namespace App\Livewire\Lotes;

use App\Models\Cartera;
use App\Models\Deudacartera;
use App\Models\Lote;
use App\Models\User;
use App\Services\DeudaTramoService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ConstructorLote extends Component
{
    use WithPagination;

    public Cartera $cartera;

    // === Filtros de la grilla ===
    #[Url] public string $search = '';
    #[Url] public string $estado = '';
    #[Url] public string $rango = '';
    #[Url] public string $tramo = '';
    #[Url] public ?float $saldoMin = null;
    #[Url] public ?float $saldoMax = null;
    #[Url] public string $ordenarPor = 'antiguedad';
    #[Url] public string $ordenDir = 'desc';
    public int $perPage = 15;

    // === Selección ===
    public array $seleccionados = [];

    // === Datos del nuevo lote ===
    public string $nombreLote = '';
    public string $descripcionLote = '';
    public ?int $cobradorId = null;
    public ?string $fechaAsignacion = null;
    public ?string $fechaLimite = null;

    public bool $showModalCrear = false;

    public function mount(Cartera $cartera): void
    {
        // Doble chequeo de autorización (por si acaso)
        abort_unless(
            auth()->user()->hasAnyRole(['JefeVentas', 'Admin']),
            403
        );

        $this->cartera = $cartera;
        $this->fechaAsignacion = now()->toDateString();
    }

    // --------------------------------------------------------------
    //  QUERY BASE
    // --------------------------------------------------------------
    protected function queryBase()
    {
        return Deudacartera::query()
            ->where('cartera_id', $this->cartera->id)
            ->whereDoesntHave('lotes', function ($q) {
                $q->whereIn('lotes.estado', ['borrador', 'asignado', 'en_proceso']);
            });
    }

    protected function aplicarFiltros($query)
    {
        return $query
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('num_doc', 'like', "%{$this->search}%")
                        ->orWhere('cliente', 'like', "%{$this->search}%")
                        ->orWhere('codigo_cliente', 'like', "%{$this->search}%")
                        ->orWhere('rut_id', 'like', "%{$this->search}%")
                        ->orWhere('telefono', 'like', "%{$this->search}%");
                });
            })
            ->when($this->estado, fn($q) => $q->where('estado', $this->estado))
            ->when($this->rango, fn($q) => $q->where('rango', $this->rango))
            ->when($this->tramo, function ($q) {
                [$min, $max] = $this->rangoTramo($this->tramo);
                $q->whereBetween('antiguedad', [$min, $max]);
            })
            ->when($this->saldoMin !== null, fn($q) => $q->where('saldo', '>=', $this->saldoMin))
            ->when($this->saldoMax !== null, fn($q) => $q->where('saldo', '<=', $this->saldoMax));
    }

    #[Computed]
    public function deudas()
    {
        return $this->aplicarFiltros($this->queryBase())
            ->orderBy($this->ordenarPor, $this->ordenDir)
            ->paginate($this->perPage);
    }

    protected function rangoTramo(string $tramo): array
    {
        return match ($tramo) {
            'mas_251' => [251, 99999],
            '211_250' => [211, 250],
            '181_210' => [181, 210],
            '151_180' => [151, 180],
            '121_150' => [121, 150],
            '91_120'  => [91, 120],
            '61_90'   => [61, 90],
            '31_60'   => [31, 60],
            '01_30'   => [1, 30],
            default   => [0, 0],
        };
    }

    // --------------------------------------------------------------
    //  CARRITO / TOTALES
    // --------------------------------------------------------------
    #[Computed]
    public function totalesSeleccion(): array
    {
        if (empty($this->seleccionados)) {
            return ['count' => 0, 'saldo' => 0, 'importe' => 0, 'tramos' => []];
        }

        $deudas = Deudacartera::whereIn('id', $this->seleccionados)
            ->get(['id', 'saldo', 'importe', 'antiguedad']);

        $tramos = [];
        foreach ($deudas as $d) {
            $t = DeudaTramoService::tramo((int) $d->antiguedad);
            $tramos[$t] = ($tramos[$t] ?? 0) + 1;
        }

        return [
            'count'   => $deudas->count(),
            'saldo'   => (float) $deudas->sum('saldo'),
            'importe' => (float) $deudas->sum('importe'),
            'tramos'  => $tramos,
        ];
    }

    // --------------------------------------------------------------
    //  CATÁLOGOS
    // --------------------------------------------------------------
    #[Computed]
    public function estadosDisponibles()
    {
        return Deudacartera::where('cartera_id', $this->cartera->id)
            ->distinct()->pluck('estado')->filter()->values();
    }

    #[Computed]
    public function rangosDisponibles()
    {
        return Deudacartera::where('cartera_id', $this->cartera->id)
            ->distinct()->pluck('rango')->filter()->values();
    }

    #[Computed]
    public function cobradores()
    {
        return User::role('Cobrador')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    // --------------------------------------------------------------
    //  ACCIONES TABLA
    // --------------------------------------------------------------
    public function sortBy(string $campo): void
    {
        if ($this->ordenarPor === $campo) {
            $this->ordenDir = $this->ordenDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenarPor = $campo;
            $this->ordenDir = 'desc';
        }
    }

    public function toggleSeleccion(int $id): void
    {
        if (in_array($id, $this->seleccionados, true)) {
            $this->seleccionados = array_values(array_diff($this->seleccionados, [$id]));
        } else {
            $this->seleccionados[] = $id;
        }
    }

    public function seleccionarPagina(): void
    {
        $ids = $this->deudas->pluck('id')->toArray();
        $this->seleccionados = array_values(array_unique(array_merge($this->seleccionados, $ids)));
    }

    public function seleccionarFiltradas(): void
    {
        $ids = $this->aplicarFiltros($this->queryBase())->pluck('id')->toArray();
        $this->seleccionados = $ids;
        $this->dispatch('notify', type: 'info', message: count($ids) . ' deudas seleccionadas.');
    }

    public function limpiarSeleccion(): void
    {
        $this->seleccionados = [];
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'estado', 'rango', 'tramo', 'saldoMin', 'saldoMax']);
        $this->resetPage();
    }

    // --------------------------------------------------------------
    //  MODAL
    // --------------------------------------------------------------
    public function abrirModalCrear(): void
    {
        if (empty($this->seleccionados)) {
            $this->dispatch('notify', type: 'warning', message: 'Selecciona al menos una deuda.');
            return;
        }

        $this->nombreLote = 'Lote ' . now()->format('d/m/Y H:i');
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
        $this->validate([
            'nombreLote'      => 'required|string|max:120',
            'descripcionLote' => 'nullable|string|max:500',
            'cobradorId'      => 'required|exists:users,id',
            'fechaAsignacion' => 'required|date',
            'fechaLimite'     => 'nullable|date|after_or_equal:fechaAsignacion',
        ]);

        $cobrador = User::findOrFail($this->cobradorId);
        if (!$cobrador->hasRole('Cobrador')) {
            $this->addError('cobradorId', 'El usuario seleccionado no tiene rol Cobrador.');
            return;
        }

        DB::transaction(function () use ($cobrador) {
            $deudas = Deudacartera::whereIn('id', $this->seleccionados)->get();

            $lote = Lote::create([
                'cartera_id'       => $this->cartera->id,
                'company_id'       => $this->cartera->company_id,
                'user_id'          => $cobrador->id,
                'created_by'       => auth()->id(),
                'codigo'           => Lote::generarCodigo(),
                'nombre'           => $this->nombreLote,
                'descripcion'      => $this->descripcionLote,
                'estado'           => 'asignado',
                'fecha_asignacion' => $this->fechaAsignacion,
                'fecha_limite'     => $this->fechaLimite,
                'total_deudas'     => $deudas->count(),
                'total_saldo'      => $deudas->sum('saldo'),
                'total_importe'    => $deudas->sum('importe'),
            ]);

            $lote->deudas()->attach($deudas->pluck('id'));
        });

        $this->dispatch('notify', type: 'success', message: 'Lote creado y asignado correctamente.');
        $this->cerrarModalCrear();
        $this->reset(['seleccionados']);
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.lotes.constructor-lote')
            ->layout('adminlte::page', [
                'title' => 'Constructor de lotes · ' . ($this->cartera->company?->name ?? 'Cartera'),
            ]);
    }
}