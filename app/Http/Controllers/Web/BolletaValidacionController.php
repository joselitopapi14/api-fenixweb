<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BolletaEmpeno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Dompdf\Dompdf;
use Dompdf\Options;
use Carbon\Carbon;

class BolletaValidacionController extends Controller
{
    /**
     * Validar una boleta de empeño mediante token QR
     */
    public function validar(Request $request, $token)
    {
        // Buscar la boleta por el token QR
        $boleta = BolletaEmpeno::where('qr_code', $token)
            ->with([
                'cliente.tipoDocumento',
                'empresa.barrio', 'empresa.comuna', 'empresa.municipio', 'empresa.departamento',
                'sede.barrio', 'sede.comuna', 'sede.municipio', 'sede.departamento',
                'usuario',
                'productos.producto.tipoMedida',
                'tipoInteres',
                'cuotas.usuario'
            ])
            ->first();

        if (!$boleta) {
            return view('boletas-empeno.validacion.no-encontrada', [
                'token' => $token
            ]);
        }

        // Calcular información adicional
        $plazoInfo = $this->calcularPlazo($boleta);
        $estadoInfo = $this->calcularEstado($boleta);

        return view('boletas-empeno.validacion.detalle', [
            'boleta' => $boleta,
            'plazoInfo' => $plazoInfo,
            'estadoInfo' => $estadoInfo,
            'token' => $token
        ]);
    }

    /**
     * Generar PDF público de la boleta validada
     */
    public function pdf(Request $request, $token)
    {
        // Buscar la boleta por el token QR
        $boleta = BolletaEmpeno::where('qr_code', $token)
            ->with([
                'cliente.tipoDocumento',
                'empresa.barrio', 'empresa.comuna', 'empresa.municipio', 'empresa.departamento',
                'sede.barrio', 'sede.comuna', 'sede.municipio', 'sede.departamento',
                'usuario',
                'productos.producto.tipoMedida',
                'tipoInteres',
            ])
            ->first();

        if (!$boleta) {
            abort(404, 'Boleta no encontrada');
        }

        // Calcular plazo
        $inicio = Carbon::parse($boleta->fecha_prestamo);
        $fin = Carbon::parse($boleta->fecha_vencimiento);
        $intervalo = $inicio->diff($fin);
        $meses = ($intervalo->y * 12) + $intervalo->m;
        $dias = $intervalo->d;

        $partes = [];
        if ($meses > 0) {
            $partes[] = $meses . ' MES' . ($meses === 1 ? '' : 'ES');
        }
        if ($dias > 0) {
            $partes[] = $dias . ' DÍA' . ($dias === 1 ? '' : 'S');
        }
        if (empty($partes)) {
            $partes[] = '0 DÍAS';
        }
        $plazoTexto = implode(' ', $partes);

        // Dirección de la empresa/sede
        $direccion = ($boleta->sede->direccion_completa ?? null) ??
                    ($boleta->sede->direccion ?? null) ??
                    ($boleta->empresa->direccion_completa ?? null) ??
                    ($boleta->empresa->direccion ?? '');

        // Texto de cláusulas
        $razon = $boleta->empresa->razon_social ?? 'Compraventa';
        $ciudad = $boleta->empresa->municipio->name ?? '';
        $depto = $boleta->empresa->departamento->name ?? '';

        $clausulas = <<<HTML
La cláusula de retroventa por un plazo de {$plazoTexto}, durante el cual la venta se retrotraerá siempre que la Compraventa "{$razon}" reciba como pago el precio de esta compraventa más una utilidad del precio por cada mes o fracción de mes que transcurra.

El vendedor deberá cancelar las utilidades causadas antes del vencimiento del contrato para tener derecho a renovar este contrato. Vencido el plazo de la compraventa con pacto de retroventa, el comprador no está obligado a respetar el pacto y podrá disponer de los bienes muebles libremente porque se habrá cumplido el evento contemplado en este contrato, que consolida la propiedad en cabeza de la Compraventa "{$razon}".

Pactamos que la Compraventa "{$razon}" no responderá por los bienes muebles en caso de pérdida o deterioro por <strong>FUERZA MAYOR O CASO FORTUITO</strong>: (ATRACO, ROBO, HURTO, INCENDIO, SAQUEO, INUNDACIÓN, DECOMISO DE AUTORIDAD LEGAL, ETC. ETC.), por lo tanto el vendedor perderá sus bienes muebles y no tendrá derecho a indemnización alguna.

El vendedor declara ser propietario de los bienes para todos los efectos legales y comerciales de este contrato, {$ciudad} - {$depto}.
HTML;

        $data = [
            'boleta' => $boleta,
            'serie_numero' => $boleta->numero_contrato,
            'precio' => number_format($boleta->monto_prestamo, 2),
            'vendedor' => $razon,
            'vendedor_id' => $boleta->empresa->nit ?? '',
            'direccion' => $direccion,
            'telefonos' => $boleta->sede->telefono ?? ($boleta->empresa->telefono_fijo ?? ''),
            'titulo_compraventa' => 'Compraventa',
            'encabezado_contrato' => 'Contrato de Compraventa con pacto de Retroventa',
            'plazo' => $plazoTexto,
            'clausulas_texto' => $clausulas,
            'nota_roja' => 'DOMINGOS Y FESTIVOS NO SE ENTREGAN "ALHAJAS"',
            'firma_vendedor_label' => 'FIRMA DEL VENDEDOR O AUTORIZADO',
            'firma_comprador_label' => 'FIRMA DEL COMPRADOR',
            'es_validacion_publica' => true,
        ];

        $html = view('boletas-empeno.pdf.boleta', $data)->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('dpi', 96);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'landscape');
        $dompdf->render();

