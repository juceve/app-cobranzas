<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Recibo {{ $recibo->numero }}</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f1f3f5; color: #212529; }
        .wrap { max-width: 480px; margin: 0 auto; padding: 16px; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.08); overflow: hidden; }
        .head { background: #198754; color: #fff; padding: 24px 20px; text-align: center; }
        .head .icon { font-size: 40px; line-height: 1; }
        .head h1 { margin: 8px 0 2px; font-size: 20px; }
        .head .num { opacity: .9; font-size: 14px; }
        .monto { text-align: center; padding: 20px 16px 8px; }
        .monto small { display: block; color: #6c757d; text-transform: uppercase; letter-spacing: .05em; font-size: 12px; }
        .monto strong { font-size: 34px; }
        .estado { text-align: center; padding-bottom: 12px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .badge.ok { background: #d1e7dd; color: #0f5132; }
        .badge.pend { background: #fff3cd; color: #664d03; }
        dl { margin: 0; padding: 8px 20px 12px; }
        .fila { display: flex; justify-content: space-between; gap: 16px; padding: 10px 0; border-bottom: 1px solid #eee; font-size: 15px; }
        .fila dt { color: #6c757d; }
        .fila dd { margin: 0; text-align: right; font-weight: 600; word-break: break-word; }
        h2 { font-size: 13px; text-transform: uppercase; letter-spacing: .05em; color: #6c757d; margin: 0; padding: 12px 20px 4px; }
        table { width: calc(100% - 40px); margin: 0 20px 16px; border-collapse: collapse; font-size: 14px; }
        td { padding: 8px 0; border-bottom: 1px solid #eee; }
        td:last-child { text-align: right; font-weight: 600; }
        tfoot td { border: 0; font-size: 16px; }
        .nota { padding: 0 20px 20px; font-size: 12px; color: #6c757d; text-align: center; }
        @media (prefers-color-scheme: dark) {
            body { background: #121212; color: #e9ecef; }
            .card { background: #1e1e1e; box-shadow: none; }
            .fila, td { border-color: #333; }
            .badge.ok { background: #14532d; color: #d1e7dd; }
            .badge.pend { background: #664d03; color: #fff3cd; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="head">
                <div class="icon">✔</div>
                <h1>Recibo de cobro</h1>
                <div class="num">{{ $recibo->numero }}</div>
            </div>

            <div class="monto">
                <small>Monto recibido</small>
                <strong>{{ \App\Models\Recibo::MONEDA }} {{ number_format((float) $recibo->monto_total, 2) }}</strong>
            </div>
            <div class="estado">
                @if($recibo->estaValidado())
                    <span class="badge ok">Pago validado</span>
                @else
                    <span class="badge pend">Pago registrado, pendiente de validación</span>
                @endif
            </div>

            <dl>
                <div class="fila"><dt>Empresa</dt><dd>{{ $recibo->empresa }}</dd></div>
                <div class="fila"><dt>Cliente</dt><dd>{{ $recibo->cliente }}</dd></div>
                <div class="fila"><dt>Código</dt><dd>{{ $recibo->codigo_cliente }}</dd></div>
                <div class="fila"><dt>Fecha</dt><dd>{{ $recibo->fecha_cobro->format('d/m/Y') }}</dd></div>
                <div class="fila"><dt>Forma de pago</dt><dd>{{ $recibo->etiquetaFormaPago() }}</dd></div>
                @if($recibo->referencia)
                    <div class="fila"><dt>Referencia</dt><dd>{{ $recibo->referencia }}</dd></div>
                @endif
                <div class="fila"><dt>Recibido por</dt><dd>{{ $recibo->cobrador }}</dd></div>
            </dl>

            <h2>Detalle</h2>
            <table>
                <tbody>
                    @foreach($recibo->cobros as $cobro)
                        <tr><td>Doc. {{ $cobro->deuda?->num_doc ?? '—' }}</td><td>{{ number_format((float) $cobro->monto, 2) }}</td></tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><td><strong>Total</strong></td><td>{{ \App\Models\Recibo::MONEDA }} {{ number_format((float) $recibo->monto_total, 2) }}</td></tr>
                </tfoot>
            </table>

            <p class="nota">Comprobante emitido el {{ $recibo->emitido_en->format('d/m/Y H:i') }}.<br>Guarda este enlace como constancia de tu pago.</p>
        </div>
    </div>
</body>
</html>
