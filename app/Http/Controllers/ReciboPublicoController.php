<?php

namespace App\Http\Controllers;

use App\Models\Recibo;
use App\Services\ReciboService;
use Illuminate\Contracts\View\View;

class ReciboPublicoController extends Controller
{
    public function show(string $token): View
    {
        $recibo = $this->buscar($token);

        return view('recibos.publico', ['recibo' => $recibo]);
    }

    public function ticket(string $token): View
    {
        $recibo = $this->buscar($token);

        return view('recibos.ticket', [
            'recibo' => $recibo,
            'qr' => ReciboService::qrSvg($recibo->urlPublica(), 150),
        ]);
    }

    private function buscar(string $token): Recibo
    {
        return Recibo::query()
            ->with(['cobros.deuda'])
            ->where('token', $token)
            ->firstOrFail();
    }
}
