<?php

namespace App\Http\Controllers\Web\BolletaEmpeno;

use App\Http\Controllers\Controller;
use App\Models\BolletaEmpeno;
use App\Models\BolletaEmpenoProducto;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Sede;
use App\Exports\BoletasEmpenoExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use Dompdf\Dompdf;
use Dompdf\Options;
use Carbon\Carbon;

class BolletaEmpenoController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

    // Eager load cuotas as we'll need to know if a boleta tiene cuotas (to prevent anulación)
    $query = BolletaEmpeno::with(['cliente', 'empresa', 'productos.producto', 'cuotas'])
            ->orderBy('created_at', 'desc');

        // Filtrar por empresa según permisos del usuario
        if (!$user->esAdministradorGlobal()) {
            $empresasUsuario = $user->empresasActivas->pluck('id');
            $query->whereIn('empresa_id', $empresasUsuario);
        }

        // Filtro para mostrar/ocultar anuladas
        $mostrarAnuladas = $request->input('mostrar_anuladas', 'false') === 'true';
        if (!$mostrarAnuladas) {
            $query->where('anulada', false);
        }

        // Filtros
        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('numero_contrato')) {
            $searchTerm = trim($request->numero_contrato);

            // Solo buscar si el término tiene al menos 2 caracteres
            if (strlen($searchTerm) >= 2) {
                $query->whereHas('cliente', function ($clienteQuery) use ($searchTerm) {
                    $clienteQuery->where(function ($clienteSubQuery) use ($searchTerm) {
                        // Convertir a minúsculas para búsqueda case-insensitive
                        $searchTermLower = strtolower($searchTerm);

                        // Buscar en nombres y apellidos individualmente (case-insensitive)
                        $clienteSubQuery->whereRaw('LOWER(nombres) LIKE ?', ['%' . $searchTermLower . '%'])
                                        ->orWhereRaw('LOWER(apellidos) LIKE ?', ['%' . $searchTermLower . '%'])
                                        ->orWhereRaw('LOWER(razon_social) LIKE ?', ['%' . $searchTermLower . '%'])
                                        ->orWhereRaw('LOWER(cedula_nit) LIKE ?', ['%' . $searchTermLower . '%'])
                                        // Buscar nombre completo concatenado (case-insensitive)
                                        ->orWhereRaw("LOWER(CONCAT(COALESCE(nombres, ''), ' ', COALESCE(apellidos, ''))) LIKE ?", ['%' . $searchTermLower . '%']);
                    });
                });
            }
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        // Logging completo de la consulta - SIEMPRE loguear para debugging
        $allBoletas = clone $query;
        $todosLosResultados = $allBoletas->get();

        // Información de la consulta actual
        $requestInfo = [
            'usuario' => $user->name,
            'email' => $user->email,
            'url_completa' => $request->fullUrl(),
            'metodo' => $request->method(),
            'todos_los_parametros' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toDateTimeString()
        ];

        // Si hay búsqueda específica, log detallado
        if ($request->filled('numero_contrato')) {
            $searchTerm = trim($request->numero_contrato);

            $resultados = [];
            foreach ($todosLosResultados as $boleta) {
                $cliente = $boleta->cliente;
                if ($cliente) {
                    $motivo = [];

                    // Verificar qué campos coincidieron
                    if (stripos($cliente->nombres, $searchTerm) !== false) {
                        $motivo[] = "nombres: {$cliente->nombres}";
                    }
                    if (stripos($cliente->apellidos, $searchTerm) !== false) {
                        $motivo[] = "apellidos: {$cliente->apellidos}";
                    }
                    if (stripos($cliente->razon_social, $searchTerm) !== false) {
                        $motivo[] = "razon_social: {$cliente->razon_social}";
                    }
                    if (stripos($cliente->cedula_nit, $searchTerm) !== false) {
                        $motivo[] = "cedula_nit: {$cliente->cedula_nit}";
                    }
                    $nombreCompleto = trim($cliente->nombres . ' ' . $cliente->apellidos);
                    if (stripos($nombreCompleto, $searchTerm) !== false && !in_array("nombres: {$cliente->nombres}", $motivo) && !in_array("apellidos: {$cliente->apellidos}", $motivo)) {
                        $motivo[] = "nombre_completo: {$nombreCompleto}";
                    }

                    $resultados[] = [
                        'contrato' => $boleta->numero_contrato,
                        'cliente' => $nombreCompleto ?: $cliente->razon_social,
                        'cedula' => $cliente->cedula_nit,
                        'motivo_coincidencia' => implode(', ', $motivo)
                    ];
                }
            }

            \Log::info('BÚSQUEDA DE BOLETAS CON FILTRO', array_merge($requestInfo, [
                'termino_busqueda' => $searchTerm,
                'filtros_aplicados' => [
                    'empresa_id' => $request->empresa_id,
                    'estado' => $request->estado,
                    'fecha_desde' => $request->fecha_desde,
                    'fecha_hasta' => $request->fecha_hasta,
                    'mostrar_anuladas' => $request->mostrar_anuladas
                ],
                'total_resultados' => count($todosLosResultados),
                'todos_los_contratos' => $todosLosResultados->pluck('numero_contrato')->toArray(),
                'resultados_detallados' => $resultados
            ]));
        } else {
            // Log cuando se cargan todas las boletas sin filtro de búsqueda
            \Log::info('CARGA DE BOLETAS SIN FILTRO DE BÚSQUEDA', array_merge($requestInfo, [
                'filtros_aplicados' => [
                    'empresa_id' => $request->empresa_id,
                    'estado' => $request->estado,
                    'fecha_desde' => $request->fecha_desde,
                    'fecha_hasta' => $request->fecha_hasta,
                    'mostrar_anuladas' => $request->mostrar_anuladas
                ],
                'total_resultados' => count($todosLosResultados),
                'primeros_10_contratos' => $todosLosResultados->take(10)->pluck('numero_contrato')->toArray(),
                'contratos_completos' => $todosLosResultados->pluck('numero_contrato')->toArray()
            ]));
        }

        $boletas = $query->paginate(15);

        // Para admin global, obtener lista de empresas para el filtro
        $empresas = null;
        if ($user->esAdministradorGlobal()) {
            $empresas = Empresa::activas()->orderBy('razon_social')->get();
        } else {
            $empresas = $user->empresasActivas;
        }

        // Si es una solicitud AJAX, devolver solo la vista parcial
        if ($request->ajax()) {
            return view('boletas-empeno.partials.boletas-list-with-pagination', compact('boletas'))->render();
        }

        return view('boletas-empeno.index', compact('boletas', 'empresas', 'mostrarAnuladas'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();

        // Obtener empresas disponibles según el tipo de usuario
        if ($user->esAdministradorGlobal()) {
            // Administrador global: puede ver todas las empresas activas
            $empresas = Empresa::activas()->orderBy('razon_social')->get();
        } else {
            // Usuario regular: solo puede ver las empresas a las que pertenece
            $empresas = $user->empresasActivas;
        }

        // Validar que el usuario tenga acceso a al menos una empresa
        if ($empresas->isEmpty()) {
            abort(403, 'No tienes acceso a ninguna empresa para crear boletas de empeño.');
        }

        // Si se especifica una empresa en la URL, validar acceso
        $empresa = null;
        if ($request->filled('empresa_id')) {
            $empresaId = $request->input('empresa_id');
            $empresa = $empresas->where('id', $empresaId)->first();

            if (!$empresa) {
                abort(403, 'No tienes acceso a la empresa especificada.');
            }
        } else {
            // Si no se especifica empresa y solo hay una disponible, usarla por defecto
            if ($empresas->count() === 1) {
                $empresa = $empresas->first();
            }
        }

        // Obtener sedes de la empresa seleccionada (si hay alguna)
        $sedes = collect();
        if ($empresa) {
            $sedes = $empresa->sedesActivas()->orderBy('es_principal', 'desc')->orderBy('nombre')->get();
        }

        return view('boletas-empeno.create', compact('empresas', 'sedes', 'empresa'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        try {
            // Validación de datos
            $request->validate([
                'empresa_id' => 'required|exists:empresas,id',
                'sede_id' => 'nullable|exists:sedes,id',
                'cliente_id' => 'required|exists:clientes,id',
                'tipo_interes_id' => 'required|exists:tipo_interes,id',
                'monto_prestamo' => 'required|numeric|min:0',
                'fecha_vencimiento' => 'required|date|after:today',
                'observaciones' => 'nullable|string|max:1000',
                'productos' => 'required|array|min:1',
                'productos.*.producto_id' => 'required|exists:productos,id',
                'productos.*.cantidad' => 'required|numeric|min:0.01',
                'productos.*.descripcion_adicional' => 'nullable|string|max:500',
                'productos.*.foto_producto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors(),
                    'message' => 'Datos de validación incorrectos'
                ], 422);
            }
            throw $e;
        }

        // Verificar acceso a la empresa
        if (!$user->esAdministradorGlobal()) {
            $empresa = $user->empresasActivas->where('id', $request->empresa_id)->first();
            if (!$empresa) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes acceso a esta empresa.'
                    ], 403);
                }
                abort(403, 'No tienes acceso a esta empresa.');
            }
        }

        DB::beginTransaction();
        try {
            // Crear la boleta de empeño
            $boleta = BolletaEmpeno::create([
                'cliente_id' => $request->cliente_id,
                'empresa_id' => $request->empresa_id,
                'sede_id' => $request->sede_id,
                'user_id' => $user->id,
                'monto_prestamo' => $request->monto_prestamo,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'fecha_prestamo' => now()->format('Y-m-d'), // Fecha actual
                'observaciones' => $request->observaciones,
                'estado' => 'activa',
                'tipo_interes_id' => $request->tipo_interes_id,
                'tipo_movimiento_id' => 1, // Siempre es 1 automáticamente
            ]);

            // Procesar productos
            foreach ($request->productos as $productoData) {
                $fotoPath = null;

                // Manejar subida de foto si existe
                if (isset($productoData['foto_producto']) && $productoData['foto_producto']) {
                    $foto = $productoData['foto_producto'];
                    $fotoPath = $foto->store('boletas-empeno/productos', 'public');
                }

                BolletaEmpenoProducto::create([
                    'boleta_empeno_id' => $boleta->id,
                    'producto_id' => $productoData['producto_id'],
                    'cantidad' => $productoData['cantidad'],
                    'descripcion_adicional' => $productoData['descripcion_adicional'] ?? null,
                    'foto_producto' => $fotoPath,
                ]);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Boleta de empeño creada exitosamente.',
                    'redirect_url' => route('boletas-empeno.show', $boleta)
                ]);
            }

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Boleta de empeño creada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('boletas-empeno.show', $boleta);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la boleta de empeño: ' . $e->getMessage()
                ], 500);
            }

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al crear la boleta de empeño: ' . $e->getMessage(),
                'status' => 'error'
            ]);

            return back()->withInput();
        }
    }

    public function show(BolletaEmpeno $boletaEmpeno)
    {
        $user = Auth::user();

        // Verificar acceso
        if (!$user->esAdministradorGlobal()) {
            $empresa = $user->empresasActivas->where('id', $boletaEmpeno->empresa_id)->first();
            if (!$empresa) {
                abort(403, 'No tienes acceso a esta boleta.');
            }
        }

        $boletaEmpeno->load(['cliente', 'empresa.tiposInteres', 'sede', 'usuario', 'productos.producto', 'tipoInteres']);

        // Parsear fecha_prestamo si existe y es string
        if ($boletaEmpeno->fecha_prestamo && is_string($boletaEmpeno->fecha_prestamo)) {
            $boletaEmpeno->fecha_prestamo = Carbon::parse($boletaEmpeno->fecha_prestamo);
        }

        return view('boletas-empeno.show', compact('boletaEmpeno'));
    }

    /**
     * Generar PDF de la boleta (Dompdf)
     */
    public function pdf(BolletaEmpeno $boletaEmpeno)
    {
        $user = Auth::user();

        if (!$user->esAdministradorGlobal()) {
            $empresa = $user->empresasActivas->where('id', $boletaEmpeno->empresa_id)->first();
            if (!$empresa) {
                abort(403, 'No tienes acceso a esta boleta.');
            }
        }

        // Generar QR code si no existe
        if (!$boletaEmpeno->qr_code) {
            $boletaEmpeno->generarQrCode();
        }

        // Eager loading para evitar N+1 y habilitar direccion_completa
        $boletaEmpeno->load([
            'cliente.tipoDocumento',
            'empresa.barrio', 'empresa.comuna', 'empresa.municipio', 'empresa.departamento',
            'sede.barrio', 'sede.comuna', 'sede.municipio', 'sede.departamento',
            'usuario',
            'productos.producto.tipoMedida',
            'tipoInteres',
        ]);

        // --------- Cálculo del plazo (meses + días) ----------
        // Asegúrate de que tus campos sean parsables por Carbon (date/datetime)
        $inicio = Carbon::parse($boletaEmpeno->fecha_prestamo);
        $fin    = Carbon::parse($boletaEmpeno->fecha_vencimiento);

        // Diferencia exacta en años, meses y días
        $intervalo = $inicio->diff($fin);
        $meses  = ($intervalo->y * 12) + $intervalo->m; // convertir años a meses y sumarlos
        $dias   = $intervalo->d;

        // Armar texto del plazo (solo números, el CSS .caps lo pone en mayúsculas)
        $partes = [];
        if ($meses > 0) {
            $partes[] = $meses . ' MES' . ($meses === 1 ? '' : 'ES');
        }
        if ($dias > 0) {
            $partes[] = $dias . ' DÍA' . ($dias === 1 ? '' : 'S');
        }
        // Si inicio == fin
        if (empty($partes)) {
            $partes[] = '0 DÍAS';
        }
        $plazoTexto = implode(' ', $partes);

        // --------- Dirección priorizando la sede si existe direccion_completa ----------
        // Usa el accessor getDireccionCompletaAttribute() de Empresa (y de Sede si lo tienes)
        $direccion =
            ($boletaEmpeno->sede->direccion_completa ?? null) ??
            ($boletaEmpeno->sede->direccion ?? null) ??
            ($boletaEmpeno->empresa->direccion_completa ?? null) ??
            ($boletaEmpeno->empresa->direccion ?? '');

        // --------- Texto de cláusulas dinámico ----------
        $razon = $boletaEmpeno->empresa->razon_social ?? 'Compraventa';
        $ciudad = $boletaEmpeno->empresa->municipio->name ?? '';
        $depto  = $boletaEmpeno->empresa->departamento->name ?? '';

        $clausulas = <<<HTML
La cláusula de retroventa por un plazo de {$plazoTexto}, durante el cual la venta se retrotraerá siempre que la Compraventa "{$razon}" reciba como pago el precio de esta compraventa más una utilidad del precio por cada mes o fracción de mes que transcurra.

El vendedor deberá cancelar las utilidades causadas antes del vencimiento del contrato para tener derecho a renovar este contrato. Vencido el plazo de la compraventa con pacto de retroventa, el comprador no está obligado a respetar el pacto y podrá disponer de los bienes muebles libremente porque se habrá cumplido el evento contemplado en este contrato, que consolida la propiedad en cabeza de la Compraventa "{$razon}".

Pactamos que la Compraventa "{$razon}" no responderá por los bienes muebles en caso de pérdida o deterioro por <strong>FUERZA MAYOR O CASO FORTUITO</strong>: (ATRACO, ROBO, HURTO, INCENDIO, SAQUEO, INUNDACIÓN, DECOMISO DE AUTORIDAD LEGAL, ETC. ETC.), por lo tanto el vendedor perderá sus bienes muebles y no tendrá derecho a indemnización alguna.

El vendedor declara ser propietario de los bienes para todos los efectos legales y comerciales de este contrato, {$ciudad} - {$depto}.
HTML;

        // --------- Datos para la vista ----------
        $data = [
            'boleta'                 => $boletaEmpeno,
            'serie_numero'           => $boletaEmpeno->numero_contrato,
            'precio'                 => number_format($boletaEmpeno->monto_prestamo, 2),
            'vendedor'               => $razon,
            'vendedor_id'            => $boletaEmpeno->empresa->nit ?? '',
            'direccion'              => $direccion,
            'telefonos'              => $boletaEmpeno->sede->telefono ?? ($boletaEmpeno->empresa->telefono_fijo ?? ''),
            'titulo_compraventa'     => 'Compraventa',
            'encabezado_contrato'    => 'Contrato de Compraventa con pacto de Retroventa',
            'plazo'                  => $plazoTexto, // se muestra en el H1 con .caps
            'clausulas_texto'        => $clausulas,
            'nota_roja'              => 'DOMINGOS Y FESTIVOS NO SE ENTREGAN “ALHAJAS”',
            'firma_vendedor_label'   => 'FIRMA DEL VENDEDOR O AUTORIZADO',
            'firma_comprador_label'  => 'FIRMA DEL COMPRADOR',
            'qr_code_data'           => $boletaEmpeno->qr_code_image,
            'url_validacion'         => $boletaEmpeno->url_validacion,
        ];

        // HTML principal de la boleta
        $htmlBoleta = view('boletas-empeno.pdf.boleta', $data)->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('dpi', 96); // correspondencia mm/pt

        $dompdf = new Dompdf($options);

        // Helper corto para extraer el contenido del <body> de cada vista (evita concat de dos documentos HTML completos)
        $extractBody = function ($html) {
            if (preg_match('/<body.*?>(.*?)<\/body>/is', $html, $m)) {
                return $m[1];
            }
            return $html;
        };

        // Si la boleta tiene cuotas, generar PDFs separados y unirlos con FPDI para preservar tamaños
        if ($boletaEmpeno->cuotas()->exists()) {
            // Obtener última cuota registrada
            $ultimaCuota = $boletaEmpeno->cuotas()->with('usuario')->orderBy('fecha_abono', 'desc')->first();
            $todasLasCuotas = $boletaEmpeno->cuotas->sortBy('fecha_abono');

            $cuota = $ultimaCuota;

            // Rutas temporales
            $tmpDir = sys_get_temp_dir();
            $tmpBoleta = tempnam($tmpDir, 'boleta_') . '.pdf';
            $tmpRecibo = tempnam($tmpDir, 'recibo_') . '.pdf';
            $tmpSalida = tempnam($tmpDir, 'salida_') . '.pdf';

            try {
                // Generar PDF de la boleta (A5 landscape) y guardar en archivo temporal
                $dompdfBoleta = new Dompdf($options);
                $dompdfBoleta->loadHtml($htmlBoleta);
                $dompdfBoleta->setPaper('A5', 'landscape');
                $dompdfBoleta->render();
                file_put_contents($tmpBoleta, $dompdfBoleta->output());

                // Generar PDF del recibo (A4 portrait) y guardar en archivo temporal
                // La vista de recibo espera una variable llamada $boleta; pasar $boletaEmpeno bajo esa key
                $htmlRecibo = view('cuotas.pdf.recibo', [
                    'cuota' => $cuota,
                    'boleta' => $boletaEmpeno,
                    'empresa' => $boletaEmpeno->empresa,
                    'cliente' => $boletaEmpeno->cliente,
                    'todasLasCuotas' => $todasLasCuotas,
                ])->render();
                $dompdfRecibo = new Dompdf($options);
                $dompdfRecibo->loadHtml($htmlRecibo);
                $dompdfRecibo->setPaper('A4', 'portrait');
                $dompdfRecibo->render();
                file_put_contents($tmpRecibo, $dompdfRecibo->output());

                // Usar FPDI para combinar preservando tamaños de página
                $pdf = new \setasign\Fpdi\Fpdi();

                // Función que importa todas las páginas de un PDF
                $importAll = function($sourcePath) use ($pdf) {
                    $pageCount = $pdf->setSourceFile($sourcePath);
                    for ($i = 1; $i <= $pageCount; $i++) {
                        $tplId = $pdf->importPage($i);
                        $size = $pdf->getTemplateSize($tplId);

                        // Compatibilidad: algunas versiones devuelven ['w','h'], otras ['width','height']
                        $width = $size['w'] ?? $size['width'] ?? ($size[0] ?? null);
                        $height = $size['h'] ?? $size['height'] ?? ($size[1] ?? null);

                        if (empty($width) || empty($height)) {
                            \Log::warning('FPDI: dimensiones de plantilla no disponibles, se omite página', ['template' => $tplId, 'size' => $size]);
                            continue;
                        }

                        $orientation = ($width > $height) ? 'L' : 'P';

                        // Añadir página con tamaño exacto y usar la plantilla escalada al tamaño
                        $pdf->AddPage($orientation, [$width, $height]);
                        $pdf->useTemplate($tplId, 0, 0, $width, $height);
                    }
                };

                $importAll($tmpBoleta);
                $importAll($tmpRecibo);

                // Guardar PDF combinado en archivo de salida
                $pdf->Output('F', $tmpSalida);

                $combinedContent = file_get_contents($tmpSalida);

                // Limpiar temporales
                @unlink($tmpBoleta);
                @unlink($tmpRecibo);
                @unlink($tmpSalida);

                $filename = 'boleta_' . $boletaEmpeno->numero_contrato . '_con_recibo.pdf';

                return Response::make($combinedContent, 200, [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => "inline; filename=\"{$filename}\"",
                ]);

            } catch (\Throwable $e) {
                \Log::error('Error generando/uniendo PDFs con FPDI: ' . $e->getMessage(), ['boleta_id' => $boletaEmpeno->id]);
                // Intentar limpiar archivos si existieran
                @unlink($tmpBoleta);
                @unlink($tmpRecibo);
                @unlink($tmpSalida);
                // Continuar y renderizar la boleta sola más abajo
            }
        }

        // Si no hay cuotas o la unión falló, renderizar solo la boleta como antes
        $dompdf->loadHtml($htmlBoleta);
        $dompdf->setPaper('A5', 'landscape');
        $dompdf->render();

        $filename = 'boleta_' . $boletaEmpeno->numero_contrato . '.pdf';
        return Response::make($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    public function anular(Request $request, BolletaEmpeno $boletaEmpeno)
    {
        $user = Auth::user();

        // Verificar acceso
        if (!$user->esAdministradorGlobal()) {
            $empresa = $user->empresasActivas->where('id', $boletaEmpeno->empresa_id)->first();
            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'title' => 'Error de Acceso',
                    'message' => 'No tienes acceso a esta boleta.',
                    'status' => 'error'
                ], 403);
            }
        }

        $request->validate([
            'razon_anulacion' => 'required|string|max:500'
        ]);

        if ($boletaEmpeno->anulada) {
            return response()->json([
                'success' => false,
                'title' => 'Error',
                'message' => 'La boleta ya está anulada.',
                'status' => 'warning'
            ]);
        }

        // Regla: No permitir anular si la boleta está en estado 'pagada'
        if ($boletaEmpeno->estado === 'pagada') {
            return response()->json([
                'success' => false,
                'title' => 'Operación no permitida',
                'message' => 'No se puede anular una boleta que está en estado "pagada".',
                'status' => 'warning'
            ], 400);
        }

        // Regla: No permitir anular si ya existen cuotas asociadas a la boleta
        // Usar relación cargada si está disponible, si no, consultar existencia
        $tieneCuotas = isset($boletaEmpeno->relations['cuotas']) ? $boletaEmpeno->cuotas->count() > 0 : $boletaEmpeno->cuotas()->exists();
        if ($tieneCuotas) {
            return response()->json([
                'success' => false,
                'title' => 'Operación no permitida',
                'message' => 'No se puede anular una boleta que tiene cuotas registradas.',
                'status' => 'warning'
            ], 400);
        }

        try {
            $boletaEmpeno->update([
                'anulada' => true,
                'razon_anulacion' => $request->razon_anulacion,
                'anulada_por' => $user->id,
                'fecha_anulacion' => now(),
                'estado' => 'anulada'
            ]);

            return response()->json([
                'success' => true,
                'title' => '¡Éxito!',
                'message' => 'Boleta anulada exitosamente.',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'title' => 'Error',
                'message' => 'Error al anular la boleta: ' . $e->getMessage(),
                'status' => 'error'
            ]);
        }
    }

    // Métodos AJAX para obtener datos dinámicos
    public function getSedes(Request $request)
    {
        $empresaId = $request->get('empresa_id');

        if (!$empresaId) {
            return response()->json([]);
        }

        $user = Auth::user();

        // Verificar acceso a la empresa
        if (!$user->esAdministradorGlobal()) {
            $empresa = $user->empresasActivas->where('id', $empresaId)->first();
            if (!$empresa) {
                return response()->json([
                    'error' => 'No tienes acceso a esta empresa.',
                    'status' => 'error'
                ], 403);
            }
        }

        try {
            $sedes = Sede::where('empresa_id', $empresaId)
                ->where('activa', true)
                ->orderBy('es_principal', 'desc')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'es_principal']);

            return response()->json($sedes);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar las sedes: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    public function getClientes(Request $request)
    {
        $empresaId = $request->get('empresa_id');

        if (!$empresaId) {
            return response()->json([]);
        }

        $user = Auth::user();

        // Verificar acceso a la empresa
        if (!$user->esAdministradorGlobal()) {
            $empresa = $user->empresasActivas->where('id', $empresaId)->first();
            if (!$empresa) {
                return response()->json([
                    'error' => 'No tienes acceso a esta empresa.',
                    'status' => 'error'
                ], 403);
            }
        }

        try {
            $clientes = Cliente::where('empresa_id', $empresaId)
                ->orderByRaw("COALESCE(razon_social, CONCAT(nombres, ' ', apellidos)) ASC")
                ->get(['id', 'nombres', 'apellidos', 'razon_social', 'cedula_nit', 'tipo_documento_id']);

            // Formatear los datos para incluir nombre_completo
            $clientesFormateados = $clientes->map(function ($cliente) {
                // Crear nombre completo manualmente para asegurar que funcione
                if ($cliente->tipo_documento_id == 6) {
                    // Persona Jurídica (NIT)
                    $nombreCompleto = $cliente->razon_social ?: 'Sin razón social';
                } else {
                    // Persona Natural
                    $nombreCompleto = trim(($cliente->nombres ?: '') . ' ' . ($cliente->apellidos ?: '')) ?: 'Sin nombre';
                }

                return [
                    'id' => $cliente->id,
                    'nombre_completo' => $nombreCompleto,
                    'cedula_nit' => $cliente->cedula_nit ?: 'Sin documento',
                ];
            });

            return response()->json($clientesFormateados);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar los clientes: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    public function getProductos(Request $request)
    {
        $empresaId = $request->get('empresa_id');
        $search = $request->get('search', '');

        $user = Auth::user();

        try {
            $query = Producto::with(['tipoProducto', 'tipoOro', 'tipoMedida', 'empresa']);

            // Filtrar por empresa o productos globales
            if ($empresaId) {
                // Verificar acceso a la empresa
                if (!$user->esAdministradorGlobal()) {
                    $empresa = $user->empresasActivas->where('id', $empresaId)->first();
                    if (!$empresa) {
                        return response()->json([
                            'error' => 'No tienes acceso a esta empresa.',
                            'status' => 'error'
                        ], 403);
                    }
                }

                $query->where(function($q) use ($empresaId) {
                    $q->where('empresa_id', $empresaId)
                      ->orWhereNull('empresa_id'); // Incluir productos globales
                });
            } else {
                // Si no se especifica empresa, mostrar solo productos globales
                $query->whereNull('empresa_id');
            }

            // Filtrar por búsqueda
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('codigo_barras', 'like', "%{$search}%");
                });
            }

            $productos = $query->orderBy('nombre')
                ->limit(50)
                ->get();

            // Agregar el campo imagen_url usando el accessor
            $productos->each(function($producto) {
                $producto->imagen_url = $producto->imagen_url;
            });

            return response()->json($productos);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar los productos: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }


    public function export(Request $request)
    {
        $filename = 'boletas_empeno_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new BoletasEmpenoExport($request), $filename);
    }

    /**
     * Genera un número de contrato único para la empresa
     */
    private function generarNumeroContrato($empresaId)
    {
        $empresa = Empresa::findOrFail($empresaId);
        $año = date('Y');

        // Buscar el último número para esta empresa en el año actual
        $ultimaBoleta = BolletaEmpeno::where('empresa_id', $empresaId)
            ->whereYear('created_at', $año)
            ->orderBy('numero_contrato', 'desc')
            ->first();

        if ($ultimaBoleta && preg_match('/(\d+)$/', $ultimaBoleta->numero_contrato, $matches)) {
            $ultimoNumero = intval($matches[1]);
        } else {
            $ultimoNumero = 0;
        }

        $nuevoNumero = $ultimoNumero + 1;

        // Formato: EMPRESA-AÑO-NUMERO (ej: EMP001-2025-001)
        $prefijo = strtoupper(substr($empresa->razon_social, 0, 3)) . substr($empresa->nit, -3);

        return $prefijo . '-' . $año . '-' . str_pad($nuevoNumero, 3, '0', STR_PAD_LEFT);
    }

    public function crearProducto(Request $request)
    {
        $user = Auth::user();

        // Validación
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'codigo_barras' => 'nullable|string|max:100',
            'precio_venta' => 'nullable|numeric|min:0',
            'precio_compra' => 'nullable|numeric|min:0',
            'empresa_id' => 'required|exists:empresas,id',
            'tipo_producto_id' => 'nullable|exists:tipo_productos,id',
            'tipo_oro_id' => 'nullable|exists:tipo_oros,id',
            'tipo_medida_id' => 'nullable|exists:tipo_medidas,id',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Verificar acceso a la empresa
        if (!$user->esAdministradorGlobal()) {
            $empresa = $user->empresasActivas->where('id', $request->empresa_id)->first();
            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'title' => 'Error de Acceso',
                    'message' => 'No tienes acceso a esta empresa.',
                    'status' => 'error'
                ], 403);
            }
        }

        try {
            $imagenPath = null;

            // Manejar subida de imagen
            if ($request->hasFile('imagen')) {
                $imagenPath = $request->file('imagen')->store('productos', 'public');
            }

            $producto = Producto::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'codigo_barras' => $request->codigo_barras,
                'precio_venta' => $request->precio_venta,
                'precio_compra' => $request->precio_compra,
                'empresa_id' => $request->empresa_id,
                'tipo_producto_id' => $request->tipo_producto_id,
                'tipo_oro_id' => $request->tipo_oro_id,
                'tipo_medida_id' => $request->tipo_medida_id,
                'imagen' => $imagenPath,
            ]);

            // Cargar las relaciones para devolver todos los datos
            $producto->load(['tipoProducto', 'tipoOro', 'tipoMedida']);

            return response()->json([
                'success' => true,
                'title' => '¡Éxito!',
                'message' => 'Producto creado exitosamente',
                'status' => 'success',
                'producto' => [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'descripcion' => $producto->descripcion,
                    'codigo_barras' => $producto->codigo_barras,
                    'precio_venta' => $producto->precio_venta,
                    'precio_compra' => $producto->precio_compra,
                    'imagen_url' => $producto->imagen_url,
                    'tipo_producto' => $producto->tipoProducto,
                    'tipo_oro' => $producto->tipoOro,
                    'tipo_medida' => $producto->tipoMedida,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'title' => 'Error',
                'message' => 'Error al crear el producto: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    public function getTiposInteres(Request $request)
    {
        $empresaId = $request->get('empresa_id');
        $user = Auth::user();

        if (!$empresaId) {
            return response()->json([]);
        }

        // Verificar acceso a la empresa
        if (!$user->esAdministradorGlobal()) {
            $empresa = $user->empresasActivas->where('id', $empresaId)->first();
            if (!$empresa) {
                return response()->json([
                    'error' => 'No tienes acceso a esta empresa.',
                    'status' => 'error'
                ], 403);
            }
        }

        try {
            // Obtener tipos de interés activos de la empresa y globales
            $tiposInteres = \App\Models\TipoInteres::where('activo', true)
                ->where(function($query) use ($empresaId) {
                    $query->where('empresa_id', $empresaId)
                          ->orWhereNull('empresa_id'); // Incluir tipos globales
                })
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'porcentaje']);

            return response()->json($tiposInteres);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar los tipos de interés: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    public function getPreviewNumeroContrato()
    {
        try {
            $numeroContrato = BolletaEmpeno::generarNumeroContrato();

            return response()->json([
                'numero_contrato' => $numeroContrato
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar el número de contrato: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    public function uploadFoto(BolletaEmpeno $boletaEmpeno)
    {
        $user = Auth::user();

        // Verificar acceso
        if (!$user->esAdministradorGlobal()) {
            $empresa = $user->empresasActivas->where('id', $boletaEmpeno->empresa_id)->first();
            if (!$empresa) {
                abort(403, 'No tienes acceso a esta boleta.');
            }
        }

        return view('boletas-empeno.upload-foto', compact('boletaEmpeno'));
    }

    public function storeFoto(Request $request, BolletaEmpeno $boletaEmpeno)
    {
        $user = Auth::user();

        // Verificar acceso
        if (!$user->esAdministradorGlobal()) {
            $empresa = $user->empresasActivas->where('id', $boletaEmpeno->empresa_id)->first();
            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a esta boleta.'
                ], 403);
            }
        }

        $request->validate([
            'foto_prenda' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120' // 5MB max
        ]);

        try {
            // Eliminar foto anterior si existe
            if ($boletaEmpeno->foto_prenda) {
                Storage::disk('public')->delete($boletaEmpeno->foto_prenda);
            }

            // Subir nueva foto
            $fotoPath = $request->file('foto_prenda')->store('boletas-empeno/fotos', 'public');

            // Actualizar boleta
            $boletaEmpeno->update([
                'foto_prenda' => $fotoPath
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Foto subida exitosamente.',
                    'foto_url' => $boletaEmpeno->foto_prenda_url,
                    'status' => 'success'
                ]);
            }

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Foto de la prenda subida exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('boletas-empeno.show', $boletaEmpeno);

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al subir la foto: ' . $e->getMessage(),
                    'status' => 'error'
                ], 500);
            }

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al subir la foto: ' . $e->getMessage(),
                'status' => 'error'
            ]);

            return back();
        }
    }

    public function deleteFoto(BolletaEmpeno $boletaEmpeno)
    {
        $user = Auth::user();

        // Verificar acceso
        if (!$user->esAdministradorGlobal()) {
            $empresa = $user->empresasActivas->where('id', $boletaEmpeno->empresa_id)->first();
            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a esta boleta.'
                ], 403);
            }
        }

        try {
            // Eliminar foto del storage
            if ($boletaEmpeno->foto_prenda) {
                Storage::disk('public')->delete($boletaEmpeno->foto_prenda);
            }

            // Actualizar boleta
            $boletaEmpeno->update([
                'foto_prenda' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Foto eliminada exitosamente.',
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la foto: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    public function updateUbicacion(Request $request)
    {
        $request->validate([
            'boleta_id' => 'required|exists:boletas_empeno,id',
            'ubicacion' => 'nullable|string|max:500'
        ]);

        $user = Auth::user();
        $boletaEmpeno = BolletaEmpeno::findOrFail($request->boleta_id);

        // Verificar permisos de acceso a la boleta
        if (!$user->esAdministradorGlobal()) {
            $empresa = $user->empresasActivas->where('id', $boletaEmpeno->empresa_id)->first();
            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a esta boleta.'
                ], 403);
            }
        }

        try {
            $boletaEmpeno->update([
                'ubicacion' => $request->ubicacion
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ubicación actualizada exitosamente.',
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la ubicación: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }
}
