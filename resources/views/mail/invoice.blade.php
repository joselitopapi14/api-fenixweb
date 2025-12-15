<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Electrónica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 24px;
        }
        .empresa-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .empresa-info h2 {
            color: #007bff;
            margin-top: 0;
            font-size: 18px;
        }
        .factura-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
        }
        .info-box h3 {
            margin-top: 0;
            color: #495057;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .info-box p {
            margin: 5px 0;
            font-size: 14px;
        }
        .mensaje {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .mensaje p {
            margin: 0;
            color: #155724;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 12px;
        }
        .destacado {
            color: #007bff;
            font-weight: bold;
        }
        @media (max-width: 600px) {
            .factura-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Factura Electrónica</h1>
            <p>Documento enviado exitosamente a la DIAN</p>
        </div>

        <!-- Empresa Info -->
        <div class="empresa-info">
            <h2>{{ $empresa->name }}</h2>
            <p><strong>NIT:</strong> {{ $empresa->nit }}-{{ $empresa->dv }}</p>
            @if($empresa->address)
                <p><strong>Dirección:</strong> {{ $empresa->address }}</p>
            @endif
            @if($empresa->phone)
                <p><strong>Teléfono:</strong> {{ $empresa->phone }}</p>
            @endif
            @if($empresa->email)
                <p><strong>Email:</strong> {{ $empresa->email }}</p>
            @endif
        </div>

        <!-- Factura Info -->
        <div class="factura-info">
            <div class="info-box">
                <h3>Información de la Factura</h3>
                <p><strong>Número:</strong> <span class="destacado">{{ $factura->numero_factura }}</span></p>
                <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($factura->issue_date)->format('d/m/Y') }}</p>
                <p><strong>Hora:</strong> {{ $factura->created_at->format('H:i:s') }}</p>
                <p><strong>Total:</strong> <span class="destacado">${{ number_format($factura->total, 0, ',', '.') }}</span></p>
                @if($factura->cufe)
                    <p><strong>CUFE:</strong> <small>{{ $factura->cufe }}</small></p>
                @endif
            </div>

            <div class="info-box">
                <h3>Información del Cliente</h3>
                <p><strong>Nombre:</strong> {{ $cliente->nombre_completo ?? $cliente->name }}</p>
                <p><strong>Documento:</strong> {{ $cliente->documento ?? $cliente->document }}</p>
                @if($cliente->telefono ?? $cliente->phone)
                    <p><strong>Teléfono:</strong> {{ $cliente->telefono ?? $cliente->phone }}</p>
                @endif
                <p><strong>Email:</strong> {{ $cliente->email }}</p>
            </div>
        </div>

        <!-- Mensaje principal -->
        <div class="mensaje">
            <p>
                <strong>¡Estimado cliente!</strong><br>
                Adjunto a este correo encontrará su factura electrónica No. <strong>{{ $factura->numero_factura }}</strong>
                en formato PDF junto con el XML oficial que ha sido enviado y validado exitosamente ante la DIAN.
            </p>
        </div>

        <!-- Información adicional -->
        <div style="margin: 20px 0;">
            <h3 style="color: #495057;">Información importante:</h3>
            <ul style="color: #6c757d; font-size: 14px;">
                <li>Este documento tiene plena validez legal como factura electrónica</li>
                <li>El archivo ZIP adjunto contiene tanto el PDF como el XML oficial</li>
                <li>Conserve estos documentos para sus registros contables</li>
                @if($factura->cufe)
                    <li>Puede verificar la autenticidad del documento en el portal de la DIAN usando el CUFE</li>
                @endif
            </ul>
        </div>

        <!-- Verificación DIAN -->
        @if($factura->cufe)
            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0;">
                <h4 style="margin-top: 0; color: #856404;">Verificación en línea</h4>
                <p style="margin: 0; color: #856404; font-size: 14px;">
                    Puede verificar este documento directamente en el portal de la DIAN visitando:<br>
                    @if($empresa->tipo_ambiente == 1)
                        <a href="https://catalogo-vpfe.dian.gov.co/Document/ShowDocumentToPublic/{{ $factura->cufe }}"
                           style="color: #007bff; text-decoration: none;"
                           target="_blank">
                            Ver documento en DIAN
                        </a>
                    @else
                        <a href="https://catalogo-vpfe-hab.dian.gov.co/Document/ShowDocumentToPublic/{{ $factura->cufe }}"
                           style="color: #007bff; text-decoration: none;"
                           target="_blank">
                            Ver documento en DIAN (Ambiente de pruebas)
                        </a>
                    @endif
                </p>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Este correo ha sido generado automáticamente por el sistema de facturación electrónica.</p>
            <p>Para consultas o soporte, contáctese con {{ $empresa->name }}</p>
            @if($empresa->phone)
                <p>Teléfono: {{ $empresa->phone }}</p>
            @endif
            @if($empresa->email)
                <p>Email: {{ $empresa->email }}</p>
            @endif
            <hr style="margin: 15px 0; border: none; border-top: 1px solid #dee2e6;">
            <p style="color: #adb5bd;">Software desarrollado por: www.fenixbgsas.com</p>
        </div>
    </div>
</body>
</html>
