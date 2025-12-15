<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientImportHistory extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'client_import_histories';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'filename',
        'stored_path',
        'user_id',
        'empresa_id',
        'modo_importacion',
        'total_rows',
        'successful_imports',
        'skipped_duplicates',
        'failed_imports',
        'created_clients',
        'updated_clients',
        'duplicate_clients',
        'errors',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'duplicate_clients' => 'array',
        'errors' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el usuario que realizó la importación
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con la empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeByStatus($query, $status)
    {
        if ($status && $status !== 'all') {
            return $query->where('status', $status);
        }
        return $query;
    }

    /**
     * Scope para filtrar por empresa
     */
    public function scopeByEmpresa($query, $empresaId)
    {
        if ($empresaId) {
            return $query->where('empresa_id', $empresaId);
        }
        return $query;
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeByDateRange($query, $from = null, $to = null)
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        return $query;
    }

    /**
     * Obtener el porcentaje de éxito de la importación
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }
        return round(($this->successful_imports / $this->total_rows) * 100, 2);
    }

    /**
     * Determinar si la importación tuvo errores
     */
    public function getHasErrorsAttribute(): bool
    {
        return $this->failed_imports > 0 || !empty($this->errors);
    }

    /**
     * Obtener el color del estado para la UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'green',
            'completed_with_errors' => 'yellow',
            'failed' => 'red',
            default => 'gray'
        };
    }

    /**
     * Obtener el texto del estado en español
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'Completada',
            'completed_with_errors' => 'Completada con errores',
            'failed' => 'Fallida',
            default => 'Desconocido'
        };
    }
}
