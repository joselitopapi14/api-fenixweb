<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>CUO{{ $boleta->numero_contrato ?? '' }}</title>
    <style>
        /* --- DOMPDF: 1 sola página A4 portrait --- */
        @page {
            size: A4;
            margin: 10mm;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #111;
            font-size: 10pt;
            line-height: 1.3;
        }

        * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .marco {
            border: 0.5mm solid #184A8C;
            padding: 5mm;
        }

        .azul {
            color: #184A8C;
        }

        .rojo {
            color: #C32020;
        }

        .caps {
            text-transform: uppercase;
        }

        .t-centrado {
            text-align: center;
        }

        .t-der {
            text-align: right;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            page-break-inside: avoid;
        }

        .sp {
            height: 2mm;
        }

        .tit {
            font-size: 16pt;
            font-weight: 700;
        }

        .h1 {
            font-size: 12pt;
            font-weight: 700;
        }

        .h2 {
            font-size: 10pt;
            font-weight: 700;
        }

        .folio {
            font-size: 14pt;
            font-weight: 700;
            color: #C32020;
        }

        .precioLbl {
            font-size: 9pt;
            font-weight: 700;
            color: #184A8C;
            text-align: right;
        }

        .precioBox {
            border: 0.5mm solid #184A8C;
            padding: 2mm;
            font-weight: 700;
            text-align: center;
            font-size: 14pt;
        }

        .bloque {
            border: 0.2mm solid #d9d9d9;
            padding: 2mm;
            margin: 2mm 0;
        }

        .lbl {
            color: #333;
            font-weight: 600;
        }

        .linea {
            display: inline-block;
            min-width: 15mm;
            border-bottom: 0.3mm solid #184A8C;
        }

        .tabla-cuotas {
            width: 100%;
            margin: 3mm 0;
        }

        .tabla-cuotas th {
            background: #f0f0f0;
            border: 0.5mm solid #000;
            padding: 2mm;
            font-size: 9pt;
        }

        .tabla-cuotas td {
            border: 0.5mm solid #000;
            padding: 1.5mm;
            font-size: 9pt;
        }

        .cuota-destacada {
            background: #f0f0f0 !important;
            font-weight: 700;
        }

        .resumen {
            margin: 3mm 0;
        }

        .resumen-item {
            margin: 1mm 0;
        }

        .firmas {
            margin-top: 15mm;
        }

        .firma-linea {
            border-bottom: 0.5mm solid #000;
            width: 100%;
            height: 15mm;
            margin-bottom: 2mm;
        }

        .nota {
            font-size: 10pt;
            font-weight: 700;
            text-align: center;
            color: #C32020;
            padding: 2mm 0;
        }

        .logo {
            width: 40mm;
            height: auto;
        }
    </style>
</head>

