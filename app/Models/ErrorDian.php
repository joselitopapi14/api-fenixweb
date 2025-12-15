<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorDian extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'errores_dian';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'codigo',
        'descripcion',
        'mensaje_original',
        'hash_error',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Crear o encontrar un error DIAN basado en el mensaje original
     *
     * @param string $mensajeOriginal
     * @param string|null $codigo
     * @param string|null $descripcion
     * @return ErrorDian
     */
    public static function crearOEncontrar(string $mensajeOriginal, ?string $codigo = null, ?string $descripcion = null): self
    {
        $hashError = md5($mensajeOriginal);

        // Buscar si ya existe
        $errorExistente = self::where('hash_error', $hashError)->first();

        if ($errorExistente) {
            return $errorExistente;
        }

        // Si no existe, crear uno nuevo
        return self::create([
            'codigo' => $codigo,
            'descripcion' => $descripcion ?: $mensajeOriginal,
            'mensaje_original' => $mensajeOriginal,
            'hash_error' => $hashError,
        ]);
    }

    /**
     * Extraer código del error desde el mensaje original
     *
     * @param string $mensajeOriginal
     * @return string|null
     */
    public static function extraerCodigo(string $mensajeOriginal): ?string
    {
        // Buscar patrón "Regla: CODIGO"
        if (preg_match('/Regla:\s*([A-Z0-9]+[a-z]*)/i', $mensajeOriginal, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extraer descripción del error desde el mensaje original
     *
     * @param string $mensajeOriginal
     * @return string
     */
    public static function extraerDescripcion(string $mensajeOriginal): string
    {
        // Buscar patrón "Rechazo: DESCRIPCION"
        if (preg_match('/Rechazo:\s*(.+)$/i', $mensajeOriginal, $matches)) {
            return trim($matches[1]);
        }

        // Si no encuentra el patrón, usar el mensaje completo
        return $mensajeOriginal;
    }
}
