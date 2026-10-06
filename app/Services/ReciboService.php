<?php

namespace App\Services;

use App\Models\Cobro;
use App\Models\Gestion;
use App\Models\Lote;
use App\Models\Recibo;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReciboService
{
    /**
     * Emite el recibo de un conjunto de cobros hechos en una misma gestión.
     *
     * @param  Collection<int, Cobro>  $cobros
     */
    public static function emitir(Lote $lote, Gestion $gestion, Collection $cobros, string $cliente): Recibo
    {
        $primero = $cobros->first();

        $recibo = Recibo::create([
            'token' => Str::random(40),
            'company_id' => $lote->company_id,
            'lote_id' => $lote->id,
            'gestion_id' => $gestion->id,
            'user_id' => $gestion->user_id,
            'codigo_cliente' => $gestion->codigo_cliente,
            'cliente' => $cliente,
            'empresa' => $lote->company?->name ?? $lote->cartera?->company?->name ?? '',
            'cobrador' => $gestion->usuario?->name ?? '',
            'forma_pago' => $primero->forma_pago,
            'referencia' => $primero->referencia,
            'monto_total' => $cobros->sum('monto'),
            'fecha_cobro' => $primero->fecha_cobro,
            'emitido_en' => now(),
        ]);

        $recibo->update(['numero' => sprintf('REC-%d-%06d', now()->year, $recibo->id)]);
        Cobro::whereKey($cobros->pluck('id'))->update(['recibo_id' => $recibo->id]);

        return $recibo;
    }

    public static function qrSvg(string $contenido, int $tamano = 160): string
    {
        $escritor = new Writer(new ImageRenderer(new RendererStyle($tamano, 1), new SvgImageBackEnd));

        return $escritor->writeString($contenido);
    }
}