<body>
    <div class="marco">

        <!-- ENCABEZADO -->
        <table>
            <tr>
                <td style="width:25%; vertical-align:top;">
                    @if (file_exists(public_path('images/logo.png')))
                        @php
                            $logoPath = public_path('images/logo.png');
                            $logoData = base64_encode(file_get_contents($logoPath));
                            $logoMime = 'image/png';
                        @endphp
                        <img src="data:{{ $logoMime }};base64,{{ $logoData }}" class="logo" alt="Logo">
                    @endif
                </td>
                <td style="width:50%; padding:0 3mm; vertical-align:top;">
                    <div class="tit azul caps">{{ $boleta->empresa->razon_social ?? 'Compraventa' }}</div>
                    <div>{{ $boleta->empresa->nit ?? '' }}{{ $boleta->empresa->dv ? ' - ' . $boleta->empresa->dv : '' }}
                    </div>
                    <div>
                        {{ $boleta->empresa->representante_legal ?? '' }}{{ $boleta->empresa->cedula_representante ? ' - ' . $boleta->empresa->cedula_representante : '' }}
                    </div>
                    <div>{{ $boleta->empresa->direccion ?? '' }}</div>
                    <div>
                        {{ $boleta->empresa->telefono_fijo ?? '' }}{{ $boleta->empresa->celular ? ' / ' . $boleta->empresa->celular : '' }}
                    </div>
                    <div class="sp"></div>
                    <div class="h2 azul caps">RECIBO DE CUOTA</div>
                </td>
                <td style="width:25%; vertical-align:top;">
                    <div class="t-der folio">CUO.{{ $boleta->numero_contrato ?? '' }}</div>
                    <div class="sp"></div>
                    @if(isset($boleta->productos) && $boleta->productos->count())
                        @php
                            $productosList = $boleta->productos->map(function($p) {
                                $producto = optional($p)->producto;
                                $nombre = optional($producto)->nombre ?: 'N/A';
                                $cantidad = $p->cantidad ?? '';
                                $tipoMedidaObj = optional($producto)->tipoMedida;
                                $tipoMedida = optional($tipoMedidaObj)->nombre ?: optional($tipoMedidaObj)->name ?: 'N/A';
                                return trim($nombre . ' ' . $cantidad) . ' (' . $tipoMedida . ')';
                            })->implode(', ');
                        @endphp
                        <div style="font-size:9pt; margin-top:3mm; text-align:right;"><strong>Productos:</strong> {{ $productosList }}</div>
                    @else
                        <div style="font-size:9pt; margin-top:3mm; text-align:right;"><strong>Productos:</strong> No registrados</div>
                    @endif
                    <div class="precioLbl">MONTO PAGADO $</div>
                    <div class="precioBox">
                        {{ number_format($cuota->monto_pagado, 0, ',', '.') }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="sp"></div>

        <!-- INFORMACIÓN DEL CLIENTE -->
        <table style="border: 0.5mm solid #000;">
            <tr>
                <td style="width:70%; padding: 2mm; border-right: 0.5mm solid #000; vertical-align:top;">
                    <div><span class="lbl">Cliente:</span> {{ $boleta->cliente->nombre_completo ?? 'N/A' }}
                    </div>
                    <div><span class="lbl">{{ $boleta->cliente->tipoDocumento->abreviacion ?? 'Doc' }}:</span>
                        {{ $boleta->cliente->cedula_nit ?? 'N/A' }}</div>
                    <div><span class="lbl">Dirección:</span> {{ $boleta->cliente->direccion ?? 'N/A' }}</div>
                    <div><span class="lbl">Teléfono:</span> {{ $boleta->cliente->telefono ?: 'N/A' }}</div>
                </td>
                <td style="width:30%; padding: 2mm; vertical-align:top;">
                    <div><span class="lbl">Boleta:</span> {{ $boleta->numero_contrato }}</div>
                    <div><span class="lbl">Fecha Cuota:</span> {{ $cuota->fecha_abono->format('d/m/Y') }}</div>
                    <div><span class="lbl">Hora:</span> {{ $cuota->created_at->format('H:i:s') }}</div>
                    <div><span class="lbl">Atendido por:</span> {{ $cuota->usuario->name ?? 'N/A' }}</div>
                </td>
            </tr>
        </table>

        <div class="sp"></div>

        <!-- TABLA DE CUOTAS -->
        <div class="h2 azul">HISTORIAL DE CUOTAS</div>
        <table class="tabla-cuotas">
            <thead>
                <tr>
                    <th>Cuota #</th>
                    <th>Fecha</th>
                    <th>Monto Pagado</th>
                    <th>Observaciones</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($todasLasCuotas as $index => $cuotaItem)
                    <tr class="{{ $cuotaItem->id == $cuota->id ? 'cuota-destacada' : '' }}">
                        <td class="t-centrado">{{ $index + 1 }}</td>
                        <td class="t-centrado">{{ $cuotaItem->fecha_abono->format('d/m/Y') }}</td>
                        <td class="t-der">${{ number_format($cuotaItem->monto_pagado, 0, ',', '.') }}</td>
                        <td class="t-centrado caps">{{ $cuotaItem->observaciones }}</td>
                        <td class="t-centrado caps">{{ $cuotaItem->estado }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="sp"></div>

        <!-- RESUMEN -->
        <div class="h2 azul">RESUMEN</div>
        <table style="border: 0.5mm solid #000;">
            <tr>
                <td style="width:70%; padding: 2mm; border-right: 0.5mm solid #000; vertical-align:top;">
                    <div class="resumen-item"><span class="lbl">Total de cuotas registradas:</span>
                        {{ $todasLasCuotas->count() }}</div>
                    <div class="resumen-item"><span class="lbl">Valor contrato:</span>
                        ${{ number_format($boleta->monto_prestamo, 0, ',', '.') }}</div>
                    <div class="resumen-item"><span class="lbl">Contrato:</span>
                        {{ $boleta->numero_contrato }}</div>
                    <div class="resumen-item">
                        <span class="lbl">Productos de la boleta:</span>
                        @if(isset($boleta->productos) && $boleta->productos->count())
                            @php
                                $productosDetalle = $boleta->productos->map(function($p) {
                                    $producto = optional($p)->producto;
                                    $nombre = optional($producto)->nombre ?: 'N/A';
                                    $cantidad = $p->cantidad ?? '';
                                    $tipoMedidaObj = optional($producto)->tipoMedida;
                                    $tipoMedida = optional($tipoMedidaObj)->nombre ?: 'N/A';
                                    return $nombre . ', ' . $cantidad . ', ' . $tipoMedida;
                                })->implode('; ');
                            @endphp
                            {{ $productosDetalle }}
                        @else
                            No hay productos registrados
                        @endif
                    </div>
                    <div class="resumen-item">
                        <span class="lbl">Monto letras:</span>
                        {{ strtoupper(\App\Helpers\NumberToWordsHelper::convertir($todasLasCuotas->sum('monto_pagado'))) }} PESOS
                    </div>
                </td>
                <td style="width:30%; padding: 2mm; vertical-align:top;">
                    <div class="resumen-item"><span class="lbl">Total monto pagado:</span>
                        ${{ number_format($todasLasCuotas->sum('monto_pagado'), 0, ',', '.') }}</div>
                    @php
                        $diasTranscurridos = $boleta->created_at->diffInDays(now());
                    @endphp
                    <div class="resumen-item"><span class="lbl">Días transcurridos desde creación:</span>
                        {{ number_format($diasTranscurridos, 0, ',', '.') }} días</div>
                </td>
            </tr>
        </table>

        <div class="sp"></div>

        <!-- FIRMAS -->
        <div class="firmas">
            <table>
                <tr>
                    <td style="width:45%; text-align:center;">
                        <div class="firma-linea"></div>
                        <div><strong>CLIENTE</strong></div>
                        <div>{{ $boleta->cliente->nombre_completo ?? '' }}</div>
                        <div>{{ $boleta->cliente->tipoDocumento->abreviacion ?? '' }}:
                            {{ $boleta->cliente->cedula_nit ?? '' }}</div>
                    </td>
                    <td style="width:10%;"></td>
                    <td style="width:45%; text-align:center;">
                        <div class="firma-linea"></div>
                        <div><strong>AUTORIZADO</strong></div>
                        <div>{{ $boleta->empresa->razon_social ?? '' }}</div>
                        <div>{{ $cuota->usuario->name ?? 'Sistema' }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- NOTA FINAL -->
        <div class="nota caps">
            Recibo generado automáticamente - {{ now()->format('d/m/Y H:i:s') }}
        </div>

        <!-- FOOTER DESARROLLADOR -->
        <div style="text-align: center; margin-top: 10mm; font-size: 9pt; color: #666;">
            <a href="https://fenixbgsas.com/" target="_blank" style="color: #184A8C; text-decoration: none;">
                Desarrollado por Fenix BG SAS
            </a>
        </div>

    </div>
</body>

</html>
