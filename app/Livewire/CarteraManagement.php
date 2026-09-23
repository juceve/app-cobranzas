<?php

namespace App\Livewire;

use App\Models\Cartera;
use App\Models\Company;
use App\Models\Deudacartera;
use App\Models\Historialcartera;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class CarteraManagement extends Component
{
    use WithPagination;
    use WithFileUploads;

    public ?int $editingCarteraId = null;
    public string $companyId = '';
    public ?string $ultActualizacion = null;
    public string $search = '';
    public bool $showCarteraModal = false;

    protected string $paginationTheme = 'bootstrap';

    // ── Upload
    public ?int $uploadCarteraId = null;
    public $excelFile = null;
    public ?string $uploadOriginalName = null;
    public int $progress = 0;
    public bool $uploading = false;
    public bool $showUploadModal = false;
    public bool $reemplazarDeudas = true;

    // ── Historial
    public bool $showHistorialModal = false;
    public ?int $historialCarteraId = null;
    public string $historialEstado = '';

    public function mount(): void
    {
        $this->ensurePermission('carteras.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingHistorialEstado(): void
    {
        $this->resetPage('historialPage');
    }

    public function createCartera(): void
    {
        $this->ensurePermission('carteras.create');
        $this->resetForm();
        $this->showCarteraModal = true;
    }

    public function editCartera(int $carteraId): void
    {
        $this->ensurePermission('carteras.update');

        $cartera = Cartera::findOrFail($carteraId);

        $this->editingCarteraId = $cartera->id;
        $this->companyId = (string) $cartera->company_id;
        $this->ultActualizacion = $cartera->ult_actualizacion
            ? Carbon::parse($cartera->ult_actualizacion)->format('Y-m-d')
            : null;
        $this->showCarteraModal = true;
        $this->resetValidation();
    }

    public function saveCartera(): void
    {
        $this->ensurePermission($this->editingCarteraId === null ? 'carteras.create' : 'carteras.update');

        $validated = $this->validate();
        $attributes = [
            'company_id'        => (int) $validated['companyId'],
            'ult_actualizacion' => $validated['ultActualizacion'] ?: null,
        ];

        if ($this->editingCarteraId === null) {
            Cartera::create($attributes);
            $message = 'La cartera fue creada correctamente.';
        } else {
            Cartera::findOrFail($this->editingCarteraId)->update($attributes);
            $message = 'La cartera fue actualizada correctamente.';
        }

        $this->resetForm();
        $this->dispatch('cartera-notification',
            icon: 'success',
            message: $message,
        );
    }

    public function closeModal(): void
    {
        $this->resetForm();
    }

    // ─────────────────────────────────────────────────────────────
    //  HISTORIAL
    // ─────────────────────────────────────────────────────────────

    public function openHistorialModal(int $carteraId): void
    {
        $this->ensurePermission('carteras.view');

        $this->historialCarteraId = $carteraId;
        $this->historialEstado    = '';
        $this->resetPage('historialPage');
        $this->showHistorialModal = true;
    }

    public function closeHistorialModal(): void
    {
        $this->reset(['showHistorialModal', 'historialCarteraId', 'historialEstado']);
    }

    public function verDeudas(int $carteraId): void
    {
        $this->ensurePermission('carteras.view');

        $this->redirectRoute('carteras.deudas', ['cartera' => $carteraId], navigate: true);
    }

    public function render()
    {
        $carteras = Cartera::query()
            ->with('company')
            ->withCount('deudacarteras')
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->whereHas('company', function (Builder $q): void {
                        $q->where('name', 'ilike', "%{$this->search}%")
                            ->orWhere('nit', 'ilike', "%{$this->search}%");
                    })
                        ->orWhere('ult_actualizacion', 'ilike', "%{$this->search}%");
                });
            })
            ->orderByDesc('ult_actualizacion')
            ->paginate(10);

        $companies = Company::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'nit']);

        // Historial paginado (solo si el modal está abierto)
        $historial = null;
        $historialCartera = null;
        if ($this->showHistorialModal && $this->historialCarteraId) {
            $historialCartera = Cartera::with('company')->find($this->historialCarteraId);

            $historial = Historialcartera::query()
                ->with('user')
                ->where('cartera_id', $this->historialCarteraId)
                ->when($this->historialEstado !== '', fn (Builder $q) =>
                    $q->where('estado', $this->historialEstado)
                )
                ->orderByDesc('fecha')
                ->paginate(10, ['*'], 'historialPage');
        }

        return view('livewire.cartera-management', [
            'carteras'         => $carteras,
            'companies'        => $companies,
            'historial'        => $historial,
            'historialCartera' => $historialCartera,
        ]);
    }

    protected function rules(): array
    {
        return [
            'companyId' => ['required', 'integer', 'exists:companies,id'],
            'ultActualizacion' => ['nullable', 'date'],
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingCarteraId',
            'companyId',
            'ultActualizacion',
            'showCarteraModal',
        ]);
        $this->resetValidation();
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

    // ─────────────────────────────────────────────────────────────
    //  UPLOAD
    // ─────────────────────────────────────────────────────────────

    public function openUploadModal(int $carteraId): void
    {
        $this->ensurePermission('carteras.update');

        $this->reset(['excelFile', 'progress', 'uploading', 'uploadOriginalName']);
        $this->resetValidation();

        $this->uploadCarteraId  = $carteraId;
        $this->reemplazarDeudas = true;
        $this->showUploadModal  = true;
    }

    public function closeUploadModal(): void
    {
        if ($this->uploading) {
            return;
        }

        $this->reset([
            'excelFile', 'progress', 'uploading',
            'uploadOriginalName', 'uploadCarteraId', 'showUploadModal',
        ]);
        $this->resetValidation();
    }

    public function updatedExcelFile(): void
    {
        $this->progress = 0;

        $this->validate([
            'excelFile' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ], [], ['excelFile' => 'archivo Excel']);

        $this->uploadOriginalName = $this->excelFile->getClientOriginalName();
    }

    public function processUpload(): void
    {
        $this->ensurePermission('carteras.update');

        $this->validate([
            'excelFile' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ], [], ['excelFile' => 'archivo Excel']);

        if (! $this->uploadCarteraId) {
            $this->addError('excelFile', 'No se ha seleccionado una cartera válida.');
            return;
        }

        $cartera = Cartera::findOrFail($this->uploadCarteraId);

        $this->uploading = true;
        $this->progress  = 5;
        $this->dispatch('upload-start');

        $storedPath   = null;
        $originalName = null;
        $hashFile     = null;

        try {
            $originalName = $this->uploadOriginalName ?: $this->excelFile->getClientOriginalName();

            $extension = strtolower($this->excelFile->getClientOriginalExtension() ?: 'xlsx');
            $safeBase  = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'cartera';
            $newName   = $safeBase . '_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.' . $extension;

            $this->progress = 15;
            $this->dispatch('upload-progress', progress: $this->progress);

            $storedPath = $this->excelFile->storeAs('excel-carteras', $newName, 'public');

            $this->progress = 30;
            $this->dispatch('upload-progress', progress: $this->progress);

            $absolutePath = Storage::disk('public')->path($storedPath);
            $hashFile = file_exists($absolutePath) ? hash_file('sha256', $absolutePath) : null;

            // ── Calcular cambios ANTES de reemplazar (clave: NumDoc, criterio: Saldo)
            $resumen = $this->calcularCambios($storedPath, $cartera);

            // ── Procesar Excel + historial dentro de una transacción
            $inserted = DB::transaction(function () use ($storedPath, $cartera, $originalName, $hashFile, $resumen) {
                $inserted = $this->processExcel($storedPath, $cartera);

                Historialcartera::create([
                    'cartera_id'   => $cartera->id,
                    'fecha'        => now(),
                    'cambios'      => $resumen['cambios'],
                    'nuevos'       => $resumen['nuevos'],
                    'salidas'      => $resumen['salidas'],
                    'doc_original' => $originalName,
                    'hash'         => $hashFile,
                    'user_id'      => Auth::id(),
                    'estado'       => 'procesado',
                ]);

                $cartera->update(['ult_actualizacion' => now()]);

                return $inserted;
            });

            $this->progress = 100;
            $this->dispatch('upload-progress', progress: 100);
            $this->dispatch('upload-finished');

            $this->dispatch('cartera-notification',
                icon: 'success',
                message: "Se procesaron {$inserted} registros. "
                    . "Cambios: {$resumen['cambios']}, "
                    . "Nuevos: {$resumen['nuevos']}, "
                    . "Salidas: {$resumen['salidas']}.",
            );

            $this->reset(['excelFile', 'uploadCarteraId', 'uploadOriginalName']);
            $this->showUploadModal = false;
        } catch (\Throwable $e) {
            report($e);

            $detalle = sprintf(
                '[%s] %s (línea %d en %s)',
                class_basename($e),
                $e->getMessage(),
                $e->getLine(),
                basename($e->getFile())
            );

            Log::error('Error al procesar cartera', [
                'cartera_id'    => $cartera->id ?? null,
                'stored_path'   => $storedPath,
                'original_name' => $originalName,
                'detalle'       => $detalle,
                'trace'         => collect($e->getTrace())->take(10)->map(fn ($t) =>
                    ($t['file'] ?? '?') . ':' . ($t['line'] ?? '?') . ' → ' . ($t['function'] ?? '?')
                )->all(),
            ]);

            $this->addError('excelFile', 'Error: ' . $detalle);

            $this->dispatch('cartera-notification',
                icon: 'error',
                message: 'Error: ' . $e->getMessage(),
            );

            if (isset($cartera) && $cartera instanceof Cartera) {
                try {
                    Historialcartera::create([
                        'cartera_id'   => $cartera->id,
                        'fecha'        => now(),
                        'cambios'      => 0,
                        'nuevos'       => 0,
                        'salidas'      => 0,
                        'doc_original' => $originalName,
                        'hash'         => $hashFile,
                        'user_id'      => Auth::id(),
                        'estado'       => 'error',
                    ]);
                } catch (\Throwable $inner) {
                    report($inner);
                }
            }
        } finally {
            $this->uploading = false;
            $this->progress  = 0;
        }
    }

    /**
     * Calcula cambios/nuevos/salidas.
     *
     * Reglas de negocio:
     *  - Clave de identificación de deuda: NumDoc (columna B).
     *  - Criterio de cambio: Saldo (columna D) distinto.
     *  - Cuando hay NumDoc duplicados en el Excel, se conserva el ÚLTIMO (el que
     *    "reemplaza la fila").
     *
     * @return array{cambios:int, nuevos:int, salidas:int}
     */
    private function calcularCambios(string $storedPath, Cartera $cartera): array
    {
        try {
            $saldosExcel = $this->leerSaldosDesdeExcel($storedPath); // ['NumDoc' => saldo, ...]
            $docsExcel   = array_keys($saldosExcel);

            // Saldos actuales en BD (último saldo por NumDoc)
            $saldosActuales = Deudacartera::where('cartera_id', $cartera->id)
                ->orderBy('id')
                ->get(['num_doc', 'saldo'])
                ->mapWithKeys(fn ($d) => [(string) $d->num_doc => (float) $d->saldo])
                ->all();

            $docsActualesSet = array_flip(array_keys($saldosActuales));
            $docsExcelSet    = array_flip($docsExcel);

            // Nuevos: NumDoc en Excel que no existen en BD
            $nuevos = count(array_diff_key($docsExcelSet, $docsActualesSet));

            // Salidas: NumDoc en BD que ya no vienen en el Excel
            $salidas = count(array_diff_key($docsActualesSet, $docsExcelSet));

            // Cambios: NumDoc que existen en ambos y cuyo Saldo difiere
            $cambios = 0;
            foreach ($saldosExcel as $numDoc => $saldoNuevo) {
                $key = (string) $numDoc;
                if (isset($saldosActuales[$key])) {
                    if (abs($saldosActuales[$key] - (float) $saldoNuevo) > 0.009) {
                        $cambios++;
                    }
                }
            }

            return [
                'cambios' => $cambios,
                'nuevos'  => $nuevos,
                'salidas' => $salidas,
            ];
        } catch (\Throwable $e) {
            report($e);
            return ['cambios' => 0, 'nuevos' => 0, 'salidas' => 0];
        }
    }

    /**
     * Lee num_doc (B) y saldo (D) del Excel. Si hay NumDoc duplicados,
     * el último reemplaza al anterior.
     *
     * @return array<string, float>
     */
    private function leerSaldosDesdeExcel(string $storedPath): array
    {
        $fullPath = Storage::disk('public')->path($storedPath);

        $reader = IOFactory::createReaderForFile($fullPath);
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new class implements IReadFilter {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return in_array($columnAddress, ['B', 'D'], true);
            }
        });

        $spreadsheet = $reader->load($fullPath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, true);

        array_shift($rows); // encabezado

        $result = [];
        foreach ($rows as $r) {
            $doc   = $this->toStr($r['B'] ?? null);
            $saldo = $this->toDecimal($r['D'] ?? null);

            if ($doc !== null) {
                $result[$doc] = $saldo; // el último gana
            }
        }

        return $result;
    }

    private function processExcel(string $storedPath, Cartera $cartera): int
    {
        $fullPath = Storage::disk('public')->path($storedPath);

        $reader = IOFactory::createReaderForFile($fullPath);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $reader->setReadFilter(new class implements IReadFilter {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return in_array($columnAddress, range('A', 'Y'), true);
            }
        });

        $spreadsheet = $reader->load($fullPath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, true);

        if (empty($rows)) {
            throw new \RuntimeException('El archivo Excel está vacío.');
        }

        array_shift($rows);

        if ($this->reemplazarDeudas) {
            Deudacartera::where('cartera_id', $cartera->id)->delete();
        }

        $total    = count($rows);
        $i        = 0;
        $inserted = 0;

        foreach (array_chunk($rows, 500) as $chunk) {
            $batch = [];

            foreach ($chunk as $r) {
                if ($this->isEmptyRow($r)) {
                    continue;
                }

                $batch[] = [
                    'cartera_id'               => $cartera->id,
                    'fecha'                    => $this->toDate($r['A'] ?? null),
                    'num_doc'                  => $this->toStr($r['B'] ?? null),
                    'importe'                  => $this->toDecimal($r['C'] ?? null),
                    'saldo'                    => $this->toDecimal($r['D'] ?? null),
                    'vence'                    => $this->toDate($r['E'] ?? null),
                    'antiguedad'               => $this->toInt($r['F'] ?? null),
                    'anticuacion'              => $this->toStr($r['G'] ?? null),
                    'rango'                    => $this->toStr($r['H'] ?? null),
                    'cliente'                  => $this->toStr($r['I'] ?? null),
                    'cli_lugar'                => $this->toStr($r['J'] ?? null),
                    'ent_nombre_jefe_vendedor' => $this->toStr($r['K'] ?? null),
                    'ent_nombre_supervisor'    => $this->toStr($r['L'] ?? null),
                    'ent_nombre_vendedor'      => $this->toStr($r['M'] ?? null),
                    'plazo'                    => $this->toInt($r['N'] ?? null),
                    'fecha_ultimo_pago'        => $this->toDate($r['O'] ?? null),
                    'ciu_nombre'               => $this->toStr($r['P'] ?? null),
                    'codigo_cliente'           => $this->toStr($r['Q'] ?? null),
                    'limite_credito'           => $this->toDecimal($r['R'] ?? null),
                    'rut_id'                   => $this->toStr($r['S'] ?? null),
                    'coordenada_x'             => $this->toDecimal($r['T'] ?? null),
                    'coordenada_y'             => $this->toDecimal($r['U'] ?? null),
                    'telefono'                 => $this->toStr($r['V'] ?? null),
                    'estado'                   => $this->toStr($r['W'] ?? null),
                    'direccion'                => $this->toStr($r['X'] ?? null),
                    'fecha_carga'              => $this->toDate($r['Y'] ?? null),
                    'created_at'               => now(),
                    'updated_at'               => now(),
                ];
            }

            if (! empty($batch)) {
                Deudacartera::insert($batch);
                $inserted += count($batch);
            }

            $i += count($chunk);
            $this->progress = 30 + (int) floor(($i / max($total, 1)) * 65);
            $this->dispatch('upload-progress', progress: $this->progress);
        }

        return $inserted;
    }

    // ─────────────────────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────────────────────

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $v) {
            if ($v !== null && $v !== '') {
                return false;
            }
        }
        return true;
    }

    private function toDate($value): ?string
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }

        try {
            if (is_numeric($value) && $value > 1000 && $value < 100000) {
                $dt = ExcelDate::excelToDateTimeObject((float) $value);
                return Carbon::instance($dt)->toDateString();
            }

            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value)->toDateString();
            }

            $str = trim((string) $value);
            if ($str === '' || Str::startsWith($str, '=')) {
                return null;
            }

            return Carbon::parse($str)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function toDecimal($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $str = trim((string) $value);
        if ($str === '' || Str::startsWith($str, '=')) {
            return 0.0;
        }

        $str = str_replace([' ', "\xC2\xA0"], '', $str);

        if (str_contains($str, ',') && str_contains($str, '.')) {
            $lastComma = strrpos($str, ',');
            $lastDot   = strrpos($str, '.');
            if ($lastComma > $lastDot) {
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            } else {
                $str = str_replace(',', '', $str);
            }
        } elseif (str_contains($str, ',')) {
            $str = str_replace(',', '.', $str);
        }

        return is_numeric($str) ? (float) $str : 0.0;
    }

    private function toInt($value): ?int
    {
        if ($value === null || $value === '') return null;
        if (is_int($value)) return $value;
        if (is_float($value)) return (int) $value;

        $str = trim((string) $value);
        if ($str === '' || Str::startsWith($str, '=')) return null;
        return is_numeric($str) ? (int) $str : null;
    }

    private function toStr($value): ?string
    {
        if ($value === null) return null;
        $str = trim((string) $value);
        if ($str === '' || Str::startsWith($str, '=')) return null;
        return $str;
    }
}