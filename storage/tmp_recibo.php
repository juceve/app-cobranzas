<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$accion = $argv[1] ?? 'crear';

if ($accion === 'crear') {
    $lote = App\Models\Lote::findOrFail(1);
    $fila = DB::table('lote_deuda')->where('lote_id', 1)->first();
    $deuda = App\Models\Deudacartera::findOrFail($fila->deudacartera_id);
    $admin = App\Models\User::where('email', 'admin@cobranzas')->firstOrFail();

    $gestion = App\Models\Gestion::create(['lote_id' => 1, 'codigo_cliente' => $fila->codigo_cliente, 'user_id' => $admin->id, 'tipo' => 'visita', 'resultado' => 'pago', 'observacion' => 'TEMP_VISUAL']);
    $cobro = App\Models\Cobro::create(['lote_id' => 1, 'deudacartera_id' => $deuda->id, 'gestion_id' => $gestion->id, 'user_id' => $admin->id, 'monto' => 150.5, 'fecha_cobro' => today(), 'forma_pago' => 'transferencia', 'referencia' => 'TRX-98765']);
    $recibo = App\Services\ReciboService::emitir($lote, $gestion, collect([$cobro]), $deuda->cliente);
    echo $recibo->token."\n";
} else {
    $gestiones = App\Models\Gestion::where('observacion', 'TEMP_VISUAL')->get();
    foreach ($gestiones as $g) {
        App\Models\Cobro::where('gestion_id', $g->id)->delete();
        App\Models\Recibo::where('gestion_id', $g->id)->delete();
        $g->delete();
    }
    echo "limpio\n";
}
