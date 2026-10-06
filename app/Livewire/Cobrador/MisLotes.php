<?php

namespace App\Livewire\Cobrador;

use App\Models\Cobro;
use App\Models\Gestion;
use App\Models\Lote;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MisLotes extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->can('cobrador.access'), 403);
    }

    public function render()
    {
        $usuarioId = auth()->id();

        $lotes = Lote::query()
            ->deEmpresaActual()
            ->with('cartera.company')
            ->where('user_id', $usuarioId)
            ->whereIn('estado', Lote::ESTADOS_ABIERTOS)
            ->orderByRaw('fecha_limite IS NULL, fecha_limite')
            ->get();

        $ids = $lotes->pluck('id');

        $gestionados = DB::table('lote_deuda')
            ->whereIn('lote_id', $ids)
            ->where('estado_gestion', '<>', 'pendiente')
            ->selectRaw('lote_id, COUNT(DISTINCT codigo_cliente) AS n')
            ->groupBy('lote_id')
            ->pluck('n', 'lote_id');

        $recuperado = DB::table('lote_deuda')
            ->whereIn('lote_id', $ids)
            ->selectRaw('lote_id, SUM(monto_recuperado) AS total')
            ->groupBy('lote_id')
            ->pluck('total', 'lote_id');

        return view('livewire.cobrador.mis-lotes', [
            'lotes' => $lotes,
            'gestionados' => $gestionados,
            'recuperado' => $recuperado,
            'gestionesHoy' => Gestion::where('user_id', $usuarioId)->whereDate('gestionado_en', today())->count(),
            'cobradoHoy' => (float) Cobro::where('user_id', $usuarioId)->whereDate('fecha_cobro', today())->sum('monto'),
        ])->extends('layouts.cobrador', ['titulo' => 'Mis lotes', 'subtitulo' => auth()->user()->name])
            ->section('content_body');
    }
}
