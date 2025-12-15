<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class MovimientoInventarioProducto extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'movimiento_inventario_productos';

    protected $fillable = [
        'movimiento_inventario_id',
        'producto_id',
        'cantidad',
        'descripcion_adicional'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2'
    ];

    // Relaciones
    public function movimientoInventario()
    {
        return $this->belongsTo(MovimientoInventario::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    // Accessors
    public function getCantidadFormateadaAttribute()
    {
        return number_format($this->cantidad, 2);
    }
}
