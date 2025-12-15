<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportClientesRequest;
use App\Imports\ClientesImport;
use App\Exports\ClientesExport;
use App\Models\Empresa;
use App\Models\ClientImportHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Exception;

class ImportClientesController extends Controller
{
    /**
     * Mostrar formulario de importación
     */
    public function index()
    {
        $empresas = Empresa::where('activa', true)
            ->orderBy('razon_social')
            ->get();

        return view('admin.clientes.import', compact('empresas'));
    }

    /**
     * Procesar la importación de clientes
     */
    public function import(ImportClientesRequest $request)
    {
        try {
            $archivo = $request->file('archivo_excel');
            $empresaId = $request->input('empresa_id');
            $modoImportacion = $request->input('modo_importacion', 'crear');

            // Validar que la empresa existe
            if ($empresaId) {
                $empresa = Empresa::findOrFail($empresaId);
            } else {
                return back()->with('error', 'Debe seleccionar una empresa para importar clientes.');
            }

            // Almacenar archivo para referencia futura
            $nombreArchivo = $archivo->getClientOriginalName();
            $rutaAlmacenada = $archivo->store('imports/clientes', 'local');

            // Crear instancia del importador
            $importador = new ClientesImport($empresaId, $modoImportacion);

            // Ejecutar la importación
            Excel::import($importador, $archivo);

            // Obtener estadísticas de la importación
            $stats = $importador->getImportStats();
            $resumen = $importador->getResumen(); // Para mantener compatibilidad

            // Guardar en historial
            ClientImportHistory::create([
                'filename' => $nombreArchivo,
                'stored_path' => $rutaAlmacenada,
                'user_id' => auth()->id(),
                'empresa_id' => $empresaId,
                'modo_importacion' => $modoImportacion,
                'total_rows' => $stats['total_rows'],
                'successful_imports' => $stats['successful_imports'],
                'skipped_duplicates' => $stats['skipped_duplicates'],
                'failed_imports' => $stats['failed_imports'],
                'created_clients' => $stats['created_clients'],
                'updated_clients' => $stats['updated_clients'],
                'duplicate_clients' => $stats['duplicate_clients'],
                'errors' => $stats['errors'],
                'status' => $stats['status'],
            ]);

            // Log de la operación
            Log::info('Importación de clientes completada', [
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
            Log::error('Error en importación de clientes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'usuario' => auth()->user()->id ?? 'sistema'
            ]);

            // Si hubo error, también guardamos en historial
            if (isset($nombreArchivo) && isset($rutaAlmacenada)) {
                ClientImportHistory::create([
                    'filename' => $nombreArchivo,
                    'stored_path' => $rutaAlmacenada,
                    'user_id' => auth()->id(),
                    'empresa_id' => $empresaId ?? null,
                    'modo_importacion' => $modoImportacion ?? 'crear',
                    'total_rows' => 0,
                    'successful_imports' => 0,
                    'skipped_duplicates' => 0,
                    'failed_imports' => 0,
                    'created_clients' => 0,
                    'updated_clients' => 0,
                    'duplicate_clients' => [],
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
            // Generar plantilla dinámicamente basada en ClientesExport
            $plantillaData = collect([
                [
                    'ID' => '',
                    'Tipo de Documento' => 'Cédula de Ciudadanía',
                    'Cédula/NIT' => '12345678',
                    'DV' => '',
                    'Nombres' => 'JUAN CARLOS',
                    'Apellidos' => 'PÉREZ GARCÍA',
                    'Razón Social' => '',
                    'Email' => 'juan.perez@email.com',
                    'Fecha de Nacimiento' => '15/05/1985',
                    'Representante Legal' => '',
                    'Cédula Representante' => '',
                    'Email Representante' => '',
                    'Dirección Representante' => '',
                    'Dirección' => 'CALLE 123 # 45-67',
                    'Departamento' => 'CUNDINAMARCA',
                    'Municipio' => 'BOGOTÁ D.C.',
                    'Comuna' => 'USAQUÉN',
                    'Barrio' => 'CHAPINERO',
                    'Teléfono Fijo' => '6015551234',
                    'Celular' => '3012345678',
                    'Redes Sociales' => 'Facebook: juan.perez | Instagram: @jperez',
                    'Empresa' => '',
                    'Fecha de Creación' => '',
                    'Última Actualización' => ''
                ],
                [
                    'ID' => '',
                    'Tipo de Documento' => 'NIT',
                    'Cédula/NIT' => '900123456',
                    'DV' => '2',
                    'Nombres' => '',
                    'Apellidos' => '',
                    'Razón Social' => 'EMPRESA EJEMPLO S.A.S.',
                    'Email' => 'contacto@empresa.com',
                    'Fecha de Nacimiento' => '',
                    'Representante Legal' => 'MARÍA ELENA RODRÍGUEZ',
                    'Cédula Representante' => '87654321',
                    'Email Representante' => 'maria.rodriguez@empresa.com',
                    'Dirección Representante' => 'CARRERA 456 # 78-90',
                    'Dirección' => 'AVENIDA 789 # 12-34',
                    'Departamento' => 'CUNDINAMARCA',
                    'Municipio' => 'BOGOTÁ D.C.',
                    'Comuna' => 'SUBA',
                    'Barrio' => 'CENTRO SUBA',
                    'Teléfono Fijo' => '6017778888',
                    'Celular' => '3109876543',
                    'Redes Sociales' => 'Facebook: empresaejemplo | LinkedIn: empresa-ejemplo',
                    'Empresa' => '',
                    'Fecha de Creación' => '',
                    'Última Actualización' => ''
                ],
                [
                    'ID' => '',
                    'Tipo de Documento' => 'Cédula de Extranjería',
                    'Cédula/NIT' => '1234567890',
                    'DV' => '',
                    'Nombres' => 'ANNA MARIA',
                    'Apellidos' => 'SMITH JOHNSON',
                    'Razón Social' => '',
                    'Email' => 'anna.smith@email.com',
                    'Fecha de Nacimiento' => '20/08/1990',
                    'Representante Legal' => '',
                    'Cédula Representante' => '',
                    'Email Representante' => '',
                    'Dirección Representante' => '',
                    'Dirección' => 'TRANSVERSAL 98 # 76-54',
                    'Departamento' => 'ANTIOQUIA',
                    'Municipio' => 'MEDELLÍN',
                    'Comuna' => 'EL POBLADO',
                    'Barrio' => 'ZONA ROSA',
                    'Teléfono Fijo' => '',
                    'Celular' => '3151234567',
                    'Redes Sociales' => 'Instagram: @annasmith',
                    'Empresa' => '',
                    'Fecha de Creación' => '',
                    'Última Actualización' => ''
                ]
            ]);

            // Usar ClientesExport para generar plantilla con el formato exacto
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
                        'Tipo de Documento',
                        'Cédula/NIT',
                        'DV',
                        'Nombres',
                        'Apellidos',
                        'Razón Social',
                        'Email',
                        'Fecha de Nacimiento',
                        'Representante Legal',
                        'Cédula Representante',
                        'Email Representante',
                        'Dirección Representante',
                        'Dirección',
                        'Departamento',
                        'Municipio',
                        'Comuna',
                        'Barrio',
                        'Teléfono Fijo',
                        'Celular',
                        'Redes Sociales',
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
                        'A:X' => [
                            'alignment' => [
                                'vertical' => 'top',
                                'wrapText' => true,
                            ],
                        ],
                    ];
                }
            }, 'plantilla_importacion_clientes.xlsx');

        } catch (Exception $e) {
            Log::error('Error generando plantilla de clientes', [
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Error al generar la plantilla: ' . $e->getMessage());
        }
    }

    /**
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
            $datos = Excel::toArray(new ClientesImport(), $archivo);

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
     * Mostrar historial de importaciones de clientes
     */
    public function historial(Request $request)
    {
        $query = ClientImportHistory::with(['user', 'empresa'])
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

        return view('admin.clientes.historial', compact('historial', 'empresas'));
    }

    /**
     * Mostrar detalles de una importación específica
     */
    public function detalleHistorial($id)
    {
        $importacion = ClientImportHistory::with(['user', 'empresa'])->findOrFail($id);

        return view('admin.clientes.detalle-historial', compact('importacion'));
    }

    /**
     * Descargar archivo original de una importación
     */
    public function descargarArchivoHistorial($id)
    {
        $importacion = ClientImportHistory::findOrFail($id);

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
        $query = ClientImportHistory::with(['user', 'empresa'])
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
     * Exportar clientes de una empresa específica
     */
    public function exportar(Request $request, Empresa $empresa)
    {
        try {
            $fileName = 'clientes_' . $empresa->id . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(new ClientesExport($request, $empresa->id), $fileName);

        } catch (Exception $e) {
            Log::error('Error exportando clientes', [
                'error' => $e->getMessage(),
                'empresa_id' => $empresa->id ?? null
            ]);

            return back()->with('error', 'Error al generar la exportación: ' . $e->getMessage());
        }
    }

    /**
     * Generar mensaje resumen de la importación
     */
    private function generarMensajeResumen(array $resumen): string
    {
        $mensaje = "Importación completada:\n";
        $mensaje .= "• Filas procesadas: {$resumen['procesados']}\n";
        $mensaje .= "• Clientes creados: {$resumen['creados']}\n";
        $mensaje .= "• Clientes actualizados: {$resumen['actualizados']}\n";
        $mensaje .= "• Filas omitidas: {$resumen['omitidos']}";

        if (count($resumen['errores']) > 0) {
            $mensaje .= "\n• Errores encontrados: " . count($resumen['errores']);
        }

        return $mensaje;
    }
}
