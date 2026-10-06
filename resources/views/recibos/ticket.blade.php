<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Ticket {{ $recibo->numero }}</title>
    <style>
        @page { size: 58mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e9ecef; font-family: "Courier New", Courier, monospace; color: #000; }
        .barra { display: flex; gap: 8px; justify-content: center; padding: 12px; background: #fff; border-bottom: 1px solid #ccc; font-family: system-ui, sans-serif; }
        .barra button, .barra a { padding: 12px 20px; font-size: 16px; border-radius: 8px; border: 1px solid #198754; background: #198754; color: #fff; text-decoration: none; cursor: pointer; }
        .barra a { background: #fff; color: #198754; }
        .ticket { width: 48mm; margin: 12px auto; padding: 2mm 0; background: #fff; font-size: 11px; line-height: 1.35; }
        .centro { text-align: center; }
        .grande { font-size: 13px; font-weight: bold; }
        .linea { border-top: 1px dashed #000; margin: 6px 0; }
        .fila { display: flex; justify-content: space-between; gap: 6px; }
        .fila span:last-child { text-align: right; }
        .total { font-size: 14px; font-weight: bold; }
        .qr { width: 32mm; height: 32mm; margin: 4px auto; }
        .qr svg { width: 100%; height: 100%; display: block; }
        .corto { word-break: break-all; font-size: 9px; }
        @media print {
            body { background: #fff; }
            .barra { display: none; }
            .ticket { margin: 0 auto; }
        }
    </style>
</head>
<body>
    <div class="barra">
        <button type="button" onclick="window.print()">Imprimir</button>
        <a href="{{ $recibo->urlPublica() }}">Ver recibo</a>
    </div>

    <div class="ticket">
        <div class="centro grande">{{ $recibo->empresa }}</div>
        <div class="centro">RECIBO DE COBRO</div>
        <div class="centro grande">{{ $recibo->numero }}</div>
        <div class="linea"></div>

        <div>Fecha: {{ $recibo->fecha_cobro->format('d/m/Y') }} {{ $recibo->emitido_en->format('H:i') }}</div>
        <div>Cliente: {{ $recibo->cliente }}</div>
        <div>Cod: {{ $recibo->codigo_cliente }}</div>
        <div class="linea"></div>

        @foreach($recibo->cobros as $cobro)
            <div class="fila"><span>Doc {{ $cobro->deuda?->num_doc ?? '-' }}</span><span>{{ number_format((float) $cobro->monto, 2) }}</span></div>
        @endforeach
        <div class="linea"></div>

        <div class="fila total"><span>TOTAL {{ \App\Models\Recibo::MONEDA }}</span><span>{{ number_format((float) $recibo->monto_total, 2) }}</span></div>
        <div>Pago: {{ $recibo->etiquetaFormaPago() }}</div>
        @if($recibo->referencia)<div>Ref: {{ $recibo->referencia }}</div>@endif
        <div>Recibido por: {{ $recibo->cobrador }}</div>
        <div class="linea"></div>

        <div class="centro">Escanee para ver el detalle</div>
        <div class="qr">{!! $qr !!}</div>
        <div class="centro corto">{{ $recibo->urlPublica() }}</div>
        <div class="centro" style="margin-top:6px;">Gracias por su pago</div>
    </div>

    @if(request()->boolean('imprimir'))
        <script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
    @endif
</body>
</html>
