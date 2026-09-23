<?php

namespace App\Livewire\Lotes;

use App\Models\Lote;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url] public string $search = '';
    #[Url] public string $estado = '';

    public function render()
    {
        $query = Lote::with(['cobrador', 'creador', 'cartera.company'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('codigo', 'like', "%{$this->search}%")
                        ->orWhere('nombre', 'like', "%{$this->search}%");
                });
            })
            ->when($this->estado, fn($q) => $q->where('estado', $this->estado));

        if (auth()->user()->hasRole('Cobrador')) {
            $query->where('user_id', auth()->id());
        }

        return view('livewire.lotes.index', [
            'lotes' => $query->latest()->paginate(15),
        ])->layout('adminlte::page', [
            'title' => 'Lotes de cobranza',
        ]);
    }
}