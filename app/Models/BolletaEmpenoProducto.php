<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BolletaEmpenoProducto extends Model
{
    use HasFactory;

    protected $table = 'boleta_empeno_productos';

    protected $fillable = [
        'boleta_empeno_id',
        'producto_id',
        'cantidad',
        'descripcion_adicional'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2'
    ];

    // Relaciones
    public function boletaEmpeno()
    {
        return $this->belongsTo(BolletaEmpeno::class, 'boleta_empeno_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    // Accessors
    public function getEsOroAttribute()
    {
        return $this->producto && $this->producto->es_oro;
    }
}
