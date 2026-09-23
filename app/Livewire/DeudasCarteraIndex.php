<?php

namespace App\Livewire;

use App\Models\Cartera;
use App\Models\Deudacartera;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class DeudasCarteraIndex extends Component
{
    use WithPagination;

    public Cartera $cartera;

    public string $search        = '';
    public string $estado        = '';
    public string $rango         = '';
    public string $orderBy       = 'id';
    public string $orderDir      = 'desc';
    public int    $perPage       = 25;
    public ?string $saldoMin     = null;
    public ?string $saldoMax     = null;

    protected string $paginationTheme = 'bootstrap';

    public function mount(Cartera $cartera): void
    {
        abort_unless(auth()->user()?->can('carteras.view'), 403);
        $this->cartera = $cartera->load('company');
    }

    public function updating($field): void
    {
        if (in_array($field, ['search', 'estado', 'rango', 'saldoMin', 'saldoMax', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, ['id','num_doc','cliente','saldo','importe','vence','antiguedad','fecha'], true)) {
            return;
        }

        if ($this->orderBy === $column) {
            $this->orderDir = $this->orderDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->orderBy  = $column;
            $this->orderDir = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'estado', 'rango', 'saldoMin', 'saldoMax']);
        $this->resetPage();
    }

    public function render()
    {
        $query = Deudacartera::query()
            ->where('cartera_id', $this->cartera->id)
            ->when($this->search !== '', function (Builder $q) {
                $q->where(function (Builder $qq) {
                    $qq->where('num_doc', 'ilike', "%{$this->search}%")
                       ->orWhere('cliente', 'ilike', "%{$this->search}%")
                       ->orWhere('codigo_cliente', 'ilike', "%{$this->search}%")
                       ->orWhere('rut_id', 'ilike', "%{$this->search}%")
                       ->orWhere('telefono', 'ilike', "%{$this->search}%");
                });
            })
            ->when($this->estado !== '', fn (Builder $q) => $q->where('estado', $this->estado))
            ->when($this->rango !== '', fn (Builder $q) => $q->where('rango', $this->rango))
            ->when($this->saldoMin !== null && $this->saldoMin !== '', fn (Builder $q) => $q->where('saldo', '>=', (float) $this->saldoMin))
            ->when($this->saldoMax !== null && $this->saldoMax !== '', fn (Builder $q) => $q->where('saldo', '<=', (float) $this->saldoMax))
            ->orderBy($this->orderBy, $this->orderDir);

        $deudas = $query->paginate($this->perPage);

        $estados = Deudacartera::where('cartera_id', $this->cartera->id)
            ->distinct()->orderBy('estado')->pluck('estado')->filter()->values();

        $rangos = Deudacartera::where('cartera_id', $this->cartera->id)
            ->distinct()->orderBy('rango')->pluck('rango')->filter()->values();

        return view('livewire.deudas-cartera-index', [
            'deudas'  => $deudas,
            'estados' => $estados,
            'rangos'  => $rangos,
            'totales' => [
                'registros' => Deudacartera::where('cartera_id', $this->cartera->id)->count(),
                'saldo'     => (float) Deudacartera::where('cartera_id', $this->cartera->id)->sum('saldo'),
                'importe'   => (float) Deudacartera::where('cartera_id', $this->cartera->id)->sum('importe'),
            ],
        ])->extends('adminlte::page');
    }
}