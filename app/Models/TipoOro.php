<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class TipoOro extends Model
{
    use LogsActivity;

    protected $fillable = [
        'nombre',
        'valor_de_mercado',
        'observacion',
        'empresa_id'
    ];

    protected $casts = [
        'valor_de_mercado' => 'decimal:2'
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

    // Scope para tipos globales (sin empresa)
    public function scopeGlobales($query)
    {
        return $query->whereNull('empresa_id');
    }

    // Accessor para formatear el valor de mercado
    public function getValorDeMercadoFormateadoAttribute()
    {
        return '$' . number_format($this->valor_de_mercado, 2, ',', '.');
    }

    /**
     * Identificador personalizado para logs
     */
    protected function getActivityIdentifier(): string
    {
        $empresa = $this->empresa ? " - {$this->empresa->razon_social}" : " (Global)";
        return "Tipo Oro: {$this->nombre}{$empresa}";
    }
}
