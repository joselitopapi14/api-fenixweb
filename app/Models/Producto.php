<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class Producto extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo_producto_id',
        'tipo_oro_id',
        'imagen',
        'empresa_id',
        'codigo_barras',
        'precio_venta',
        'precio_compra',
        'tipo_medida_id',
        'peso'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'precio_venta' => 'decimal:2',
        'precio_compra' => 'decimal:2'
    ];

    // Relaciones
    public function tipoProducto()
    {
        return $this->belongsTo(TipoProducto::class);
    }

    public function tipoOro()
    {
        return $this->belongsTo(TipoOro::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function tipoMedida()
    {
        return $this->belongsTo(TipoMedida::class);
    }

    public function unidadMedida()
    {
        return $this->belongsTo(TipoMedida::class, 'tipo_medida_id');
    }

    public function impuestos()
    {
        return $this->belongsToMany(Impuesto::class, 'producto_impuesto')
                    ->withPivot('porcentaje')
                    ->withTimestamps();
    }

    // Scopes
    public function scopeDeEmpresa($query, $empresaId)
    {
        if ($empresaId) {
            return $query->where('empresa_id', $empresaId);
        }
        return $query;
    }

    public function scopeGlobales($query)
    {
        return $query->whereNull('empresa_id');
    }

    public function scopePorTipoProducto($query, $tipoProductoId)
    {
        return $query->where('tipo_producto_id', $tipoProductoId);
    }

    public function scopeConOro($query)
    {
        return $query->whereHas('tipoProducto', function ($q) {
            $q->where('id', 1); // Asumiendo que el tipo producto con ID 1 es oro
        });
    }

    public function scopePorCodigoBarras($query, $codigoBarras)
    {
        return $query->where('codigo_barras', $codigoBarras);
    }

    public function scopeConPrecioVentaEntre($query, $min, $max)
    {
        $query->where('precio_venta', '>=', $min);
        if ($max) {
            $query->where('precio_venta', '<=', $max);
        }
        return $query;
    }

    public function scopeConPrecioCompraEntre($query, $min, $max)
    {
        $query->where('precio_compra', '>=', $min);
        if ($max) {
            $query->where('precio_compra', '<=', $max);
        }
        return $query;
    }

    // Accessors
    public function getImagenUrlAttribute()
    {
        if ($this->imagen) {
            return asset('storage/' . $this->imagen);
        }
        return null;
    }

    public function getEsOroAttribute()
    {
        return $this->tipo_producto_id == 1;
    }

    /**
     * Identificador personalizado para logs
     */
    protected function getActivityIdentifier(): string
    {
        $empresa = $this->empresa ? " - {$this->empresa->razon_social}" : " (Global)";
        return "Producto: {$this->nombre}{$empresa}";
    }
}
