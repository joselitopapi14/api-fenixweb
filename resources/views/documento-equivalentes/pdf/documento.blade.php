<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>DOC{{ $documentoEquivalente->id ?? '' }}</title>
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

        .tabla-info {
            width: 100%;
            margin: 3mm 0;
        }

        .tabla-info th {
            background: #f0f0f0;
            border: 0.5mm solid #000;
            padding: 2mm;
            font-size: 9pt;
        }

        .tabla-info td {
            border: 0.5mm solid #000;
            padding: 1.5mm;
            font-size: 9pt;
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

        .qr-dian {
            width: 20mm;
            height: 20mm;
            border: 0.5mm solid #184A8C;
            padding: 1mm;
            text-align: center;
        }

        .qr-dian-large {
            width: 30mm;
            height: 30mm;
            border: 0.5mm solid #184A8C;
            padding: 2mm;
            text-align: center;
        }

        .qr-label {
            font-size: 8pt;
            color: #184A8C;
            font-weight: 600;
            margin-top: 1mm;
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
                    <div class="tit azul caps">{{ $documentoEquivalente->empresa->razon_social ?? 'Compraventa' }}</div>
                    <div>{{ $documentoEquivalente->empresa->nit ?? '' }}{{ $documentoEquivalente->empresa->dv ? ' - ' . $documentoEquivalente->empresa->dv : '' }}
                    </div>
                    <div>
                        {{ $documentoEquivalente->empresa->representante_legal ?? '' }}{{ $documentoEquivalente->empresa->cedula_representante ? ' - ' . $documentoEquivalente->empresa->cedula_representante : '' }}
                    </div>
                    <div>{{ $documentoEquivalente->empresa->direccion ?? '' }}</div>
                    <div>
                        {{ $documentoEquivalente->empresa->telefono_fijo ?? '' }}{{ $documentoEquivalente->empresa->celular ? ' / ' . $documentoEquivalente->empresa->celular : '' }}
                    </div>
                    <div class="sp"></div>
                    <div class="h2 azul caps">DOCUMENTO EQUIVALENTE</div>
                </td>
                <td style="width:25%; vertical-align:top;">
                    <div class="t-der folio">DOC.{{ $documentoEquivalente->id ?? '' }}</div>
                    <div class="sp"></div>
                    <div style="font-size:9pt; margin-top:3mm; text-align:right;"><strong>Concepto:</strong> {{ $documentoEquivalente->concepto->nombre ?? 'N/A' }}</div>
                    <div class="precioLbl">MONTO $</div>
                    <div class="precioBox">
                        {{ number_format($documentoEquivalente->monto, 0, ',', '.') }}
                    </div>

                    @if($documentoEquivalente->cuds && $documentoEquivalente->qr_code_image)
                    <div style="margin-top: 3mm; text-align: right;">
                        <div class="qr-dian">
                            <img src="data:image/png;base64,{{ $documentoEquivalente->qr_code_image }}" style="width: 18mm; height: 18mm;" alt="QR DIAN">
                        </div>
                        <div class="qr-label">VALIDAR DIAN</div>
                    </div>
                    @endif
                </td>
            </tr>
        </table>

        <div class="sp"></div>

        <!-- INFORMACIÓN DEL CLIENTE -->
        <table style="border: 0.5mm solid #000;">
            <tr>
                <td style="width:70%; padding: 2mm; border-right: 0.5mm solid #000; vertical-align:top;">
                    <div><span class="lbl">Cliente:</span> {{ $documentoEquivalente->cliente->nombre_completo ?? 'N/A' }}
                    </div>
                    <div><span class="lbl">{{ $documentoEquivalente->cliente->tipoDocumento->abreviacion ?? 'Doc' }}:</span>
                        {{ $documentoEquivalente->cliente->cedula_nit ?? 'N/A' }}</div>
                    <div><span class="lbl">Dirección:</span> {{ $documentoEquivalente->cliente->direccion ?? 'N/A' }}</div>
                    <div><span class="lbl">Teléfono:</span> {{ $documentoEquivalente->cliente->telefono ?: 'N/A' }}</div>
                </td>
                <td style="width:30%; padding: 2mm; vertical-align:top;">
                    <div><span class="lbl">Documento:</span> {{ $documentoEquivalente->id }}</div>
                    <div><span class="lbl">Fecha Documento:</span> {{ $documentoEquivalente->fecha_documento->format('d/m/Y') }}</div>
                    <div><span class="lbl">Fecha Registro:</span> {{ $documentoEquivalente->created_at->format('d/m/Y H:i:s') }}</div>
                    <div><span class="lbl">Estado:</span> {{ ucfirst($documentoEquivalente->estado) }}</div>
                </td>
            </tr>
        </table>

        <div class="sp"></div>

        <!-- INFORMACIÓN DEL DOCUMENTO -->
        <div class="h2 azul">DETALLES DEL DOCUMENTO</div>
        <table class="tabla-info">
            <thead>
                <tr>
                    <th>Campo</th>
                    <th>Información</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="lbl">Concepto</td>
                    <td>{{ $documentoEquivalente->concepto->nombre ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Cliente</td>
                    <td>{{ $documentoEquivalente->cliente->nombre_completo ?? 'N/A' }} - {{ $documentoEquivalente->cliente->cedula_nit ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Descripción</td>
                    <td>{{ $documentoEquivalente->descripcion }}</td>
                </tr>
                <tr>
                    <td class="lbl">Monto</td>
                    <td>${{ number_format($documentoEquivalente->monto, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="sp"></div>

        <!-- RESUMEN -->
        <div class="h2 azul">RESUMEN</div>
        <table style="border: 0.5mm solid #000;">
            <tr>
                <td style="width:70%; padding: 2mm; border-right: 0.5mm solid #000; vertical-align:top;">
                    <div class="resumen-item"><span class="lbl">Fecha del documento:</span>
                        {{ $documentoEquivalente->fecha_documento->format('d/m/Y') }}</div>
                    <div class="resumen-item"><span class="lbl">Concepto aplicado:</span>
                        {{ $documentoEquivalente->concepto->nombre ?? 'N/A' }}</div>
                    <div class="resumen-item">
                        <span class="lbl">Monto en letras:</span>
                        {{ strtoupper(\App\Helpers\NumberToWordsHelper::convertir($documentoEquivalente->monto)) }} PESOS
                    </div>
                </td>
                <td style="width:30%; padding: 2mm; vertical-align:top;">
                    <div class="resumen-item"><span class="lbl">Monto del documento:</span>
                        ${{ number_format($documentoEquivalente->monto, 2, ',', '.') }}</div>
                    <div class="resumen-item"><span class="lbl">Estado actual:</span>
                        {{ ucfirst($documentoEquivalente->estado) }}</div>
                    @php
                        $diasTranscurridos = $documentoEquivalente->created_at->diffInDays(now());
                    @endphp
                    <div class="resumen-item"><span class="lbl">Días desde creación:</span>
                        {{ number_format($diasTranscurridos, 0, ',', '.') }} días</div>
                </td>
            </tr>
        </table>

        <div class="sp"></div>
{{--
        @if($documentoEquivalente->cuds && $documentoEquivalente->qr_code_image)
        <!-- VALIDACIÓN DIAN -->
        <div class="h2 azul">VALIDACIÓN DIAN</div>
        <table style="border: 0.5mm solid #000;">
            <tr>
                <td style="width:70%; padding: 2mm; border-right: 0.5mm solid #000; vertical-align:top;">
                    <div class="resumen-item"><span class="lbl">CUDS (Código Único de Documento Soporte):</span></div>
                    <div style="font-size: 8pt; word-break: break-all; margin: 2mm 0;">{{ $documentoEquivalente->cuds }}</div>

                    @if($documentoEquivalente->xml_url)
                    <div class="resumen-item"><span class="lbl">XML de respuesta DIAN:</span></div>
                    <div style="font-size: 8pt; word-break: break-all; margin: 2mm 0;">{{ $documentoEquivalente->xml_url }}</div>
                    @endif

                    <div class="resumen-item"><span class="lbl">URL de validación:</span></div>
                    <div style="font-size: 8pt; word-break: break-all; margin: 2mm 0;">{{ $documentoEquivalente->qr_code_url }}</div>
                </td>
                <td style="width:30%; padding: 2mm; vertical-align:top; text-align: center;">
                    <div class="qr-dian-large">
                        <img src="data:image/png;base64,{{ $documentoEquivalente->qr_code_image }}" style="width: 26mm; height: 26mm;" alt="QR Validación DIAN">
                    </div>
                    <div class="qr-label">ESCANEAR PARA</div>
                    <div class="qr-label">VALIDAR EN DIAN</div>
                    <div style="font-size: 7pt; color: #666; margin-top: 2mm;">
                        Ambiente: {{ $documentoEquivalente->empresa->tipo_ambiente == 2 ? 'Habilitación' : 'Producción' }}
                    </div>
                </td>
            </tr>
        </table>
        @endif --}}

        <div class="sp"></div>

        <!-- FIRMAS -->
        <div class="firmas">
            <table>
                <tr>
                    <td style="width:45%; text-align:center;">
                        <div class="firma-linea"></div>
                        <div><strong>CLIENTE</strong></div>
                        <div>{{ $documentoEquivalente->cliente->nombre_completo ?? '' }}</div>
                        <div>{{ $documentoEquivalente->cliente->tipoDocumento->abreviacion ?? '' }}:
                            {{ $documentoEquivalente->cliente->cedula_nit ?? '' }}</div>
                    </td>
                    <td style="width:10%;"></td>
                    <td style="width:45%; text-align:center;">
                        <div class="firma-linea"></div>
                        <div><strong>AUTORIZADO</strong></div>
                        <div>{{ $documentoEquivalente->empresa->razon_social ?? '' }}</div>
                        <div>{{ $documentoEquivalente->empresa->representante_legal ?? 'Sistema' }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- NOTA FINAL -->
        <div class="nota caps">
            Documento generado automáticamente - {{ now()->format('d/m/Y H:i:s') }}
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
