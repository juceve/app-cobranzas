<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Ticket {{ $recibo->numero }}</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #e9ecef;
            font-family: "Courier New", Courier, monospace;
            color: #000;
        }

        .barra {
            display: flex;
            gap: 8px;
            justify-content: center;
            padding: 12px;
            background: #fff;
            border-bottom: 1px solid #ccc;
            font-family: system-ui, sans-serif;
        }

        .barra button,
        .barra a {
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 8px;
            border: 1px solid #198754;
            background: #198754;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
        }

        .barra a {
            background: #fff;
            color: #198754;
        }

        .ticket {
            width: 48mm;
            margin: 12px auto;
            padding: 2mm 0;
            background: #fff;
            font-size: 11px;
            line-height: 1.35;
        }

        .centro {
            text-align: center;
        }

        .grande {
            font-size: 13px;
            font-weight: bold;
        }

        .linea {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .fila {
            display: flex;
            justify-content: space-between;
            gap: 6px;
        }

        .fila span:last-child {
            text-align: right;
        }

        .total {
            font-size: 14px;
            font-weight: bold;
        }

        .qr {
            width: 32mm;
            height: 32mm;
            margin: 4px auto;
        }

        .qr svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .corto {
            word-break: break-all;
            font-size: 9px;
        }

        @media print {
            body {
                background: #fff;
            }

            .barra {
                display: none;
            }

            .ticket {
                margin: 0 auto;
            }
        }

        @media print {
            @page {
                size: 58mm auto;
                margin: 0;
            }

            html,
            body {
                width: 58mm;
                margin: 0;
                padding: 0;
                background: #fff;
            }

            .barra {
                display: none !important;
            }

            .ticket {
                width: 48mm;
                margin: 0 auto;
                padding: 2mm 0;
                background: #fff;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="barra">
        {{-- <button type="button" onclick="window.print()">Imprimir</button> --}}
        <button type="button" id="btnImprimir">Imprimir</button>
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
        <div class="fila"><span>Doc {{ $cobro->deuda?->num_doc ?? '-' }}</span><span>{{ number_format((float)
                $cobro->monto, 2) }}</span></div>
        @endforeach
        <div class="linea"></div>

        <div class="fila total"><span>TOTAL {{ \App\Models\Recibo::MONEDA }}</span><span>{{ number_format((float)
                $recibo->monto_total, 2) }}</span></div>
        <div>Pago: {{ $recibo->etiquetaFormaPago() }}</div>
        @if($recibo->referencia)<div>Ref: {{ $recibo->referencia }}</div>@endif
        <div>Recibido por: {{ $recibo->cobrador }}</div>
        <div class="linea"></div>

        <div class="centro">Escanee para ver el detalle</div>
        <div class="qr">{!! $qr !!}</div>
        <div class="centro corto">{{ $recibo->urlPublica() }}</div>
        <div class="centro" style="margin-top:6px;">Gracias por su pago</div>
    </div>


    {{-- IMPRESION EN TERMICA --}}
    <script>
        const datosRecibo = {{ \Illuminate\Support\Js::from([
        'empresa' => $recibo->empresa,
        'numero' => $recibo->numero,
        'fecha' => $recibo->fecha_cobro->format('d/m/Y'),
        'hora' => $recibo->emitido_en->format('H:i'),
        'cliente' => $recibo->cliente,
        'codigo' => $recibo->codigo_cliente,
        'cobros' => $recibo->cobros->map(fn ($cobro) => [
            'documento' => $cobro->deuda?->num_doc ?? '-',
            'monto' => number_format((float) $cobro->monto, 2, '.', ''),
        ])->values()->all(),
        'total' => number_format((float) $recibo->monto_total, 2, '.', ''),
        'moneda' => \App\Models\Recibo::MONEDA,
        'formaPago' => $recibo->etiquetaFormaPago(),
        'referencia' => $recibo->referencia,
        'cobrador' => $recibo->cobrador,
        'url' => $recibo->urlPublica(),
    ]) }};

    // ESC/POS: comandos básicos para impresora térmica.
    const ESC = 0x1B;
    const GS  = 0x1D;

    const encoder = new TextEncoder();
    const bytes = [];

    function comando(...valores) {
        bytes.push(...valores);
    }

    // Elimina acentos para evitar problemas con páginas de códigos.
    function texto(valor = '') {
        return String(valor)
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^\x20-\x7E]/g, '?');
    }

    function escribirLinea(valor = '') {
        bytes.push(...encoder.encode(texto(valor) + '\n'));
    }

    function centrar() {
        comando(ESC, 0x61, 1);
    }

    function izquierda() {
        comando(ESC, 0x61, 0);
    }

    function negrita(activar) {
        comando(ESC, 0x45, activar ? 1 : 0);
    }

    // Papel de 58 mm: fuente A suele permitir unas 32 columnas.
    function fila(izquierdaTexto, derechaTexto) {
        const derecho = texto(derechaTexto);
        const izquierdo = texto(izquierdaTexto);
        const espacios = Math.max(1, 32 - izquierdo.length - derecho.length);

        escribirLinea(izquierdo + ' '.repeat(espacios) + derecho);
    }

    // Inserta un QR nativo ESC/POS a partir de la URL pública.
   async function imprimirQR(url) {
    const urlPublica = new URL(url, window.location.origin).href;

    const urlQR = new URL(
        'https://api.qrserver.com/v1/create-qr-code/'
    );

    urlQR.searchParams.set('size', '240x240');
    urlQR.searchParams.set('format', 'png');
    urlQR.searchParams.set('margin', '2');
    urlQR.searchParams.set('data', urlPublica);

    const imagen = new Image();
    imagen.crossOrigin = 'anonymous';

    await new Promise((resolve, reject) => {
        imagen.onload = resolve;
        imagen.onerror = () => reject(
            new Error('No se pudo descargar la imagen del QR.')
        );

        imagen.src = urlQR.toString();
    });

    // const canvas = document.createElement('canvas');
    // const ancho = 240;
    // const alto = 240;

    // canvas.width = ancho;
    // canvas.height = alto;

    // const contexto = canvas.getContext('2d', {
    //     willReadFrequently: true
    // });

    // contexto.fillStyle = '#fff';
    // contexto.fillRect(0, 0, ancho, alto);
    // contexto.drawImage(imagen, 0, 0, ancho, alto);
const canvas = document.createElement('canvas');

// Ancho imprimible habitual de una térmica de 58 mm: 384 puntos.
const ancho = 384;
const alto = 240;
const anchoQR = 240;

canvas.width = ancho;
canvas.height = alto;

const contexto = canvas.getContext('2d', {
    willReadFrequently: true
});

contexto.fillStyle = '#fff';
contexto.fillRect(0, 0, ancho, alto);

// Centrar el QR dentro del ancho imprimible.
const margenIzquierdo = Math.floor((ancho - anchoQR) / 2);

contexto.drawImage(
    imagen,
    margenIzquierdo,
    0,
    anchoQR,
    alto
);
    const pixeles = contexto.getImageData(
        0, 0, ancho, alto
    ).data;

    const bytesPorFila = Math.ceil(ancho / 8);
    const raster = [];

    for (let y = 0; y < alto; y++) {
        for (let byteX = 0; byteX < bytesPorFila; byteX++) {
            let valor = 0;

            for (let bit = 0; bit < 8; bit++) {
                const x = byteX * 8 + bit;

                if (x >= ancho) continue;

                const indice = (y * ancho + x) * 4;

                const gris =
                    0.299 * pixeles[indice] +
                    0.587 * pixeles[indice + 1] +
                    0.114 * pixeles[indice + 2];

                if (gris < 160) {
                    valor |= (0x80 >> bit);
                }
            }

            raster.push(valor);
        }
    }

    // Centrar imagen en papel térmico.
    comando(ESC, 0x61, 1);

    // Imprimir imagen monocromática mediante GS v 0.
    comando(
        GS, 0x76, 0x30, 0x00,
        bytesPorFila & 0xFF,
        (bytesPorFila >> 8) & 0xFF,
        alto & 0xFF,
        (alto >> 8) & 0xFF
    );

    bytes.push(...raster);

    comando(0x0A);

    // Volver a alinear el texto a la izquierda.
    comando(ESC, 0x61, 0);
}

    async function generarReciboESCPos() {
        bytes.length = 0;

        // Inicializar impresora.
        comando(ESC, 0x40);

        // Encabezado.
        centrar();
        negrita(true);
        escribirLinea(datosRecibo.empresa);
        escribirLinea('RECIBO DE COBRO');
        escribirLinea(datosRecibo.numero);
        negrita(false);

        escribirLinea('--------------------------------');

        izquierda();
        escribirLinea('Fecha: ' + datosRecibo.fecha + ' ' + datosRecibo.hora);
        escribirLinea('Cliente: ' + datosRecibo.cliente);
        escribirLinea('Cod: ' + datosRecibo.codigo);

        escribirLinea('--------------------------------');

        datosRecibo.cobros.forEach(cobro => {
            fila('Doc ' + cobro.documento, cobro.monto);
        });

        escribirLinea('--------------------------------');

        negrita(true);
        fila('TOTAL ' + datosRecibo.moneda, datosRecibo.total);
        negrita(false);

        escribirLinea('Pago: ' + datosRecibo.formaPago);

        if (datosRecibo.referencia) {
            escribirLinea('Ref: ' + datosRecibo.referencia);
        }

        escribirLinea('Recibido por: ' + datosRecibo.cobrador);

        escribirLinea('--------------------------------');

        centrar();
        escribirLinea('Escanee para ver el detalle');
      await imprimirQR(datosRecibo.url);

        escribirLinea('');
        escribirLinea('Gracias por su pago');
        // escribirLinea('');
        // escribirLinea('');

        // Alimentar el papel sin forzar el corte.
        comando(ESC, 0x64, 1);

        return new Uint8Array(bytes);
    }

    function convertirBase64(datos) {
        let binario = '';

        // Evita problemas con btoa y arreglos de bytes grandes.
        for (let i = 0; i < datos.length; i += 8192) {
            binario += String.fromCharCode(
                ...datos.subarray(i, Math.min(i + 8192, datos.length))
            );
        }

        return btoa(binario);
    }

   async function imprimirRecibo() {
    const esAndroid = /Android/i.test(navigator.userAgent);

    if (!esAndroid) {
        window.print();
        return;
    }

    const boton = document.getElementById('btnImprimir');

    if (boton.disabled) return;

    boton.disabled = true;

    try {
        const datos = await generarReciboESCPos();
        const base64 = convertirBase64(datos);

        window.location.href = 'rawbt:base64,' + base64;
    } catch (error) {
        console.error('Error al preparar el recibo:', error);

        alert(
            'No se pudo preparar el recibo. ' +
            'Comprueba la conexión a Internet y vuelve a intentarlo.'
        );
    } finally {
        boton.disabled = false;
    }
}

    document.getElementById('btnImprimir')
        .addEventListener('click', imprimirRecibo);

    // Respeta el parámetro ?imprimir=1, tanto en Android como en PC.
    // @if(request()->boolean('imprimir'))
    //     window.addEventListener('load', () => {
    //         setTimeout(imprimirRecibo, 500);
    //     });
    // @endif
    </script>
</body>

</html>