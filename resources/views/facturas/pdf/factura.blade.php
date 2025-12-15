<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Factura {{ $factura->numero_factura ?? '' }}</title>
</head>

<body>
    @php
    // Generar el logo de la empresa en base64 desde archivo local
    $logoBase64 = '';
    if ($factura->empresa->logo) {
        try {
            // Construir la ruta completa del archivo desde storage
            $logoPath = storage_path('app/public/' . $factura->empresa->logo);

            // Verificar si el archivo existe y leerlo
            if (file_exists($logoPath)) {
                $imageData = file_get_contents($logoPath);
                if ($imageData !== false) {
                    // Obtener el tipo de imagen desde la ruta del archivo
                    $imageType = 'png'; // default
                    $urlInfo = pathinfo($logoPath);
                    if (isset($urlInfo['extension'])) {
                        $imageType = strtolower($urlInfo['extension']);
                        // Convertir jpg a jpeg para el MIME type
                        if ($imageType === 'jpg') {
                            $imageType = 'jpeg';
                        }
                    }
                    $logoBase64 = 'data:image/' . $imageType . ';base64,' . base64_encode($imageData);
                }
            }
        } catch (\Exception $e) {
            // Si hay error leyendo la imagen, usar cadena vacía
            $logoBase64 = '';
        }
    }
    @endphp

<style>
/* Reset y configuración base */
* {
    font-family: 'Times New Roman', Times, serif;
    padding: 0;
    margin: 0;
    box-sizing: border-box;
}

@page {
    margin-top: 136px !important;
    margin-bottom: 40px !important;
}

body {
    position: relative;
    padding: 10px;
}

/* Marca de agua del logo */
body::before {
    content: "";
    position: absolute;
    top: -150px;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0.15;
    z-index: -1;
    background-image: url("{{ $logoBase64 }}");
    background-repeat: no-repeat;
    background-position: center;
    background-size: 600px;
}

/* Tabla principal del header */
.invoice-header {
    position: fixed;
    top: -132px;
    left: 0cm;
    right: 0cm;
    width: 100%;
    padding: 0 16px;
}

/* Contenedor del logo */
.company-logo {
    height: 100px;
}

.company-logo img {
    display: block;
    height: 100%;
    max-width: 150px;
    opacity: 0.9;
    margin: 0 auto;
}

/* Información de la empresa */
.company-info {
    font-size: 14px;
    text-align: center;
    padding: 0 8px;
}

.company-info h4 {
    margin: 0;
}

.company-info .sub-header {
    font-size: 13px;
}

.company-info .dian-authorization {
    font-size: 10.5px;
    margin-top: 5px;
}

/* Información de la factura */
.invoice-details {
    text-align: center;
    border: 1px solid black;
    font-size: 14px;
    padding: 5px;
}

.invoice-details h3 {
    margin: 0;
}

/* Fechas de la factura */
.invoice-dates {
    margin-top: 5px;
}

.date-row {
    position: relative;
    font-size: 13px;
    margin-bottom: 2px;
}

.date-value {
    position: absolute;
    right: 10px;
    width: 1.7cm;
}

/* Reset y configuración de tablas */
table {
    border-collapse: collapse;
    width: 100%;
}

/* Espaciado de secciones */
.client-info,
.invoice-main,
.invoice-summary {
    margin-bottom: 5px;
}

/* Información del cliente */
.client-info td {
    border: 1px solid black;
    padding: 5px;
    font-size: 13px;
}

/* Encabezado de productos */
.invoice-main thead td {
    border: 1px solid black;
    font-size: 12.5px;
    padding: 0 4px;
    font-weight: bold;
}

/* Cuerpo de productos */
.invoice-main tbody td {
    font-size: 12px;
    padding: 5px;
    border-right: 1px solid black;
    line-height: 1;
    vertical-align: top;
}

/* Pie de tabla de productos */
.invoice-main tfoot td {
    border: 1px solid black;
    font-size: 12.5px;
}

/* Detalles finales */
.invoice-details-final td {
    vertical-align: top;
    border: 1px solid black;
    padding: 5px;
    font-size: 12px;
    height: 80px;
}

/* Elementos de texto */
h4, p, h3 {
    margin: 0;
}

