<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Concepto extends Model
{
    use LogsActivity;

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
        'empresa_id'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Relación con empresa
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    // Scope para filtrar por empresa
    public function scopeDeEmpresa($query, $empresaId)
    {
        if ($empresaId) {
            return $query->where('empresa_id', $empresaId);
        }
        return $query;
    }

    // Scope para conceptos activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Identificador personalizado para logs
     */
    protected function getActivityIdentifier(): string
    {
        $empresa = $this->empresa ? " - {$this->empresa->razon_social}" : " (Global)";
        return "Concepto: {$this->nombre}{$empresa}";
    }
}
