<?php

namespace App\Livewire;

use App\Models\Cartera;
use App\Models\Company;
use App\Models\Deudacartera;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class DeudaAnalisis extends Component
{
    use WithPagination;

    // ─── Filtros globales ───
    public string $carteraId = '';       // '' = todas
    public string $rango = '';           // VENCIDAS / VIGENTES
    public string $ciudad = '';
    public string $jefeVendedor = '';
    public string $cobrador = '';
    public string $busqueda = '';
    public bool $soloCriticas = false;   // antigüedad > 90 y saldo > 500

    protected string $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->ensurePermission('analisis.view');
    }

    public function updating($name): void
    {
        // Cualquier cambio de filtro reinicia paginación
        if (in_array($name, [
            'carteraId',
            'rango',
            'ciudad',
            'jefeVendedor',
            'cobrador',
            'busqueda',
            'soloCriticas',
        ], true)) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['carteraId', 'rango', 'ciudad', 'jefeVendedor', 'cobrador', 'busqueda', 'soloCriticas']);
        $this->resetPage();
    }

    /**
     * Filtro base: último corte por cartera + saldo > 0 + no ausente.
     * Si tu tabla no acumula históricos, quita el whereIn de último corte.
     */
    private function baseQuery(): Builder
    {
        $ultimasFechas = Deudacartera::query()
            ->select('cartera_id', DB::raw('MAX(fecha_carga) as max_fecha'))
            ->groupBy('cartera_id');

        return Deudacartera::query()
            ->joinSub($ultimasFechas, 'ult', function ($join) {
                $join->on('deudacarteras.cartera_id', '=', 'ult.cartera_id')
                    ->on('deudacarteras.fecha_carga', '=', 'ult.max_fecha');
            })
            // ⚠️ Sin ->select(...) aquí. Cada consulta define el suyo.
            ->where('deudacarteras.saldo', '>', 0)
            ->where('deudacarteras.ausente', false)
            ->when($this->carteraId !== '', fn($q) => $q->where('deudacarteras.cartera_id', $this->carteraId))
            ->when($this->rango !== '', fn($q) => $q->where('deudacarteras.rango', $this->rango))
            ->when($this->ciudad !== '', fn($q) => $q->where('deudacarteras.ciu_nombre', $this->ciudad))
            ->when($this->jefeVendedor !== '', fn($q) => $q->where('deudacarteras.ent_nombre_jefe_vendedor', $this->jefeVendedor))
            ->when($this->cobrador !== '', fn($q) => $q->where('deudacarteras.ent_nombre_vendedor', $this->cobrador))
            ->when($this->busqueda !== '', function ($q) {
                $q->where(function ($q) {
                    $q->where('deudacarteras.cliente', 'ilike', "%{$this->busqueda}%")
                        ->orWhere('deudacarteras.num_doc', 'ilike', "%{$this->busqueda}%")
                        ->orWhere('deudacarteras.codigo_cliente', 'ilike', "%{$this->busqueda}%")
                        ->orWhere('deudacarteras.rut_id', 'ilike', "%{$this->busqueda}%");
                });
            })
            ->when($this->soloCriticas, fn($q) => $q->where('deudacarteras.antiguedad', '>', 90)->where('deudacarteras.saldo', '>', 500));
    }

    /**
     * KPIs generales del filtro actual.
     */
    private function kpis(): array
    {
        return $this->baseQuery()
            ->selectRaw("
            COUNT(*)                                 AS total_deudas,
            COALESCE(SUM(importe), 0)                AS total_importe,
            COALESCE(SUM(saldo), 0)                  AS total_saldo,
            COALESCE(SUM(CASE WHEN rango = 'VENCIDAS' THEN saldo ELSE 0 END), 0) AS saldo_vencido,
            COALESCE(SUM(CASE WHEN rango = 'VIGENTES' THEN saldo ELSE 0 END), 0) AS saldo_vigente,
            COALESCE(AVG(antiguedad), 0)             AS antiguedad_promedio,
            COALESCE(MAX(antiguedad), 0)             AS antiguedad_max
        ")
            ->first()
            ->toArray();
    }

    /**
     * Ranking por jefe de ventas.
     */
   private function porJefeVendedor()
{
    return $this->baseQuery()
        ->selectRaw("
            ent_nombre_jefe_vendedor AS jefe,
            COUNT(*)                                        AS total_deudas,
            COALESCE(SUM(saldo), 0)                         AS saldo_total,
            COALESCE(SUM(CASE WHEN rango='VENCIDAS' THEN saldo ELSE 0 END), 0) AS saldo_vencido,
            COALESCE(AVG(antiguedad), 0)                    AS antiguedad_promedio
        ")
        ->whereNotNull('ent_nombre_jefe_vendedor')
        ->groupBy('ent_nombre_jefe_vendedor')
        ->orderByDesc('saldo_vencido')
        ->limit(10)
        ->get();
}

    /**
     * Ranking por cobrador/vendedor.
     */
  private function porVendedor()
{
    return $this->baseQuery()
        ->selectRaw("
            ent_nombre_vendedor AS vendedor,
            COUNT(*)                                        AS total_deudas,
            COALESCE(SUM(saldo), 0)                         AS saldo_total,
            COALESCE(SUM(CASE WHEN rango='VENCIDAS' THEN saldo ELSE 0 END), 0) AS saldo_vencido
        ")
        ->whereNotNull('ent_nombre_vendedor')
        ->groupBy('ent_nombre_vendedor')
        ->orderByDesc('saldo_vencido')
        ->limit(10)
        ->get();
}

    /**
     * Distribución por antigüedad (buckets).
     */
   private function porAntiguedad()
{
    $sub = $this->baseQuery()->selectRaw("
        CASE
            WHEN antiguedad <= 30  THEN '1-30'
            WHEN antiguedad <= 60  THEN '31-60'
            WHEN antiguedad <= 90  THEN '61-90'
            WHEN antiguedad <= 120 THEN '91-120'
            WHEN antiguedad <= 180 THEN '121-180'
            WHEN antiguedad <= 250 THEN '181-250'
            ELSE '251+'
        END AS bucket,
        saldo
    ");

    return DB::query()
        ->fromSub($sub, 'd')
        ->selectRaw("
            bucket,
            COUNT(*)          AS total,
            COALESCE(SUM(saldo), 0) AS saldo
        ")
        ->groupBy('bucket')
        ->orderByRaw("
            CASE bucket
                WHEN '1-30' THEN 1
                WHEN '31-60' THEN 2
                WHEN '61-90' THEN 3
                WHEN '91-120' THEN 4
                WHEN '121-180' THEN 5
                WHEN '181-250' THEN 6
                ELSE 7
            END
        ")
        ->get();
}
    /**
     * Listado priorizado de deudas para gestionar.
     * Calcula un "score de prioridad" en SQL sin tabla extra.
     */
   private function deudasPriorizadas()
{
    return $this->baseQuery()
        ->select('deudacarteras.*')
        ->selectRaw("
            (
                CASE
                    WHEN antiguedad > 180 THEN 50
                    WHEN antiguedad > 120 THEN 40
                    WHEN antiguedad > 90  THEN 30
                    WHEN antiguedad > 60  THEN 20
                    WHEN antiguedad > 30  THEN 10
                    ELSE 0
                END
                +
                CASE
                    WHEN saldo > 10000 THEN 30
                    WHEN saldo > 5000  THEN 20
                    WHEN saldo > 1000  THEN 10
                    ELSE 0
                END
                +
                CASE
                    WHEN rango = 'VENCIDAS' THEN 20
                    ELSE 0
                END
            ) AS prioridad_score
        ")
        ->orderByDesc('prioridad_score')
        ->orderByDesc('saldo')
        ->paginate(25);
}

    public function render()
    {
        // Filtros disponibles (para los <select>)
        $carteras   = Cartera::with('company')->orderBy('id')->get();
        $ciudades   = Deudacartera::whereNotNull('ciu_nombre')->distinct()->orderBy('ciu_nombre')->pluck('ciu_nombre');
        $jefes      = Deudacartera::whereNotNull('ent_nombre_jefe_vendedor')->distinct()->orderBy('ent_nombre_jefe_vendedor')->pluck('ent_nombre_jefe_vendedor');
        $vendedores = Deudacartera::whereNotNull('ent_nombre_vendedor')->distinct()->orderBy('ent_nombre_vendedor')->pluck('ent_nombre_vendedor');

        return view('livewire.deuda-analisis', [
            'kpis'             => $this->kpis(),
            'porJefe'          => $this->porJefeVendedor(),
            'porVendedor'      => $this->porVendedor(),
            'porAntiguedad'    => $this->porAntiguedad(),
            'deudas'           => $this->deudasPriorizadas(),
            'carteras'         => $carteras,
            'ciudades'         => $ciudades,
            'jefes'            => $jefes,
            'vendedores'       => $vendedores,
        ]);
    }

    private function ensurePermission(string $permission): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->can($permission), 403);
    }
}