table, tr, td {
    width: 100%;
}

.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

.font-numeric {
    font-variant-numeric: tabular-nums;
}

/* Footer fijo en la parte inferior */
.invoice-footer {
    position: fixed;
    bottom: 0;
    left: 0cm;
    right: 0cm;
    width: 100%;
    font-size: 10px;
    background: #ffffff;
    border-top: 1px solid #000000;
    padding: 10px 0;
}

.footer-container {
    width: 100%;
    margin: 0 auto;
    padding: 0 15px;
}

.footer-row {
    display: table;
    width: 100%;
    table-layout: fixed;
}

.footer-col {
    display: table-cell;
    vertical-align: top;
    padding: 5px 10px;
}

.footer-col-left {
    width: 35%;
    border-right: 1px solid #000000;
}

.footer-col-center {
    width: 35%;
    border-right: 1px solid #000000;
}

.footer-col-right {
    width: 30%;
}

.footer-title {
    font-size: 11px;
    font-weight: bold;
    color: #000000;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.footer-content {
    font-size: 9px;
    line-height: 1.3;
    color: #000000;
}

.footer-content p {
    margin: 2px 0;
}

.footer-highlight {
    background-color: #ffffff;
    padding: 3px 5px;
    border-radius: 3px;
    border-left: 3px solid #000000;
    margin: 3px 0;
}

.footer-code {
    font-family: 'Courier New', monospace;
    font-size: 8px;
    background-color: #ffffff;
    padding: 2px 4px;
    border-radius: 2px;
    border: 1px solid #000000;
    word-break: break-all;
}

.footer-contact-item {
    display: block;
    margin-bottom: 2px;
}

.footer-icon {
    display: inline-block;
    width: 8px;
    height: 8px;
    margin-right: 4px;
    vertical-align: middle;
}

.footer-bottom {
    text-align: center;
    margin-top: 8px;
    padding-top: 5px;
    border-top: 1px solid #000000;
    font-size: 8px;
    color: #000000;
}

