<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class TipoInteres extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'tipo_interes';

    protected $fillable = [
        'nombre',
        'porcentaje',
        'activo',
        'descripcion',
        'empresa_id'
    ];

    protected $casts = [
        'porcentaje' => 'decimal:2',
        'activo' => 'boolean',
    ];

    /**
     * Relación con Empresa (opcional)
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para filtrar por empresa
     */
    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    /**
     * Scope para tipos de interés globales (sin empresa)
     */
    public function scopeGlobales($query)
    {
        return $query->whereNull('empresa_id');
    }

    /**
     * Scope para tipos de interés con empresa
     */
    public function scopeConEmpresa($query)
    {
        return $query->whereNotNull('empresa_id');
    }

    // Accessors
    public function getPorcentajeFormateadoAttribute()
    {
        return number_format($this->porcentaje, 2) . '%';
    }

    /**
     * Obtiene el nombre de la empresa o 'Global' si no tiene empresa
     */
    public function getNombreEmpresaAttribute()
    {
        return $this->empresa ? $this->empresa->nombre : 'Global';
    }

    /**
     * Indica si es un tipo de interés global (sin empresa específica)
     */
    public function getEsGlobalAttribute()
    {
        return is_null($this->empresa_id);
    }

    // Alias para mantener compatibilidad
    public function getPorcentajeFormattedAttribute()
    {
        return $this->porcentaje_formateado;
    }
}
