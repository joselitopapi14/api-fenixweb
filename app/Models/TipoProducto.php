<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class TipoProducto extends Model
{
    use LogsActivity;

    protected $fillable = [
        'nombre',
        'empresa_id'
    ];

    // Relación con empresa
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    // Relación con productos
    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    // Scope para filtrar por empresa
    public function scopeDeEmpresa($query, $empresaId)
    {
        if ($empresaId) {
            return $query->where('empresa_id', $empresaId);
        }
        return $query;
    }

    // Scope para productos globales (sin empresa)
    public function scopeGlobales($query)
    {
        return $query->whereNull('empresa_id');
    }

    /**
     * Identificador personalizado para logs
     */
    protected function getActivityIdentifier(): string
    {
        $empresa = $this->empresa ? " - {$this->empresa->razon_social}" : " (Global)";
        return "Tipo Producto: {$this->nombre}{$empresa}";
    }
}