        $filename = 'boleta_validada_' . $boleta->numero_contrato . '.pdf';
        return Response::make($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Calcular información del plazo
     */
    private function calcularPlazo($boleta)
    {
        $inicio = Carbon::parse($boleta->fecha_prestamo);
        $fin = Carbon::parse($boleta->fecha_vencimiento);
        $ahora = Carbon::now();

        $intervalo = $inicio->diff($fin);
        $meses = ($intervalo->y * 12) + $intervalo->m;
        $dias = $intervalo->d;

        $plazoTotal = [];
        if ($meses > 0) {
            $plazoTotal[] = $meses . ' mes' . ($meses === 1 ? '' : 'es');
        }
        if ($dias > 0) {
            $plazoTotal[] = $dias . ' día' . ($dias === 1 ? '' : 's');
        }
        if (empty($plazoTotal)) {
            $plazoTotal[] = '0 días';
        }

        // Calcular días restantes
        $diasRestantes = $ahora->diffInDays($fin, false); // false para obtener negativos si ya venció

        return [
            'plazo_total' => implode(' y ', $plazoTotal),
            'dias_restantes' => $diasRestantes,
            'vencida' => $diasRestantes < 0,
            'por_vencer' => $diasRestantes >= 0 && $diasRestantes <= 7
        ];
    }

    /**
     * Calcular información del estado
     */
    private function calcularEstado($boleta)
    {
        $estado = $boleta->estado;
        $anulada = $boleta->anulada;

        if ($anulada) {
            return [
                'estado' => 'Anulada',
                'clase_css' => 'danger',
                'descripcion' => 'Esta boleta ha sido anulada',
                'razon_anulacion' => $boleta->razon_anulacion
            ];
        }

        switch ($estado) {
            case 'activa':
                $plazoInfo = $this->calcularPlazo($boleta);
                if ($plazoInfo['vencida']) {
                    return [
                        'estado' => 'Vencida',
                        'clase_css' => 'danger',
                        'descripcion' => 'La boleta ha vencido'
                    ];
                } elseif ($plazoInfo['por_vencer']) {
                    return [
                        'estado' => 'Por vencer',
                        'clase_css' => 'warning',
                        'descripcion' => 'La boleta está próxima a vencer'
                    ];
                } else {
                    return [
                        'estado' => 'Activa',
                        'clase_css' => 'success',
                        'descripcion' => 'La boleta está vigente'
                    ];
                }
                break;
            case 'renovada':
                return [
                    'estado' => 'Renovada',
                    'clase_css' => 'info',
                    'descripcion' => 'La boleta ha sido renovada'
                ];
                break;
            case 'desempenada':
                return [
                    'estado' => 'Desempeñada',
                    'clase_css' => 'success',
                    'descripcion' => 'La boleta ha sido desempeñada'
                ];
                break;
            case 'rematada':
                return [
                    'estado' => 'Rematada',
                    'clase_css' => 'secondary',
                    'descripcion' => 'La boleta ha sido rematada'
                ];
                break;
            default:
                return [
                    'estado' => ucfirst($estado),
                    'clase_css' => 'secondary',
                    'descripcion' => 'Estado: ' . $estado
                ];
        }
    }
}
