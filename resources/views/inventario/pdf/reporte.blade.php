<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Reporte de Inventario - {{ $fechaDesde }} a {{ $fechaHasta }}</title>
    <style>
        /* --- DOMPDF: Múltiples páginas A4 portrait --- */
        @page {
            size: A4;
            margin: 15mm;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #111;
            font-size: 9pt;
            line-height: 1.2;
        }

        * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .marco {
            border: 0.5mm solid #184A8C;
            padding: 5mm;
            margin-bottom: 5mm;
        }

        .azul {
            color: #184A8C;
        }

        .rojo {
            color: #C32020;
        }

        .verde {
            color: #059669;
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
        }

        .tabla-movimientos {
            page-break-inside: auto;
        }

        .tabla-movimientos tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .tabla-movimientos thead {
            display: table-header-group;
        }

        .sp {
            height: 3mm;
        }

        .tit {
            font-size: 18pt;
            font-weight: 700;
        }

        .h1 {
            font-size: 14pt;
            font-weight: 700;
        }

        .h2 {
            font-size: 11pt;
            font-weight: 700;
        }

        .h3 {
            font-size: 10pt;
            font-weight: 700;
        }

        .lbl {
            color: #333;
            font-weight: 600;
        }

        .logo {
            width: 35mm;
            height: auto;
        }

        /* Tabla de movimientos */
        .tabla-movimientos {
            width: 100%;
            margin: 3mm 0;
            font-size: 7pt;
            page-break-inside: auto;
        }

        .tabla-movimientos thead {
            display: table-header-group;
        }

        .tabla-movimientos th {
            background: #f0f0f0;
            border: 0.3mm solid #000;
            padding: 1.5mm;
            font-weight: 700;
            text-align: center;
            font-size: 7pt;
        }

        .tabla-movimientos td {
            border: 0.3mm solid #ccc;
            padding: 1mm;
            vertical-align: top;
            font-size: 7pt;
        }

        .tabla-movimientos tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .tabla-movimientos tr:nth-child(even) {
            background: #f9f9f9;
        }

        /* Tarjetas de resumen */
        .resumen-card {
            border: 0.5mm solid #184A8C;
            border-radius: 2mm;
            padding: 3mm;
            margin: 2mm;
            background: #f8f9fa;
            display: inline-block;
            width: 45%;
            vertical-align: top;
        }

        .resumen-numero {
            font-size: 16pt;
            font-weight: 700;
            color: #184A8C;
        }

        .resumen-label {
            font-size: 9pt;
            color: #666;
            margin-bottom: 1mm;
        }

        /* Filtros aplicados */
        .filtros-box {
            border: 0.3mm solid #ddd;
            background: #f8f9fa;
            padding: 2mm;
            margin: 2mm 0;
            border-radius: 1mm;
        }

        .filtro-item {
            margin: 1mm 0;
            font-size: 8pt;
        }

        /* Tipos de movimiento */
        .empeno {
            color: #059669;
            font-weight: 600;
        }

        .desempeno {
            color: #dc2626;
            font-weight: 600;
        }

        /* Page break utilities */
        .page-break {
            page-break-before: always;
        }

        .no-break {
            page-break-inside: avoid;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 10mm;
            left: 15mm;
            right: 15mm;
            text-align: center;
            font-size: 8pt;
            color: #666;
            border-top: 0.3mm solid #ddd;
            padding-top: 2mm;
        }
    </style>
</head>

