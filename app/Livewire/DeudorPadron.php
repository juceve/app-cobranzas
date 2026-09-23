<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Views\DeudorPadron as DeudorPadronView;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeudorPadron extends Component
{
    use WithPagination;

    public string $search = '';

    public string $ciudad = '';

    public bool $soloConCoordenadas = false;

    protected string $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->ensurePermission('deudores.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCiudad(): void
    {
        $this->resetPage();
    }

    public function updatingSoloConCoordenadas(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'ciudad', 'soloConCoordenadas']);
        $this->resetPage();
    }

    /**
     * Construye la query base con los filtros aplicados.
     */
    private function baseQuery(): Builder
    {
        return DeudorPadronView::query()
            ->buscar($this->search ?: null)
            ->deCiudad($this->ciudad ?: null)
            ->when($this->soloConCoordenadas, fn (Builder $q) => $q->conCoordenadas());
    }

    /**
     * Exporta el listado filtrado completo a CSV (streaming).
     */
    public function exportCsv(): StreamedResponse
    {
        $this->ensurePermission('deudores.export');

        $filename = 'padron_deudores_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 para que Excel respete acentos
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'RUT', 'Código Cliente', 'Cliente',
                'Ciudad', 'Lugar', 'Dirección',
                'Coordenada X', 'Coordenada Y', 'Teléfono',
                'Jefe Vendedor', 'Supervisor', 'Vendedor',
                'Primera aparición', 'Última aparición', 'Carteras distintas',
            ]);

            $this->baseQuery()
                ->orderBy('ciu_nombre')
                ->orderBy('codigo_cliente')
                ->orderBy('cliente')
                ->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $d) {
                        fputcsv($handle, [
                            $d->rut_id,
                            $d->codigo_cliente,
                            $d->cliente,
                            $d->ciu_nombre,
                            $d->cli_lugar,
                            $d->direccion,
                            $d->coordenada_x,
                            $d->coordenada_y,
                            $d->telefono,
                            $d->ent_nombre_jefe_vendedor,
                            $d->ent_nombre_supervisor,
                            $d->ent_nombre_vendedor,
                            optional($d->primera_aparicion)->format('d/m/Y'),
                            optional($d->ultima_aparicion)->format('d/m/Y'),
                            $d->carteras_distintas,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render()
    {
        $deudores = $this->baseQuery()
            ->orderBy('ciu_nombre', 'asc')
            ->orderBy('codigo_cliente', 'asc')
            ->orderBy('cliente', 'asc')
            ->paginate(25);

        $ciudades = DeudorPadronView::query()
            ->select('ciu_nombre')
            ->whereNotNull('ciu_nombre')
            ->where('ciu_nombre', '<>', '')
            ->distinct()
            ->orderBy('ciu_nombre')
            ->pluck('ciu_nombre');

        $totalConCoordenadas = DeudorPadronView::query()->conCoordenadas()->count();
        $totalGeneral        = DeudorPadronView::query()->count();

        return view('livewire.deudor-padron', [
            'deudores'             => $deudores,
            'ciudades'             => $ciudades,
            'totalConCoordenadas'  => $totalConCoordenadas,
            'totalGeneral'         => $totalGeneral,
        ]);
    }

    private function ensurePermission(string $permission): void
    {
        abort_unless($this->currentUser()->can($permission), 403);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}