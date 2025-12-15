<?php

namespace App\Jobs;

use App\Imports\ProductosImport;
use App\Models\ProductImportHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ProcesarImportacionProductos implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rutaArchivo;
    protected $nombreArchivo;
    protected $empresaId;
    protected $modoImportacion;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($rutaArchivo, $nombreArchivo, $empresaId = null, $modoImportacion = 'crear', $userId = null)
    {
        $this->rutaArchivo = $rutaArchivo;
        $this->nombreArchivo = $nombreArchivo;
        $this->empresaId = $empresaId;
        $this->modoImportacion = $modoImportacion;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Iniciando importación asíncrona de productos', [
                'archivo' => $this->rutaArchivo,
                'empresa_id' => $this->empresaId,
                'modo' => $this->modoImportacion,
                'usuario' => $this->userId
            ]);

            // Verificar que el archivo existe
            if (!Storage::exists($this->rutaArchivo)) {
                throw new Exception("El archivo {$this->rutaArchivo} no existe");
            }

            // Crear instancia del importador
            $importador = new ProductosImport($this->empresaId, $this->modoImportacion);

            // Ejecutar la importación
            Excel::import($importador, Storage::path($this->rutaArchivo));

            // Obtener estadísticas de la importación
            $stats = $importador->getImportStats();
            $resumen = $importador->getResumen();

            // Guardar en historial
            ProductImportHistory::create([
                'filename' => $this->nombreArchivo,
                'stored_path' => $this->rutaArchivo,
                'user_id' => $this->userId,
                'empresa_id' => $this->empresaId,
                'modo_importacion' => $this->modoImportacion,
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

            Log::info('Importación asíncrona completada', [
                'resumen' => $resumen,
                'usuario' => $this->userId
            ]);

            // NO eliminar archivo temporal - mantener para historial

        } catch (Exception $e) {
            Log::error('Error en importación asíncrona de productos', [
                'archivo' => $this->rutaArchivo,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Guardar error en historial
            ProductImportHistory::create([
                'filename' => $this->nombreArchivo,
                'stored_path' => $this->rutaArchivo,
                'user_id' => $this->userId,
                'empresa_id' => $this->empresaId,
                'modo_importacion' => $this->modoImportacion,
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

            // NO eliminar archivo temporal en caso de error - mantener para historial

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Fallo en importación asíncrona de productos', [
            'archivo' => $this->rutaArchivo,
            'empresa_id' => $this->empresaId,
            'modo' => $this->modoImportacion,
            'usuario' => $this->userId,
            'error' => $exception->getMessage()
        ]);

        // Guardar fallo en historial si no se ha guardado antes
        $existeHistorial = ProductImportHistory::where('stored_path', $this->rutaArchivo)
            ->where('user_id', $this->userId)
            ->exists();

        if (!$existeHistorial) {
            ProductImportHistory::create([
                'filename' => $this->nombreArchivo,
                'stored_path' => $this->rutaArchivo,
                'user_id' => $this->userId,
                'empresa_id' => $this->empresaId,
                'modo_importacion' => $this->modoImportacion,
                'total_rows' => 0,
                'successful_imports' => 0,
                'skipped_duplicates' => 0,
                'failed_imports' => 0,
                'created_products' => 0,
                'updated_products' => 0,
                'duplicate_products' => [],
                'errors' => ['Fallo crítico en la importación: ' . $exception->getMessage()],
                'status' => 'failed',
            ]);
        }

        // NO eliminar archivo temporal - mantener para historial
    }
}
