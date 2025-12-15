<?php

namespace App\Imports;

use App\Models\Producto;
use App\Models\TipoProducto;
use App\Models\TipoOro;
use App\Models\Empresa;
use App\Models\TipoMedida;
use App\Models\Impuesto;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class ProductosImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsEmptyRows,
    WithChunkReading,
    SkipsOnError,
    SkipsOnFailure
{
    protected $empresaId;
    protected $modoImportacion;
    protected $errores = [];
    protected $procesados = 0;
    protected $creados = 0;
    protected $actualizados = 0;
    protected $omitidos = 0;
    protected $duplicados = []; // Array para almacenar nombres de productos duplicados

    public function __construct($empresaId = null, $modoImportacion = 'crear')
    {
        $this->empresaId = $empresaId;
        $this->modoImportacion = $modoImportacion;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            $this->procesados++;

            // Limpiar y validar datos - manteniendo compatibilidad con ambos formatos
            // Formato nuevo (igual al export): nombre, descripcion, codigo_de_barras, tipo_de_producto, tipo_de_oro, etc.
            // Formato legacy: nombre, descripcion, tipo_producto, tipo_oro

            $nombre = trim($row['nombre'] ?? '');
            $descripcion = trim($row['descripcion'] ?? '');

            // Código de barras (solo en formato nuevo) - convertir a string
            $codigoBarras = '';
            if (isset($row['codigo_de_barras']) && !is_null($row['codigo_de_barras'])) {
                $codigoBarras = trim((string)$row['codigo_de_barras']);
            }

            // Tipo de producto - compatibilidad con ambos formatos
            $tipoProductoNombre = '';
            if (isset($row['tipo_de_producto'])) {
                $tipoProductoNombre = trim((string)$row['tipo_de_producto']);
            } elseif (isset($row['tipo_producto'])) {
                $tipoProductoNombre = trim((string)$row['tipo_producto']);
            }

            // Tipo de oro - compatibilidad con ambos formatos - convertir a string
            $tipoOroNombre = '';
            if (isset($row['tipo_de_oro']) && !is_null($row['tipo_de_oro'])) {
                $tipoOroNombre = trim((string)$row['tipo_de_oro']);
            } elseif (isset($row['tipo_oro']) && !is_null($row['tipo_oro'])) {
                $tipoOroNombre = trim((string)$row['tipo_oro']);
            }
            $tipoOroNombre = !empty($tipoOroNombre) ? $tipoOroNombre : null;

            // Procesar precios (solo en formato nuevo)
            $precioVenta = $this->procesarPrecio($row['precio_de_venta'] ?? '');
            $precioCompra = $this->procesarPrecio($row['precio_de_compra'] ?? '');

            // Validar empresa solo si está especificada (solo en formato nuevo)
            $empresaRazonSocial = trim($row['empresa'] ?? '');

            if (empty($nombre)) {
                $this->errores[] = "Fila {$this->procesados}: El nombre del producto es obligatorio";
                $this->omitidos++;
                return null;
            }

            // Buscar o crear tipo de producto - Si no viene o está vacío, usar ID 2
            if (empty($tipoProductoNombre)) {
                $tipoProducto = TipoProducto::find(2);
                if (!$tipoProducto) {
                    $this->errores[] = "Fila {$this->procesados}: No se encontró el tipo de producto por defecto (ID: 2)";
                    $this->omitidos++;
                    return null;
                }
            } else {
                $tipoProducto = $this->buscarOCrearTipoProducto($tipoProductoNombre);
                if (!$tipoProducto) {
                    $this->errores[] = "Fila {$this->procesados}: No se pudo crear/encontrar el tipo de producto '{$tipoProductoNombre}'";
                    $this->omitidos++;
                    return null;
                }
            }

            // Buscar o crear tipo de oro si se especifica
            $tipoOro = null;
            if ($tipoOroNombre) {
                $tipoOro = $this->buscarOCrearTipoOro($tipoOroNombre);
                if (!$tipoOro) {
                    $this->errores[] = "Fila {$this->procesados}: No se pudo crear/encontrar el tipo de oro '{$tipoOroNombre}'";
                    $this->omitidos++;
                    return null;
                }
            }

            // Determinar empresa_id - prioridad: empresa especificada > empresa del constructor
            $empresaId = $this->empresaId;
            if (!empty($empresaRazonSocial) && $empresaRazonSocial !== 'Global') {
                $empresa = Empresa::where('razon_social', $empresaRazonSocial)->first();
                if ($empresa) {
                    $empresaId = $empresa->id;
                } else {
                    $this->errores[] = "Fila {$this->procesados}: No se encontró la empresa '{$empresaRazonSocial}'";
                    $this->omitidos++;
                    return null;
                }
            }

            // Verificar si el producto ya existe
            $productoExistente = Producto::where('nombre', $nombre)
                ->where('empresa_id', $empresaId)
                ->first();

            $datosProducto = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'codigo_barras' => $codigoBarras ?: null,
                'tipo_producto_id' => $tipoProducto->id,
                'tipo_oro_id' => $tipoOro ? $tipoOro->id : null,
                'precio_venta' => $precioVenta,
                'precio_compra' => $precioCompra,
                'empresa_id' => $empresaId
            ];

            if ($productoExistente) {
                if ($this->modoImportacion === 'crear') {
                    $this->duplicados[] = $nombre; // Agregar nombre a duplicados
                    $this->errores[] = "Fila {$this->procesados}: El producto '{$nombre}' ya existe y el modo es solo crear";
                    $this->omitidos++;
                    return null;
                }

                if ($this->modoImportacion === 'actualizar' || $this->modoImportacion === 'crear_actualizar') {
                    $productoExistente->update($datosProducto);
                    $this->actualizados++;
                    return $productoExistente;
                }
            } else {
                if ($this->modoImportacion === 'actualizar') {
                    $this->errores[] = "Fila {$this->procesados}: El producto '{$nombre}' no existe y el modo es solo actualizar";
                    $this->omitidos++;
                    return null;
                }

                $producto = new Producto($datosProducto);
                $this->creados++;
                return $producto;
            }

        } catch (Throwable $e) {
            $this->errores[] = "Fila {$this->procesados}: Error inesperado - " . $e->getMessage();
            $this->omitidos++;
            Log::error('Error en importación de productos', [
                'fila' => $this->procesados,
                'datos' => $row,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Procesar precio removiendo formato de moneda
     */
    private function procesarPrecio($precio)
    {
        if (empty($precio) || is_null($precio)) {
            return null;
        }

        // Si ya es un número, convertirlo directamente
        if (is_numeric($precio)) {
            return (float) $precio;
        }

        // Si es string, procesar formato de moneda
        $precio = (string) $precio;

        // Remover símbolos de moneda, espacios y puntos (separadores de miles)
        $precio = str_replace(['$', ' ', '.'], '', $precio);

        // Reemplazar coma por punto para decimales
        $precio = str_replace(',', '.', $precio);

        // Validar que sea un número válido
        if (!is_numeric($precio)) {
            return null;
        }

        return (float) $precio;
    }

    /**
     * Buscar o crear tipo de producto
     */
    private function buscarOCrearTipoProducto($nombre)
    {
        // Primero buscar en la empresa específica
        if ($this->empresaId) {
            $tipoProducto = TipoProducto::where('nombre', $nombre)
                ->where('empresa_id', $this->empresaId)
                ->first();

            if ($tipoProducto) {
                return $tipoProducto;
            }
        }

        // Buscar en tipos globales
        $tipoProducto = TipoProducto::where('nombre', $nombre)
            ->whereNull('empresa_id')
            ->first();

        if ($tipoProducto) {
            return $tipoProducto;
        }

        // Crear nuevo tipo de producto para la empresa
        try {
            return TipoProducto::create([
                'nombre' => $nombre,
                'empresa_id' => $this->empresaId
            ]);
        } catch (Throwable $e) {
            Log::error('Error creando tipo de producto', [
                'nombre' => $nombre,
                'empresa_id' => $this->empresaId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Buscar o crear tipo de oro
     */
    private function buscarOCrearTipoOro($nombre)
    {
        // Primero buscar en la empresa específica
        if ($this->empresaId) {
            $tipoOro = TipoOro::where('nombre', $nombre)
                ->where('empresa_id', $this->empresaId)
                ->first();

            if ($tipoOro) {
                return $tipoOro;
            }
        }

        // Buscar en tipos globales
        $tipoOro = TipoOro::where('nombre', $nombre)
            ->whereNull('empresa_id')
            ->first();

        if ($tipoOro) {
            return $tipoOro;
        }

        // Crear nuevo tipo de oro para la empresa
        try {
            return TipoOro::create([
                'nombre' => $nombre,
                'valor_de_mercado' => 0, // Valor por defecto
                'empresa_id' => $this->empresaId
            ]);
        } catch (Throwable $e) {
            Log::error('Error creando tipo de oro', [
                'nombre' => $nombre,
                'empresa_id' => $this->empresaId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Validación de las filas
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            // Formato nuevo (como export) - aceptar string o numeric
            'codigo_de_barras' => ['nullable', 'max:255'], // Removido 'string' para aceptar números
            'tipo_de_producto' => ['nullable', 'string', 'max:255'],
            'tipo_de_oro' => ['nullable', 'max:255'], // Removido 'string' para aceptar números
            'precio_de_venta' => ['nullable'], // Removido 'string' para aceptar números
            'precio_de_compra' => ['nullable'], // Removido 'string' para aceptar números
            'empresa' => ['nullable', 'string', 'max:255'],
            // Formato legacy (compatibilidad)
            'tipo_producto' => ['nullable', 'string', 'max:255'],
            'tipo_oro' => ['nullable', 'max:255'], // Removido 'string' para aceptar números
        ];
    }

    /**
     * Mensajes personalizados de validación
     */
    public function customValidationMessages()
    {
        return [
            'nombre.required' => 'El nombre del producto es obligatorio',
            'nombre.max' => 'El nombre del producto no puede exceder 255 caracteres',
            'codigo_de_barras.max' => 'El código de barras no puede exceder 255 caracteres',
            'tipo_de_producto.max' => 'El tipo de producto no puede exceder 255 caracteres',
            'tipo_de_oro.max' => 'El tipo de oro no puede exceder 255 caracteres',
            'tipo_producto.max' => 'El tipo de producto no puede exceder 255 caracteres',
            'tipo_oro.max' => 'El tipo de oro no puede exceder 255 caracteres',
            'empresa.max' => 'El nombre de la empresa no puede exceder 255 caracteres',
        ];
    }

    /**
     * Tamaño del chunk para lectura
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Manejar errores
     */
    public function onError(Throwable $e)
    {
        $this->errores[] = "Error general: " . $e->getMessage();
        Log::error('Error en importación de productos', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Manejar fallas de validación
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errores[] = "Fila {$failure->row()}: " . implode(', ', $failure->errors());
            $this->omitidos++;
        }
    }

    /**
     * Obtener resumen de la importación
     */
    public function getResumen(): array
    {
        return [
            'procesados' => $this->procesados,
            'creados' => $this->creados,
            'actualizados' => $this->actualizados,
            'omitidos' => $this->omitidos,
            'errores' => $this->errores,
            'duplicados' => $this->duplicados
        ];
    }

    /**
     * Obtener estadísticas completas para el historial
     */
    public function getImportStats(): array
    {
        $totalExitosos = $this->creados + $this->actualizados;
        $totalFallidos = $this->omitidos;
        $totalDuplicados = count($this->duplicados);

        // Determinar estado de la importación
        $status = 'completed';
        if ($totalFallidos > 0 || !empty($this->errores)) {
            $status = $totalExitosos > 0 ? 'completed_with_errors' : 'failed';
        }

        return [
            'total_rows' => $this->procesados,
            'successful_imports' => $totalExitosos,
            'skipped_duplicates' => $totalDuplicados,
            'failed_imports' => $totalFallidos,
            'created_products' => $this->creados,
            'updated_products' => $this->actualizados,
            'duplicate_products' => $this->duplicados,
            'errors' => $this->errores,
            'status' => $status
        ];
    }
}
