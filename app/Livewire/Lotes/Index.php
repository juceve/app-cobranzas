<?php

namespace App\Livewire\Lotes;

use App\Models\Cartera;
use App\Models\Lote;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    #[Url]
    public string $estado = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('lotes.view'), 403);
    }

    public function render()
    {
        $query = Lote::with(['cobrador', 'creador', 'cartera.company'])
            ->deEmpresaActual()
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('codigo', 'like', "%{$this->search}%")
                        ->orWhere('nombre', 'like', "%{$this->search}%");
                });
            })
            ->when($this->estado, fn ($q) => $q->where('estado', $this->estado));

        if (! auth()->user()->can('lotes.view_all')) {
            $query->where('user_id', auth()->id());
        }

        return view('livewire.lotes.index', [
            'lotes' => $query->latest()->paginate(15),
            'carteras' => auth()->user()->can('lotes.create')
                ? Cartera::deEmpresaActual()->with('company')->orderBy('id')->get()
                : collect(),
        ])->extends('home')->section('content_body');
    }
}