<body>
    <!-- Footer en todas las páginas -->
    <div class="footer">
        Reporte de Inventario - Generado el {{ $fechaGeneracion->format('d/m/Y H:i:s') }} por {{ $usuario->name }} |
        <a href="https://fenixbgsas.com/" target="_blank" style="color: #184A8C; text-decoration: none;">
            Desarrollado por Fenix BG SAS
        </a>
    </div>

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
                    <div class="tit azul caps">
                        {{ $empresa ? $empresa->razon_social : 'Sistema de Compraventa' }}
                    </div>
                    @if($empresa)
                        <div>{{ $empresa->nit ?? '' }}{{ $empresa->dv ? ' - ' . $empresa->dv : '' }}</div>
                        <div>{{ $empresa->representante_legal ?? '' }}{{ $empresa->cedula_representante ? ' - ' . $empresa->cedula_representante : '' }}</div>
                        <div>{{ $empresa->direccion ?? '' }}</div>
                        <div>{{ $empresa->telefono_fijo ?? '' }}{{ $empresa->celular ? ' / ' . $empresa->celular : '' }}</div>
                    @endif
                    <div class="sp"></div>
                    <div class="h1 azul caps">REPORTE DE INVENTARIO</div>
                    <div style="font-size: 10pt; color: #666;">
                        Período: {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}
                    </div>
                </td>
                <td style="width:25%; vertical-align:top; text-align:right;">
                    <div style="border: 0.5mm solid #184A8C; padding: 2mm; margin-bottom: 2mm;">
                        <div class="h3 azul">TOTALES GENERALES</div>
                        <div style="margin: 1mm 0;">
                            <span class="lbl">Movimientos:</span><br>
                            <span style="font-size: 14pt; font-weight: 700;">{{ number_format($totales['totalMovimientos']) }}</span>
                        </div>
                        <div style="margin: 1mm 0;">
                            <span class="lbl">Monto Neto:</span><br>
                            <span style="font-size: 12pt; font-weight: 700; color: #059669;">${{ number_format($totales['totalMontoNeto'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div style="border: 0.5mm solid #dc2626; padding: 2mm;">
                        <div style="font-size: 8pt; color: #dc2626; font-weight: 600;">ORO / NO ORO</div>
                        <div style="font-size: 9pt; margin: 1mm 0;">
                            Oro: ${{ number_format($totales['totalOroNeto'], 0, ',', '.') }}
                        </div>
                        <div style="font-size: 9pt;">
                            No Oro: ${{ number_format($totales['totalNoOroNeto'], 0, ',', '.') }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="sp"></div>

        <!-- FILTROS APLICADOS -->
        @if(count($filtrosAplicados) > 0)
        <div class="filtros-box no-break">
            <div class="h3 azul">FILTROS APLICADOS</div>
            @foreach($filtrosAplicados as $filtro)
                <div class="filtro-item">• {{ $filtro }}</div>
            @endforeach
        </div>
        @endif

        <div class="sp"></div>

        <!-- RESUMEN POR TIPOS -->
        <div class="no-break">
            <div class="h2 azul">RESUMEN POR TIPOS DE PRODUCTO</div>

            <table style="width: 100%; margin: 2mm 0;">
                <tr>
                    <td style="width: 50%; vertical-align: top; padding-right: 2mm;">
                        <div style="border: 0.3mm solid #059669; padding: 2mm; background: #f0fdf4;">
                            <div class="h3" style="color: #059669; margin-bottom: 2mm;">PRODUCTOS DE ORO</div>
                            <div style="font-size: 12pt; font-weight: 700; color: #059669; margin-bottom: 2mm;">
                                Total: ${{ number_format($totales['totalOroNeto'], 0, ',', '.') }}
                            </div>
                            @if(count($totales['totalesPorTipoOro']) > 0)
                                @foreach($totales['totalesPorTipoOro'] as $tipo => $monto)
                                    <div style="font-size: 8pt; margin: 1mm 0;">
                                        <table style="width: 100%; border: none;">
                                            <tr>
                                                <td style="border: none; padding: 0;">{{ $tipo }}:</td>
                                                <td style="border: none; padding: 0; text-align: right; font-weight: 600;">
                                                    ${{ number_format($monto, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                @endforeach
                            @else
                                <div style="font-size: 8pt; color: #666;">No hay movimientos de oro</div>
                            @endif
                        </div>
                    </td>
                    <td style="width: 50%; vertical-align: top; padding-left: 2mm;">
                        <div style="border: 0.3mm solid #7c3aed; padding: 2mm; background: #faf5ff;">
                            <div class="h3" style="color: #7c3aed; margin-bottom: 2mm;">PRODUCTOS NO ORO</div>
                            <div style="font-size: 12pt; font-weight: 700; color: #7c3aed; margin-bottom: 2mm;">
                                Total: ${{ number_format($totales['totalNoOroNeto'], 0, ',', '.') }}
                            </div>
                            @if(count($totales['totalesPorTipoNoOro']) > 0)
                                @foreach($totales['totalesPorTipoNoOro'] as $tipo => $monto)
                                    <div style="font-size: 8pt; margin: 1mm 0;">
                                        <table style="width: 100%; border: none;">
                                            <tr>
                                                <td style="border: none; padding: 0;">{{ $tipo }}:</td>
                                                <td style="border: none; padding: 0; text-align: right; font-weight: 600;">
                                                    ${{ number_format($monto, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                @endforeach
                            @else
                                <div style="font-size: 8pt; color: #666;">No hay movimientos de productos no oro</div>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="sp"></div>
    </div>

    <!-- TABLA DE MOVIMIENTOS -->
    @if($movimientos->count() > 0)
    <div class="marco">
        <div class="h2 azul">DETALLE DE MOVIMIENTOS ({{ $movimientos->count() }} registros)</div>

        <table class="tabla-movimientos">
            <thead>
                <tr>
                    <th style="width: 8%;">Fecha</th>
                    <th style="width: 10%;">Tipo</th>
                    <th style="width: 12%;">Contrato</th>
                    <th style="width: 18%;">Cliente</th>
                    <th style="width: 12%;">Empresa</th>
                    <th style="width: 10%;">Monto</th>
                    <th style="width: 15%;">Productos</th>
                    <th style="width: 8%;">Estado</th>
                    <th style="width: 7%;">Ubicación</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movimientos as $index => $movimiento)
                    <tr>
                        <td class="t-centrado">
                            {{ $movimiento->created_at->format('d/m/Y') }}
                        </td>
                        <td class="t-centrado">
                            @if($movimiento->tipo_registro === 'empeno')
                                <span class="empeno">EMPEÑO</span>
                            @else
                                <span class="desempeno">DESEMPEÑO</span>
                            @endif
                        </td>
                        <td class="t-centrado">
                            @if($movimiento->tipo_registro === 'empeno')
                                {{ $movimiento->numero_contrato }}
                            @else
                                {{ optional($movimiento->boletaEmpeno)->numero_contrato }}
                            @endif
                        </td>
                        <td>
                            @php
                                $cliente = $movimiento->tipo_registro === 'empeno'
                                    ? $movimiento->cliente
                                    : optional($movimiento->boletaEmpeno)->cliente;
                            @endphp
                            @if($cliente)
                                <div style="font-weight: 600; font-size: 8pt;">
                                    {{ $cliente->razon_social ?: ($cliente->nombres . ' ' . $cliente->apellidos) }}
                                </div>
                                <div style="font-size: 7pt; color: #666;">
                                    {{ optional($cliente->tipoDocumento)->abreviacion }}: {{ $cliente->cedula_nit }}
                                </div>
                            @else
                                N/A
                            @endif
                        </td>
                        <td style="font-size: 8pt;">
                            @if($movimiento->tipo_registro === 'empeno')
                                {{ optional($movimiento->empresa)->razon_social }}
                            @else
                                {{ optional(optional($movimiento->boletaEmpeno)->empresa)->razon_social }}
                            @endif
                        </td>
                        <td class="t-der">
                            <span style="font-weight: 600; color: {{ optional($movimiento->tipoMovimiento)->es_suma ? '#059669' : '#dc2626' }};">
                                ${{ number_format($movimiento->monto, 0, ',', '.') }}
                            </span>
                        </td>
                        <td style="font-size: 7pt;">
                            @php
                                $productos = $movimiento->tipo_registro === 'empeno'
                                    ? $movimiento->productos
                                    : optional($movimiento->boletaEmpeno)->productos;
                            @endphp
                            @if($productos && $productos->count() > 0)
                                @foreach($productos->take(2) as $prod)
                                    @if($prod->producto)
                                        <div>{{ $prod->producto->nombre }}</div>
                                    @endif
                                @endforeach
                                @if($productos->count() > 2)
                                    <div style="color: #666; font-style: italic;">
                                        +{{ $productos->count() - 2 }} más
                                    </div>
                                @endif
                            @else
                                <span style="color: #999;">Sin productos</span>
                            @endif
                        </td>
                        <td class="t-centrado" style="font-size: 8pt;">
                            {{ strtoupper($movimiento->estado) }}
                        </td>
                        <td style="font-size: 7pt;">
                            @if($movimiento->tipo_registro === 'empeno' && $movimiento->ubicacion)
                                {{ $movimiento->ubicacion }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="marco">
        <div class="h2 azul">DETALLE DE MOVIMIENTOS</div>
        <div style="text-align: center; padding: 20mm; color: #666;">
            No se encontraron movimientos para los filtros seleccionados.
        </div>
    </div>
    @endif

    <!-- INFORMACIÓN ADICIONAL -->
    <div class="marco no-break">
        <div class="h2 azul">INFORMACIÓN DEL REPORTE</div>
        <table style="font-size: 8pt;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 2mm;">
                    <div><span class="lbl">Fecha de generación:</span> {{ $fechaGeneracion->format('d/m/Y H:i:s') }}</div>
                    <div><span class="lbl">Generado por:</span> {{ $usuario->name }}</div>
                    <div><span class="lbl">Período consultado:</span> {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}</div>
                    <div><span class="lbl">Total de días:</span> {{ \Carbon\Carbon::parse($fechaDesde)->diffInDays(\Carbon\Carbon::parse($fechaHasta)) + 1 }} días</div>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 2mm;">
                    <div><span class="lbl">Criterios de cálculo:</span></div>
                    <div style="margin-left: 2mm; font-size: 7pt;">
                        • Los productos de oro se clasifican por tipo de oro<br>
                        • Los productos sin tipo de oro se clasifican como "No Oro"
                    </div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
