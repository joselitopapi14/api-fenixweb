<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>{{ $boleta->numero_contrato ?? '' }}</title>
  <style>
    /* --- DOMPDF: 1 sola página A5 landscape --- */
    @page { size: A5 landscape; margin: 5mm; }
    html, body { margin:0; padding:0; }
    body { font-family: DejaVu Sans, Arial, sans-serif; color:#111; font-size:8.4pt; line-height:1.2; }
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    .marco { border:0.5mm solid #184A8C; padding:3mm; }
    .azul { color:#184A8C; }
    .rojo { color:#C32020; }
    .caps { text-transform: uppercase; }
    .t-centrado { text-align:center; }
    .t-der { text-align:right; }

    table { border-collapse:collapse; width:100%; page-break-inside: avoid; }
    .sp { height:1mm; }

    .tit { font-size:13pt; font-weight:700; }
    .h1 { font-size:9.5pt; font-weight:700; }
    .h2 { font-size:8.6pt; font-weight:700; }

    .folio { font-size:11pt; font-weight:700; color:#C32020; }
    .precioLbl { font-size:7.6pt; font-weight:700; color:#184A8C; text-align:right; }
    .precioBox { border:0.5mm solid #184A8C; padding:1.6mm; font-weight:700; text-align:center; }

    .bloque { border:0.2mm solid #d9d9d9; padding:1.6mm; }
    .lbl { color:#333; }
    .linea { display:inline-block; min-width:10mm; border-bottom:0.3mm solid #184A8C; }
    .precioTexto { font-weight:600; margin-top:1.6mm; }

    p { margin:1.6mm 0; }

    .legal { font-size:6.6pt; text-align:justify; margin:1mm 0; }
    .nota { font-size:8.6pt; font-weight:700; text-align:center; color:#C32020; padding:0.6mm 0; }

    /* QR en la columna derecha debajo del precio */
    .qrbox { border:0.2mm solid #ddd; width:18mm; height:18mm; text-align:center; margin-top:2mm; }
    .qrtext { font-size:6pt; color:#666; text-align:center; }
  </style>
</head>
<body>
  <div class="marco">

    <!-- ENCABEZADO -->
    <table>
      <tr>
        <td style="width:68%; padding-right:3mm;">
          <div class="tit azul caps">{{ $titulo_compraventa ?? 'Compraventa' }}</div>
          <div>{{ $vendedor ?? ($boleta->empresa->razon_social ?? '') }}</div>
          <div>{{ $vendedor_id ?? ($boleta->empresa->nit ?? '') }} - {{ $boleta->empresa->dv ?? '' }}</div>
          <div>
            {{ $boleta->empresa->representante_legal ?? '' }} - {{ $boleta->empresa->cedula_representante ?? '' }}
            {{ $direccion ?? '' }}
            {{ $telefonos ?? ($boleta->empresa->telefono_fijo ?? '') }}
            {{ $boleta->empresa->celular ? ' / ' . $boleta->empresa->celular : '' }}
          </div>
          <div class="sp"></div>
          <div class="h2 azul">{{ $encabezado_contrato ?? 'Contrato de Compraventa con pacto de Retroventa' }}</div>
          <div class="h1 azul caps">PLAZO {{ $plazo ?? '' }}</div>
        </td>

        <!-- Columna derecha: Folio + Precio + QR -->
        <td style="width:32%; vertical-align:top;">
          <table>
            <tr>
              <td class="t-der folio">{{ $serie_numero ?? ($boleta->numero_contrato ?? '') }}</td>
            </tr>
            <tr><td class="sp"></td></tr>
            <tr>
              <td>
                <div class="precioLbl">PRECIO $</div>
                <div class="precioBox">
                  {{ isset($precio) ? $precio : number_format(($boleta->monto_prestamo ?? 0), 0, ',', '.') }}
                </div>

                @if(isset($qr_code_data) && $qr_code_data)
                  <div class="qrbox">
                    <img src="data:image/png;base64,{{ base64_encode($qr_code_data) }}" style="width:16mm;height:16mm;" alt="QR">
                  </div>
                  <div class="qrtext">Validar boleta</div>
                @endif
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>

    <div class="sp"></div>

    <!-- CLIENTE -->
    <div class="bloque">
      <div>Yo
        <span class="linea" style="width:58mm;">
          {{ $boleta->cliente->nombre_completo ?? '' }}
        </span>,
        mayor de edad, identificado con
      </div>
      <div>
        <span class="lbl">{{ $boleta->cliente->tipoDocumento->abreviacion ?? '' }} No.</span>
        <span class="linea" style="width:32mm;">{{ $boleta->cliente->cedula_nit ?? '' }}</span>
        y domiciliado en
        <span class="linea" style="width:56mm;">{{ $boleta->cliente->direccion ?? '' }}</span>
      </div>
    </div>

    <div class="sp"></div>

    <!-- DESCRIPCIÓN + PRODUCTOS EN PÁRRAFO -->
    <div>
      he vendido con pacto de retroventa de conformidad con los arts. 1939 y sgts. del C. Civil Col. a la compraventa
      {{ $boleta->empresa->razon_social ?? 'Compraventa' }}
      los siguientes bienes muebles de mi propiedad, libres de gravamen:
    </div>

    @if ($boleta->productos && $boleta->productos->count() > 0)
      <p>
        @foreach ($boleta->productos as $item)
          {{ $item->producto->nombre ?? 'Producto' }}
          ({{ $item->cantidad }} {{ $item->producto->tipoMedida->abreviatura ?? '' }})@if(!$loop->last), @endif
        @endforeach
      </p>
    @else
      <p>No se registraron productos en esta boleta.</p>
    @endif

    <!-- PRECIO EN TEXTO -->
    <div class="precioTexto">
      El precio de la compraventa es de $
      <span class="linea" style="width:42mm;">
        {{ number_format($boleta->monto_prestamo ?? 0, 0, ',', '.') }}
      </span>
      suma que declaro recibir en efectivo del comprador.
    </div>

    <!-- CLAUSULAS -->
    <div class="legal">
      {!! $clausulas_texto !!}
    </div>

    <!-- NOTA ROJA -->
    <div class="nota caps">
      {{ $nota_roja ?? 'DOMINGOS Y FESTIVOS NO SE ENTREGAN "ALHAJAS"' }}
    </div>

  </div>
</body>
</html>
