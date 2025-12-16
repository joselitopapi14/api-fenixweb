<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductImportHistory;
use App\Imports\ProductosImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ProductoImportController extends Controller
{
    /**
     * Descargar plantilla Excel para importar productos
     */
    public function template()
    {
        // Headers esperados según la documentación/JSON
        $headers = [
            "nombre", 
            "descripcion", 
            "codigo_de_barras", 
            "tipo_de_producto", 
            "tipo_de_oro", 
            "precio_de_venta", 
            "precio_de_compra",
            "empresa" // opcional, si es admin puede especificar empresa
        ];

        // Crear CSV data en memoria
        $list = [$headers];
        
        // Agregar ejemplo
        $list[] = [
            "Anillo Ejemplo", 
            "Descripción ejemplo", 
            "123456789", 
            "Joyería", 
            "18K", 
            "500000", 
            "300000",
            ""
        ];

        $callback = function() use ($list) {
            $FH = fopen('php://output', 'w');
            foreach ($list as $row) { 
                fputcsv($FH, $row);
            }
            fclose($FH);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="plantilla_productos.csv"',
        ]);
    }

    /**
     * Previsualizar datos del Excel antes de importar
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'empresa_id' => 'required_if:user_role,admin|exists:empresas,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        try {
            $archivo = $request->file('archivo');
            $empresaId = $request->empresa_id ?? Auth::user()->empresa_id;

            // Usamos la clase ProductosImport pero solo leemos la primera hoja
            // Nota: validación real ocurriría al procesar, aquí solo mostramos preview
            $array = Excel::toArray(new ProductosImport($empresaId), $archivo);
            
            if (empty($array) || empty($array[0])) {
                return response()->json(['message' => 'El archivo parece estar vacío o no tiene formato válido.'], 422);
            }

            $rows = array_slice($array[0], 0, 10); // Primeras 10 filas para preview

            // Simular validación simple para preview
            $preview = [];
            foreach ($rows as $index => $row) {
                // Mapeo simple para mostrar
                $preview[] = array_merge(['fila' => $index + 2], $row); // +2 por header y 0-index
            }

            return response()->json([
                'preview' => $preview,
                'total_filas_estimadas' => count($array[0]),
                'filas_validas' => count($array[0]), // Estimado
                'filas_con_errores' => 0 // Estimado
            ]);

        } catch (Exception $e) {
            Log::error('Error previsualizando importación: ' . $e->getMessage());
            return response()->json(['message' => 'Error al leer el archivo', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Importar productos desde archivo Excel
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'empresa_id' => 'nullable|exists:empresas,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            
            $file = $request->file('archivo');
            $empresaId = $request->empresa_id ?? Auth::user()->empresa_id;
            $modoImportacion = $request->get('modo_importacion', 'crear_actualizar'); // crear, actualizar, crear_actualizar

            // Guardar archivo
            $path = $file->storeAs(
                'imports/productos/' . date('Y/m'), 
                uniqid() . '_' . $file->getClientOriginalName(), 
                'public'
            );

            // Ejecutar importación
            $importer = new ProductosImport($empresaId, $modoImportacion);
            Excel::import($importer, $file);
            
            $stats = $importer->getImportStats();

            // Registrar historial
            $history = ProductImportHistory::create([
                'user_id' => Auth::id(),
                'empresa_id' => $empresaId,
                'filename' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'modo_importacion' => $modoImportacion,
                'total_rows' => $stats['total_rows'],
                'successful_imports' => $stats['successful_imports'],
                'failed_imports' => $stats['failed_imports'],
                'skipped_duplicates' => $stats['skipped_duplicates'],
                'created_products' => $stats['created_products'],
                'updated_products' => $stats['updated_products'],
                'duplicate_products' => $stats['duplicate_products'], // array
                'errors' => $stats['errors'], // array
                'status' => $stats['status']
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Importación completada',
                'resumen' => [
                    'total' => $stats['total_rows'],
                    'exitosos' => $stats['successful_imports'],
                    'fallidos' => $stats['failed_imports'],
                    'duplicados' => $stats['skipped_duplicates'],
                    'actualizados' => $stats['updated_products']
                ],
                'errores' => array_slice($stats['errors'], 0, 50), // Limitar respuesta
                'historial_id' => $history->id
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error importando productos API: ' . $e->getMessage());
            return response()->json(['message' => 'Error al procesar la importación', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener historial de importaciones
     */
    public function history(Request $request)
    {
        $query = ProductImportHistory::with(['user:id,name,email', 'empresa:id,razon_social,nit']);

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $perPage = $request->get('per_page', 10);
        $history = $query->latest()->paginate($perPage);

        return response()->json($history);
    }
}
