<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuadre de Caja - {{ $fechaDesde }} a {{ $fechaHasta }}</title>
    <style>
        @page {
            margin: 20mm 15mm;
            size: A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c5282;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #1a202c;
        }

        .report-subtitle {
            font-size: 12px;
            color: #4a5568;
            margin-bottom: 5px;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .info-row {
            display: table-row;
        }

        .info-cell {
            display: table-cell;
            padding: 3px 5px;
            border: 1px solid #e2e8f0;
            background-color: #f7fafc;
        }

        .info-label {
            font-weight: bold;
            width: 25%;
            background-color: #edf2f7;
        }

        .totals-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .totals-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .totals-row {
            display: table-row;
        }

        .totals-cell {
            display: table-cell;
            padding: 8px;
            border: 1px solid #2d3748;
            text-align: center;
            font-weight: bold;
            background-color: #f7fafc;
        }

        .total-neto {
            background-color: #c6f6d5 !important;
            color: #22543d;
            font-size: 12px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th,
        .table td {
            border: 1px solid #e2e8f0;
            padding: 4px 3px;
            text-align: left;
            vertical-align: top;
        }

        .table th {
            background-color: #edf2f7;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
        }

        .table td {
            font-size: 8px;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        .status-badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-empeno {
            background-color: #c6f6d5;
            color: #22543d;
        }

        .badge-cuota {
            background-color: #bee3f8;
            color: #2a4365;
        }

        .badge-desempeno {
            background-color: #fed7d7;
            color: #742a2a;
        }

        .badge-documento {
            background-color: #feebc8;
            color: #7b341e;
        }

        .impact-suma {
            background-color: #c6f6d5;
            color: #22543d;
            font-weight: bold;
        }

        .impact-resta {
            background-color: #fed7d7;
            color: #742a2a;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #4a5568;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }

        .page-break {
            page-break-before: always;
        }

        .filters-section {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }

        .filters-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #2d3748;
        }

        .breakdown-section {
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .breakdown-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .breakdown-row {
            display: table-row;
        }

        .breakdown-cell {
            display: table-cell;
            padding: 5px;
            border: 1px solid #e2e8f0;
        }

        .breakdown-header {
            background-color: #edf2f7;
            font-weight: bold;
            text-align: center;
        }

        .breakdown-oro {
            background-color: #fef5e7;
            color: #7b341e;
        }

        .breakdown-no-oro {
            background-color: #f3e8ff;
            color: #553c9a;
        }

        .warning-note {
            background-color: #fef5e7;
            border: 1px solid #f6e05e;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 9px;
        }

        .warning-title {
            font-weight: bold;
            color: #7b341e;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        @if($empresa)
            <div class="company-name">{{ $empresa->razon_social }}</div>
            <div class="report-subtitle">{{ $empresa->razon_social }}</div>
        @else
            <div class="company-name">{{ config('app.name') }}</div>
        @endif

        <div class="report-title">CUADRE DE CAJA</div>
        <div class="report-subtitle">
            Período: {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }} -
            {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}
        </div>
        <div class="report-subtitle">
            Generado el {{ $fechaGeneracion->format('d/m/Y H:i:s') }} por {{ $usuario->name }}
        </div>
    </div>

    <!-- Información del Reporte -->
    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell info-label">Fecha Desde:</div>
            <div class="info-cell">{{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }}</div>
            <div class="info-cell info-label">Fecha Hasta:</div>
            <div class="info-cell">{{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell info-label">Total Movimientos:</div>
            <div class="info-cell">{{ number_format($totales['totalMovimientos']) }}</div>
            <div class="info-cell info-label">Usuario:</div>
            <div class="info-cell">{{ $usuario->name }}</div>
        </div>
    </div>

    <!-- Nota explicativa -->
    <div class="warning-note">
        <div class="warning-title">EXPLICACIÓN DEL CUADRE DE CAJA:</div>
        <p><strong>SUMAN a la caja:</strong> Empeños y Cuotas pagadas</p>
        <p><strong>RESTAN de la caja:</strong> Desempeños y Documentos Equivalentes</p>
        <p>El <strong>Total Neto en Caja</strong> muestra cuánto dinero efectivo queda después de todos los movimientos.</p>
    </div>

    <!-- Filtros Aplicados -->
    @if(count($filtrosAplicados) > 0)
        <div class="filters-section">
            <div class="filters-title">Filtros Aplicados:</div>
            @foreach($filtrosAplicados as $filtro)
                <div>• {{ $filtro }}</div>
            @endforeach
        </div>
    @endif

    <!-- Totales Principales -->
    <div class="totals-section">
        <div class="totals-grid">
            <div class="totals-row">
                <div class="totals-cell total-neto">
                    TOTAL NETO EN CAJA<br>
                    ${{ number_format($totales['totalMontoNeto'], 0, ',', '.') }}
                </div>
                <div class="totals-cell">
                    Total Movimientos<br>
                    {{ number_format($totales['totalMovimientos']) }}
                </div>
                <div class="totals-cell">
                    Total Ingresos<br>
                    ${{ number_format($totales['totalIngresos'], 0, ',', '.') }}
                </div>
                <div class="totals-cell">
                    Total Egresos<br>
                    ${{ number_format($totales['totalEgresos'], 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Movimientos -->
    @if($movimientos->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 12%;">Fecha/Hora</th>
                    <th style="width: 10%;">Tipo</th>
                    <th style="width: 12%;">Contrato/Doc</th>
                    <th style="width: 20%;">Cliente</th>
                    <th style="width: 15%;">Empresa</th>
                    <th style="width: 15%;">Productos/Concepto</th>
                    <th style="width: 8%;">Monto</th>
                    <th style="width: 8%;">Impacto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movimientos as $movimiento)
                    <tr>
                        <!-- Fecha/Hora -->
                        <td class="text-center">
                            <div class="font-bold">{{ $movimiento->created_at->format('d/m/Y') }}</div>
                            <div>{{ $movimiento->created_at->format('H:i:s') }}</div>
                        </td>

                        <!-- Tipo -->
                        <td class="text-center">
                            @switch($movimiento->tipo_registro)
                                @case('empeno')
                                    <span class="status-badge badge-empeno">Empeño</span>
                                    @break
                                @case('cuota')
                                    <span class="status-badge badge-cuota">Cuota</span>
                                    @break
                                @case('desempeno')
                                    <span class="status-badge badge-desempeno">Desempeño</span>
                                    @break
                                @case('documento_equivalente')
                                    <span class="status-badge badge-documento">Doc. Equiv.</span>
                                    @break
                            @endswitch
                        </td>

                        <!-- Contrato/Documento -->
                        <td class="text-center">
                            <div class="font-bold">{{ $movimiento->numero_contrato ?? 'N/A' }}</div>
                            @if($movimiento->tipo_registro === 'cuota')
                                <div style="font-size: 7px;">ID: {{ $movimiento->id }}</div>
                            @endif
                        </td>

                        <!-- Cliente -->
                        <td>
                            @if($movimiento->cliente)
                                <div class="font-bold">
                                    {{ $movimiento->cliente->razon_social ?:
                                       trim($movimiento->cliente->nombres . ' ' . $movimiento->cliente->apellidos) }}
                                </div>
                                <div>{{ $movimiento->cliente->cedula_nit }}</div>
                            @else
                                <span>Sin cliente</span>
                            @endif
                        </td>

                        <!-- Empresa -->
                        <td>
                            @if($movimiento->empresa)
                                <div class="font-bold">{{ $movimiento->empresa->razon_social }}</div>
                                <div style="font-size: 7px;">{{ $movimiento->empresa->razon_social }}</div>
                            @else
                                <span>Sin empresa</span>
                            @endif
                        </td>

                        <!-- Productos/Concepto -->
                        <td>
                            @if($movimiento->tipo_registro === 'documento_equivalente')
                                <!-- Mostrar concepto para documentos equivalentes -->
                                <div style="margin-bottom: 2px;">
                                    <span class="status-badge breakdown-no-oro">{{ $movimiento->concepto_nombre ?? 'Sin concepto' }}</span>
                                    <div style="font-size: 7px;">Documento Equivalente</div>
                                </div>
                            @elseif($movimiento->productos && $movimiento->productos->count() > 0)
                                <!-- Mostrar productos para empeños, desempeños y cuotas -->
                                @foreach($movimiento->productos->take(2) as $producto)
                                    <div style="margin-bottom: 2px;">
                                        @if($producto->producto->tipoOro)
                                            <span class="status-badge breakdown-oro">{{ $producto->producto->tipoOro->nombre }}</span>
                                        @elseif($producto->producto->tipoProducto)
                                            <span class="status-badge breakdown-no-oro">{{ $producto->producto->tipoProducto->nombre }}</span>
                                        @endif
                                        <div style="font-size: 7px;">{{ substr($producto->producto->descripcion, 0, 30) }}{{ strlen($producto->producto->descripcion) > 30 ? '...' : '' }}</div>
                                    </div>
                                @endforeach
                                @if($movimiento->productos->count() > 2)
                                    <div style="font-size: 7px;">+{{ $movimiento->productos->count() - 2 }} más</div>
                                @endif
                            @else
                                <span style="font-size: 7px;">Sin productos</span>
                            @endif
                        </td>

                        <!-- Monto -->
                        <td class="text-right font-bold">
                            ${{ number_format($movimiento->monto ?? 0, 0, ',', '.') }}
                        </td>

                        <!-- Impacto en Caja -->
                        <td class="text-center">
                            @if($movimiento->signo_movimiento === 'suma')
                                <span class="status-badge impact-suma">+SUMA</span>
                            @else
                                <span class="status-badge impact-resta">-RESTA</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 20px; background-color: #f7fafc; border: 1px solid #e2e8f0; border-radius: 4px;">
            <strong>No se encontraron movimientos para los filtros seleccionados</strong>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div>{{ config('app.name') }} - Cuadre de Caja</div>
        <div>Generado el {{ $fechaGeneracion->format('d/m/Y H:i:s') }}</div>
    </div>
</body>
</html>
