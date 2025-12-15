<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class TipoMovimiento extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'tipo_movimientos';

    protected $fillable = [
        'nombre',
        'es_suma',
        'activo',
        'descripcion',
        'empresa_id',
    ];

    protected $casts = [
        'es_suma' => 'boolean',
        'activo' => 'boolean',
    ];

    /**
     * Relación con Empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Accessor para mostrar el tipo de movimiento de forma visual
     */
    public function getTipoMovimientoAttribute(): string
    {
        return $this->es_suma ? 'Suma (+)' : 'Resta (-)';
    }

    /**
     * Accessor para obtener el ícono del movimiento
     */
    public function getIconoMovimientoAttribute(): string
    {
        if ($this->es_suma) {
            return '<div class="w-6 h-6 bg-green-500 rounded-md flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>';
        } else {
            return '<div class="w-6 h-6 bg-red-500 rounded-md flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                    </div>';
        }
    }

    /**
     * Accessor para obtener el color del movimiento
     */
    public function getColorMovimientoAttribute(): string
    {
        return $this->es_suma ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
    }

    /**
     * Scope para tipos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para movimientos de suma
     */
    public function scopeSuma($query)
    {
        return $query->where('es_suma', true);
    }

    /**
     * Scope para movimientos de resta
     */
    public function scopeResta($query)
    {
        return $query->where('es_suma', false);
    }

    /**
     * Scope para filtrar por empresa
     */
    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    public function boletasEmpenos(): HasMany
    {
        return $this->hasMany(BoletaEmpeno::class);
    }
}