/* Iconos simples con CSS - Solo texto */
.icon-bullet::before { content: "• "; color: #000000; font-weight: bold; }
.icon-arrow::before { content: "▶ "; color: #000000; font-size: 8px; }
</style>

<!-- HEADER -->
<table class="invoice-header">
    <tr>
        <td style="width: 20%">
            <div class="company-logo">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo de la empresa" style="max-width: 100%; max-height: 100px; display: block; margin: 0 auto; object-fit: contain;">
                @endif
            </div>
        </td>

        <td style="width: 50%">
            <div class="company-info">
                @if($factura->empresa->razon_social)
                    <h4><strong>{{ strtoupper($factura->empresa->razon_social) }}</strong></h4>
                @endif

                @if($factura->empresa->legal_representative)
                    <h4><strong class="sub-header">{{ strtoupper($factura->empresa->legal_representative) }}</strong></h4>
                @endif

                @if($factura->empresa->nit && $factura->empresa->dv)
                    <h4><strong class="sub-header">{{ $factura->empresa->nit }}-{{ $factura->empresa->dv }}</strong></h4>
                @endif

                @if($factura->empresa->direccion)
                    <h4><strong class="sub-header">{{ strtoupper($factura->empresa->direccion) }}</strong></h4>
                @endif

                @if(($factura->empresa->telefono_fijo || $factura->empresa->celular) || $factura->empresa->email)
                    <h4><strong class="sub-header">
                        @if($factura->empresa->telefono_fijo || $factura->empresa->celular)Tel: {{ $factura->empresa->telefono_fijo ?: $factura->empresa->celular }}@endif
                        @if(($factura->empresa->telefono_fijo || $factura->empresa->celular) && $factura->empresa->email), @endif
                        @if($factura->empresa->email){{ $factura->empresa->email }}@endif
                    </strong></h4>
                @endif

                @if($factura->tipoMovimiento && $factura->tipoMovimiento->resolucion)
                    <p class="dian-authorization">
                        Autorización de Factura Electrónica DIAN #{{ $factura->tipoMovimiento->resolucion }}
                        @if($factura->tipoMovimiento->fecha_resolucion) del {{ $factura->tipoMovimiento->fecha_resolucion }}@endif
                        @if($factura->tipoMovimiento->consecutivo_inicial && $factura->tipoMovimiento->consecutivo_final)
                            <br>Desde {{ $factura->tipoMovimiento->prefijo }}{{ $factura->tipoMovimiento->consecutivo_inicial }}
                            Hasta {{ $factura->tipoMovimiento->prefijo }}{{ $factura->tipoMovimiento->consecutivo_final }}
                        @endif
                        @if($factura->tipoMovimiento->fecha_inicial && $factura->tipoMovimiento->fecha_final)
                            <br>Vigencia desde {{ $factura->tipoMovimiento->fecha_inicial }} hasta {{ $factura->tipoMovimiento->fecha_final }}
                        @endif
                    </p>
                @endif
            </div>
        </td>

        <td style="width: 30%">
            <div class="invoice-details">
                @if($factura->numero_factura)
                    <h3>{{ strtoupper($factura->tipoFactura->name ?? 'FACTURA') }} N°: <br>
                        <strong>{{ strtoupper($factura->numero_factura) }}</strong>
                    </h3>
                @endif
            </div>

            <div class="invoice-dates">
                <div class="date-row">
                    Fecha de emisión:
                    <span class="date-value">{{ $factura->issue_date ? date('Y-m-d', strtotime($factura->issue_date)) : date('Y-m-d', strtotime($factura->created_at)) }}</span>
                </div>

                <div class="date-row">
                    Hora:
                    <span class="date-value">{{ $factura->issue_date ? date('H:i:s', strtotime($factura->issue_date)) : date('H:i:s', strtotime($factura->created_at)) }}</span>
                </div>

                <div class="date-row">
                    Fecha de Vencimiento:
                    <span class="date-value">{{ $factura->due_date ? date('Y-m-d', strtotime($factura->due_date)) : 'N/A' }}</span>
                </div>
            </div>
        </td>
    </tr>
</table>

<!-- BODY -->
<table class="client-info">
    <tr>
        <td>
            <p style="position: relative; font-size: 13px; height:40px"><strong>Cliente:
                    <span style="position: absolute; left: 60;">
                        @if($factura->cliente)
                            {{ strtoupper($factura->cliente->nombre_completo) }}
                        @endif
                    </span></strong>
            </p>
            <p style="position: relative; font-size: 13px"><strong>NIT:
                    <span style="position: absolute; left: 60;">
                        @if($factura->cliente)
                            {{ $factura->cliente->cedula_nit }}
                            @if($factura->cliente->dv)
                                -{{ $factura->cliente->dv }}
                            @endif
                        @endif
                    </span></strong>
            </p>
            <p style="position: relative; font-size: 13px">Dirección:
                <span style="position: absolute; left: 60;">
                    @if($factura->cliente && $factura->cliente->direccion)
                        {{ strtoupper($factura->cliente->direccion) }}
                    @endif
                </span>
            </p>
            <br>
            <p style="position: relative; font-size: 13px">Ciudad:
                <span style="position: absolute; left: 60;">
                    @if($factura->cliente && $factura->cliente->municipio)
                        {{ strtoupper($factura->cliente->municipio->name) }}
                        @if($factura->cliente->departamento)
                            - {{ strtoupper($factura->cliente->departamento->name) }}
                        @endif
                    @endif
                </span>
            </p>
            <p style="position: relative; font-size: 13px">Teléfono:
                <span style="position: absolute; left: 60;">
                    @if($factura->cliente && ($factura->cliente->telefono_fijo || $factura->cliente->celular))
                        {{ $factura->cliente->telefono_fijo ?: $factura->cliente->celular }}
                    @endif
                </span>
            </p>
        </td>
        <td style="position: absolute; vertical-align: top;">
            <div style="width: 60%; vertical-align: top;">
                <p style="font-size: 13px;">Orden de pedido:
                    <span>{{ $factura->orden_pedido ?? 'N/A' }}</span>
                </p>
                <p style="font-size: 13px;">Orden Compra/Serv:
                    <span>{{ $factura->orden_compra ?? 'N/A' }}</span>
                </p>
                <p style="font-size: 13px;">Vendedor:
                    <span>
                        @if($factura->vendedor)
                            {{ $factura->vendedor->name }}
                        @elseif($factura->user)
                            {{ $factura->user->name }}
                        @endif
                    </span>
                </p>
                <p style="font-size: 13px;">Forma de Pago:
                    <span>
                        @if($factura->tipoPago)
                            {{ $factura->tipoPago->name }}
                        @else
                            Contado
                        @endif
                    </span>
                </p>
                @if($factura->cufe)
                    <p style="position: relative; font-size: 13px; height:36px">CUFE:
                        <span style="position: absolute; left: 30; font-size: 11px; max-width: 170px; word-wrap: break-word;">
                            {{ $factura->cufe }}
                        </span>
                    </p>
                @endif
            </div>

            @if($factura->cufe && $qrCodeImage)
                <div style="position:absolute; right: 6; height: 100px; width: 100px; background-color: black; top: 5">
                    <img src="data:image/png;base64,{{ $qrCodeImage }}" alt="QR Code" style="width: 100%; height: 100%;">
                </div>
            @endif
        </td>
    </tr>
</table>

<table class="invoice-main" style="margin-bottom:0">
    <thead>
        <tr style="height: 20px">
            <td style="width: 2.1cm">Referencia</td>
            <td style="width: 5.4cm">Descripción Producto/Servicio</td>
            <td style="width: 1.2cm">Presentación</td>
            <td style="width: 1.0cm; text-align:center">Cantidad</td>
            <td style="width: 1.2cm; text-align:center">Desc/Rec</td>
            <td style="width: 1.0cm; text-align:center">%IMP</td>
            <td style="width: 2.0cm; text-align: right">V. Unitario</td>
            <td style="width: 2.5cm; text-align: right">Subtotal</td>
        </tr>
    </thead>
    <tbody style="border: 1px solid black; min-height:400px;" class="font-numeric">
        @foreach($factura->facturaHasProducts as $facturaProducto)
            <tr>
                <td>{{ $facturaProducto->producto->codigo ?? 'N/A' }}</td>
                <td>{{ $facturaProducto->producto->nombre ?? 'Producto sin nombre' }}</td>
                <td class="text-center">
                    @if($facturaProducto->producto->unidadMedida)
                        {{ $facturaProducto->producto->unidadMedida->nombre }}
                    @else
                        Und.
                    @endif
                </td>
                <td class="text-center">{{ $facturaProducto->cantidad }}</td>
                <td class="text-center">
                    @if($facturaProducto->descuento > 0)
                        -{{ number_format($facturaProducto->descuento, 0, ',', '.') }}
                    @elseif($facturaProducto->recargo > 0)
                        +{{ number_format($facturaProducto->recargo, 0, ',', '.') }}
                    @else
                        0
                    @endif
                </td>
                <td class="text-center">
                    @php
                        $totalTaxRate = 0;
                        if($facturaProducto->producto->impuestos) {
                            foreach($facturaProducto->producto->impuestos as $impuesto) {
                                if($impuesto->impuesto) {
                                    $totalTaxRate += $impuesto->porcentaje;
                                }
                            }
                        }
                    @endphp
                    {{ $totalTaxRate }}
                </td>
                <td class="text-right">{{ number_format($facturaProducto->precio_unitario, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($facturaProducto->subtotal, 2, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="client-info">
    <tr style="padding-right: 20px; font-size:14px">
        <td colspan="3" rowspan="2" style="padding-right: 20px; font-size:12px; padding: 5px; vertical-align: top; position:relative; width: 300px;">
            <h4><strong>OBSERVACIÓN: </strong> {{ $factura->observaciones ?? 'Sin observaciones' }}</h4>
            <br>
            <h4><strong>SON:</strong></h4>
        </td>
        <td colspan="3" style="padding-right: 20px; font-size:14px; text-align:right;" class="font-numeric">
            <p><strong>Sub Total:</strong> </p>
            <p><strong>Descuento/Recargo:</strong> </p>
            <p><strong>Impuesto:</strong> </p>
            <p><strong>Flete:</strong> </p>
            <p><strong>Total Factura:</strong> </p>
        </td>
        <td colspan="2" style="padding-right: 20px; font-size:14px; text-align:right; letter-spacing: 0.2px;">
            <!-- Subtotal -->
            <p><strong>{{ number_format($factura->subtotal, 2, ',', '.') }}</strong> </p>
            <!-- Descuento/Recargo -->
            @php
                $totalDescuento = 0;
                $totalRecargo = 0;
                foreach($factura->facturaHasProducts as $producto) {
                    if($producto->descuento > 0) {
                        $totalDescuento += $producto->descuento;
                    }
                    if($producto->recargo > 0) {
                        $totalRecargo += $producto->recargo;
                    }
                }
                $totalDescuentoRecargo = $totalRecargo - $totalDescuento;
            @endphp
            <p><strong>
                @if($totalDescuentoRecargo > 0)
                    +{{ number_format($totalDescuentoRecargo, 2, ',', '.') }}
                @elseif($totalDescuentoRecargo < 0)
                    {{ number_format($totalDescuentoRecargo, 2, ',', '.') }}
                @else
                    {{ number_format(0.0, 2, ',', '.') }}
                @endif
            </strong> </p>
            <!-- Impuesto -->
            <p><strong>{{ number_format($factura->valor_impuestos, 2, ',', '.') }}</strong> </p>
            <!-- Flete -->
            <p><strong>{{ number_format(0.0, 2, ',', '.') }}</strong> </p>
            <!-- Total Factura -->
            <p><strong>{{ number_format($factura->total, 2, ',', '.') }}</strong> </p>
        </td>
    </tr>
    <tr>
        <td colspan="3" style="padding-right: 20px; font-size:14px; text-align:right">
            <p><strong>ReteFuente:</strong> </p>
            <p><strong>ReteIVA:</strong> </p>
            <p><strong>ReteICA:</strong> </p>
        </td>
        <td colspan="2" style="padding-right: 20px; font-size:14px; text-align:right; letter-spacing: 0.2px;" class="font-numeric">
            @php
                // Calcular retenciones por tipo
                $reteFuente = 0;
                $reteIVA = 0;
                $reteICA = 0;
                $totalRetenciones = 0;

                if ($factura->facturaHasRetenciones && $factura->facturaHasRetenciones->count() > 0) {
                    foreach ($factura->facturaHasRetenciones as $retencion) {
                        $valorRetencion = floatval($retencion->valor);
                        $totalRetenciones += $valorRetencion;

                        // Clasificar por tipo de retención (código)
                        if ($retencion->tipoRetencion) {
                            switch ($retencion->tipoRetencion->code) {
                                case '06': // ReteFuente
                                    $reteFuente += $valorRetencion;
                                    break;
                                case '05': // ReteIVA
                                    $reteIVA += $valorRetencion;
                                    break;
                                case '07': // ReteICA
                                    $reteICA += $valorRetencion;
                                    break;
                            }
                        }
                    }
                }

                // Calcular total a pagar (total factura - total retenciones)
                $totalAPagar = $factura->total - $totalRetenciones;
            @endphp
            <p><strong>{{ number_format($reteFuente, 2, ',', '.') }}</strong> </p>
            <p><strong>{{ number_format($reteIVA, 2, ',', '.') }}</strong> </p>
            <p><strong>{{ number_format($reteICA, 2, ',', '.') }}</strong> </p>
        </td>
    </tr>
</table>

<table class="invoice-details-final">
    <tr>
        <td>
            <p style="position: relative; font-size: 13px; height:40px"><strong></strong> </p>
        </td>
        <td>
            <p style="position: relative; font-size: 13px "><strong>Medios de Pago:</strong> </p>
            <span style=" font-size: 13px">
                @if($factura->medioPago)
                    {{ $factura->medioPago->name }}
                @else
                    Acuerdo mutuo
                @endif
            </span>
        </td>
        <td>
            <p><strong>Detalles impuestos</strong> </p>
            <div>
                <span style="width: 35%; display: block; float: left; text-align: right">
                    <h4 style="font-weight: normal">Base</h4>
                    <span>{{ number_format($factura->subtotal, 2, ',', '.') }}</span>
                </span>
                <span style="width: 20%; display: block; float: left; text-align: right">
                    <h4>%</h4>
                    <span>
                        @php
                            $totalTaxPercentage = 0;
                            if($factura->subtotal > 0 && $factura->valor_impuestos > 0) {
                                $totalTaxPercentage = round(($factura->valor_impuestos / $factura->subtotal) * 100, 1);
                            }
                        @endphp
                        {{ $totalTaxPercentage }}
                    </span>
                </span>
                <span style="width: 35%; display: block; float: left; text-align: right">
                    <h4>Impuesto</h4>
                    <span>{{ number_format($factura->valor_impuestos, 2, ',', '.') }}</span>
                </span>
                <div style="clear: both;"></div>
            </div>
        </td>
    </tr>
</table>

<table class="client-info">
    <tr>
        <td style="width:35%; vertical-align: top">
            <p style="position: relative; font-size: 9px ">La Factura De Venta, Tiene Carácter De Titulo Valor. (ley
                1231 De 2008; Artículos 772 - 773 - 774 - 777 - 778 - 779 Código De Comercio). El Comprador Y Aceptante
                Declara Que Recibió Real, Material Y A Conformidad La Mercancía Y/o Servicio Descrito En Este Título
                Valor.
            </p>
        </td>
        <td style="width:27.5%; vertical-align: top">
            <p style="position: relative; font-size: 12px"><strong>Aceptado:</strong> </p>
        </td>
        <td style="width:27.5%; vertical-align: top">
            <p style="position: relative; font-size: 12px; height: 30px"><strong>Recibido:</strong> </p>
            <p style="position: relative; font-size: 12px; height: 20px">Firma: </p>
            <p style="position: relative; font-size: 12px;">Nombre: </p>
            <p style="position: relative; font-size: 12px;">C.C:</p>
            <p style="position: relative; font-size: 12px;">Fecha:</p>
        </td>
    </tr>
</table>

<!-- FOOTER -->
<div class="invoice-footer">
    <div class="footer-container">
        <div class="footer-row">
            <div class="footer-col footer-col-left">
                <div class="footer-title">
                    DOCUMENTO ELECTRÓNICO
                </div>
                <div class="footer-content">
                    <div class="footer-highlight">
                        <strong>Representación Gráfica de la {{$factura->tipoFactura->name ?? 'Factura'}}</strong>
                    </div>
                    <p>
                        <strong>Fecha y Hora Aceptación:</strong><br>
                        {{ $factura->updated_at ? $factura->updated_at->format('d/m/Y H:i:s') : date('d/m/Y H:i:s') }}
                    </p>
                </div>
            </div>

            <div class="footer-col footer-col-center">
                <div class="footer-title">
                    INFORMACIÓN EMPRESA
                </div>
                <div class="footer-content">
                    <p><strong>{{ $factura->empresa->razon_social }}</strong></p>
                    <p><strong>NIT:</strong> {{ $factura->empresa->nit }}-{{ $factura->empresa->dv }}</p>
                    @if($factura->empresa->regimen)
                        <p><strong>Régimen:</strong> {{ $factura->empresa->regimen }}</p>
                    @endif
                    @if($factura->empresa->actividad_economica)
                        <p><strong>Actividad:</strong> {{ $factura->empresa->actividad_economica }}</p>
                    @endif
                </div>
            </div>

            <div class="footer-col footer-col-right">
                <div class="footer-title">
                    INFORMACIÓN CONTACTO
                </div>
                <div class="footer-content">
                    @if($factura->empresa->direccion)
                        <p class="footer-contact-item">
                            <span class="icon-bullet"></span>{{ $factura->empresa->direccion }}
                        </p>
                    @endif
                    @if($factura->empresa->telefono_fijo || $factura->empresa->celular)
                        <p class="footer-contact-item">
                            <span class="icon-bullet"></span>{{ $factura->empresa->telefono_fijo ?: $factura->empresa->celular }}
                        </p>
                    @endif
                    @if($factura->empresa->email)
                        <p class="footer-contact-item">
                            <span class="icon-bullet"></span>{{ $factura->empresa->email }}
                        </p>
                    @endif
                    @if($factura->empresa->website)
                        <p class="footer-contact-item">
                            <span class="icon-bullet"></span>{{ $factura->empresa->website }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            Este documento constituye título valor y se encuentra bajo las regulaciones establecidas por la DIAN
        </div>
    </div>
</div>

</body>

</html>
