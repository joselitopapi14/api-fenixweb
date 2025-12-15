<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportProductosRequest;
use App\Imports\ProductosImport;
use App\Exports\ProductosExport;
use App\Models\Empresa;
use App\Models\ProductImportHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Exception;

class ImportProductosController extends Controller
{
    /**
     * Mostrar formulario de importación
     */
    public function index()
    {
        $empresas = Empresa::where('activa', true)
            ->orderBy('razon_social')
            ->get();

        return view('admin.productos.import', compact('empresas'));
    }

    /**
     * Procesar la importación de productos
     */
    public function import(ImportProductosRequest $request)
    {
        try {
            $archivo = $request->file('archivo_excel');
            $empresaId = $request->input('empresa_id');
            $modoImportacion = $request->input('modo_importacion', 'crear');

            // Validar que la empresa existe si se especifica
            if ($empresaId) {
                $empresa = Empresa::findOrFail($empresaId);
            }

            // Almacenar archivo para referencia futura
            $nombreArchivo = $archivo->getClientOriginalName();
            $rutaAlmacenada = $archivo->store('imports', 'local');

            // Crear instancia del importador
            $importador = new ProductosImport($empresaId, $modoImportacion);

            // Ejecutar la importación
            Excel::import($importador, $archivo);

            // Obtener estadísticas de la importación
            $stats = $importador->getImportStats();
            $resumen = $importador->getResumen(); // Para mantener compatibilidad

            // Guardar en historial
            ProductImportHistory::create([
                'filename' => $nombreArchivo,
                'stored_path' => $rutaAlmacenada,
                'user_id' => auth()->id(),
                'empresa_id' => $empresaId,
                'modo_importacion' => $modoImportacion,
                'total_rows' => $stats['total_rows'],
                'successful_imports' => $stats['successful_imports'],
                'skipped_duplicates' => $stats['skipped_duplicates'],
                'failed_imports' => $stats['failed_imports'],
                'created_products' => $stats['created_products'],
                'updated_products' => $stats['updated_products'],
                'duplicate_products' => $stats['duplicate_products'],
                'errors' => $stats['errors'],
                'status' => $stats['status'],
            ]);

            // Log de la operación
            Log::info('Importación de productos completada', [
                'empresa_id' => $empresaId,
                'modo' => $modoImportacion,
                'resumen' => $resumen,
                'usuario' => auth()->user()->id ?? 'sistema'
            ]);

            // Preparar mensaje de respuesta
            $mensaje = $this->generarMensajeResumen($resumen);

            if (count($resumen['errores']) > 0) {
                return back()->with('warning', $mensaje)
                    ->with('errores_importacion', $resumen['errores']);
            }

            return back()->with('success', $mensaje);

        } catch (Exception $e) {
            Log::error('Error en importación de productos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'usuario' => auth()->user()->id ?? 'sistema'
            ]);

            // Si hubo error, también guardamos en historial
            if (isset($nombreArchivo) && isset($rutaAlmacenada)) {
                ProductImportHistory::create([
                    'filename' => $nombreArchivo,
                    'stored_path' => $rutaAlmacenada,
                    'user_id' => auth()->id(),
                    'empresa_id' => $empresaId ?? null,
                    'modo_importacion' => $modoImportacion ?? 'crear',
                    'total_rows' => 0,
                    'successful_imports' => 0,
                    'skipped_duplicates' => 0,
                    'failed_imports' => 0,
                    'created_products' => 0,
                    'updated_products' => 0,
                    'duplicate_products' => [],
                    'errors' => [$e->getMessage()],
                    'status' => 'failed',
                ]);
            }

            return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    /**
     * Descargar plantilla de ejemplo
     */
    public function descargarPlantilla()
    {
        try {
            // Generar plantilla dinámicamente basada en ProductosExport
            $plantillaData = collect([
                [
                    'ID' => '',
                    'Nombre' => 'Anillo de Oro 18K Clásico',
                    'Descripción' => 'Anillo elegante de oro de 18 kilates con diseño clásico atemporal',
                    'Código de Barras' => '12345678901234',
                    'Tipo de Producto' => 'Joyería',
                    'Tipo de Oro' => 'Oro 18K',
                    'Precio de Venta' => '$250.000,00',
                    'Precio de Compra' => '$200.000,00',
                    'Empresa' => 'Global',
                    'Fecha de Creación' => '',
                    'Última Actualización' => ''
                ],
                [
                    'ID' => '',
                    'Nombre' => 'Cadena de Plata 925',
                    'Descripción' => 'Cadena de plata 925 con eslabones tradicionales, 50cm de largo',
                    'Código de Barras' => '12345678901235',
                    'Tipo de Producto' => 'Joyería',
                    'Tipo de Oro' => '',
                    'Precio de Venta' => '$80.000,00',
                    'Precio de Compra' => '$60.000,00',
                    'Empresa' => 'Global',
                    'Fecha de Creación' => '',
                    'Última Actualización' => ''
                ],
                [
                    'ID' => '',
                    'Nombre' => 'Producto Sin Tipo',
                    'Descripción' => 'Producto que usará el tipo por defecto (ID 2)',
                    'Código de Barras' => '12345678901236',
                    'Tipo de Producto' => '',
                    'Tipo de Oro' => '',
                    'Precio de Venta' => '$50.000,00',
                    'Precio de Compra' => '$40.000,00',
                    'Empresa' => 'Global',
                    'Fecha de Creación' => '',
                    'Última Actualización' => ''
                ]
            ]);

            // Usar ProductosExport para generar plantilla con el formato exacto
            $export = new \App\Exports\ProductosExport(new \Illuminate\Http\Request());

            return Excel::download(new class($plantillaData) implements
                \Maatwebsite\Excel\Concerns\FromCollection,
                \Maatwebsite\Excel\Concerns\WithHeadings,
                \Maatwebsite\Excel\Concerns\WithStyles,
                \Maatwebsite\Excel\Concerns\ShouldAutoSize
            {
                private $data;

                public function __construct($data) {
                    $this->data = $data;
                }

                public function collection() {
                    return $this->data;
                }

                public function headings(): array {
                    return [
                        'ID',
                        'Nombre',
                        'Descripción',
                        'Código de Barras',
                        'Tipo de Producto',
                        'Tipo de Oro',
                        'Precio de Venta',
                        'Precio de Compra',
                        'Empresa',
                        'Fecha de Creación',
                        'Última Actualización'
                    ];
                }

                public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet) {
                    return [
                        1 => [
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => 'FFFFFF'],
                            ],
                            'fill' => [
                                'fillType' => 'solid',
                                'startColor' => ['rgb' => '059669'],
                            ],
                        ],
                        'A:K' => [
                            'alignment' => [
                                'vertical' => 'top',
                                'wrapText' => true,
                            ],
                        ],
                    ];
                }
            }, 'plantilla_importacion_productos.xlsx');

        } catch (Exception $e) {
            Log::error('Error generando plantilla de productos', [
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Error al generar la plantilla: ' . $e->getMessage());
        }
    }    /**
     * Previsualizar datos del Excel antes de importar
     */
    public function previsualizarArchivo(Request $request)
    {
        $request->validate([
            'archivo_excel' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            $archivo = $request->file('archivo_excel');

            // Leer las primeras 10 filas para previsualización
            $datos = Excel::toArray(new ProductosImport(), $archivo);

            $preview = array_slice($datos[0], 0, 10); // Tomar solo las primeras 10 filas

            return response()->json([
                'success' => true,
                'preview' => $preview,
                'total_filas' => count($datos[0])
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al leer el archivo: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Mostrar historial de importaciones de productos
     */
    public function historial(Request $request)
    {
        $query = ProductImportHistory::with(['user', 'empresa'])
            ->latest();

        // Filtros
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('empresa_id')) {
            $query->byEmpresa($request->empresa_id);
        }

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->byDateRange($request->date_from, $request->date_to);
        }

        $historial = $query->paginate(15)->withQueryString();

        $empresas = Empresa::where('activa', true)->orderBy('razon_social')->get();

        return view('admin.productos.historial', compact('historial', 'empresas'));
    }

    /**
     * Mostrar detalles de una importación específica
     */
    public function detalleHistorial($id)
    {
        $importacion = ProductImportHistory::with(['user', 'empresa'])->findOrFail($id);

        return view('admin.productos.detalle-historial', compact('importacion'));
    }

    /**
     * Descargar archivo original de una importación
     */
    public function descargarArchivoHistorial($id)
    {
        $importacion = ProductImportHistory::findOrFail($id);

        // Verificar que el archivo existe
        if (!$importacion->stored_path || !Storage::exists($importacion->stored_path)) {
            return back()->with('error', 'El archivo original no está disponible.');
        }

        // Verificar permisos (opcional: solo el usuario que subió o admins)
        if (auth()->id() !== $importacion->user_id && !auth()->user()->hasRole('Super Admin')) {
            return back()->with('error', 'No tienes permisos para descargar este archivo.');
        }

        return Storage::download($importacion->stored_path, $importacion->filename);
    }

    /**
     * API para obtener historial filtrado (para uso con JavaScript)
     */
    public function apiHistorial(Request $request)
    {
        $query = ProductImportHistory::with(['user', 'empresa'])
            ->latest();

        // Aplicar filtros
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('empresa_id')) {
            $query->byEmpresa($request->empresa_id);
        }

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->byDateRange($request->date_from, $request->date_to);
        }

        $historial = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $historial
        ]);
    }

    /**
     * Generar mensaje resumen de la importación
     */
    private function generarMensajeResumen(array $resumen): string
    {
        $mensaje = "Importación completada:\n";
        $mensaje .= "• Filas procesadas: {$resumen['procesados']}\n";
        $mensaje .= "• Productos creados: {$resumen['creados']}\n";
        $mensaje .= "• Productos actualizados: {$resumen['actualizados']}\n";
        $mensaje .= "• Filas omitidas: {$resumen['omitidos']}";

        if (count($resumen['errores']) > 0) {
            $mensaje .= "\n• Errores encontrados: " . count($resumen['errores']);
        }

        return $mensaje;
    }
}
