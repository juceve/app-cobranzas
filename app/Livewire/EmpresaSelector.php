<?php

namespace App\Livewire;

use App\Models\Company;
use App\Support\EmpresaActual;
use Illuminate\Support\Str;
use Livewire\Component;

class EmpresaSelector extends Component
{
    public string $empresaId = '';

    public function mount(): void
    {
        $this->empresaId = (string) (EmpresaActual::id() ?? '');
    }

    public function updatedEmpresaId(): void
    {
        EmpresaActual::set($this->empresaId === '' ? null : (int) $this->empresaId);

        $anterior = url()->previous();
        $esDetalleDeCartera = Str::is('carteras/*/*', trim(parse_url($anterior, PHP_URL_PATH) ?: '', '/'));

        $this->redirect($esDetalleDeCartera ? route('carteras') : $anterior);
    }

    public function render()
    {
        return view('livewire.empresa-selector', [
            'companies' => Company::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
