<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MovimientoInventario extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'movimiento_inventarios';

    protected $fillable = [
        'numero_contrato',
        'empresa_id',
        'sede_id',
        'tipo_movimiento_id',
        'user_id',
        'fecha_movimiento',
        'observaciones',
        'observaciones_generales',
        'anulado',
        'razon_anulacion',
        'anulado_por',
        'fecha_anulacion'
    ];

    protected $casts = [
        'fecha_movimiento' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'anulado' => 'boolean',
        'fecha_anulacion' => 'datetime'
    ];

    // Relaciones
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function tipoMovimiento()
    {
        return $this->belongsTo(TipoMovimiento::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function anuladoPor()
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    public function productos()
    {
        return $this->hasMany(MovimientoInventarioProducto::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('anulado', false);
    }

    public function scopeAnulados($query)
    {
        return $query->where('anulado', true);
    }

    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    // Métodos de utilidad
    public function generarNumeroContrato()
    {
        if ($this->numero_contrato) {
            return $this->numero_contrato;
        }

        $fecha = Carbon::parse($this->fecha_movimiento ?? now());
        $anio = $fecha->year;
        $mes = $fecha->format('m');

        // Prefijo basado en el tipo de movimiento
        $prefijo = $this->tipoMovimiento->es_suma ? 'MIN' : 'MOU';

        // Buscar el último número del mes
        $ultimoNumero = static::where('empresa_id', $this->empresa_id)
            ->whereYear('fecha_movimiento', $anio)
            ->whereMonth('fecha_movimiento', $mes)
            ->whereHas('tipoMovimiento', function($query) {
                $query->where('es_suma', $this->tipoMovimiento->es_suma);
            })
            ->orderBy('numero_contrato', 'desc')
            ->value('numero_contrato');

        $siguiente = 1;
        if ($ultimoNumero) {
            // Extraer el número secuencial del formato
            preg_match('/(\d+)$/', $ultimoNumero, $matches);
            if (!empty($matches[1])) {
                $siguiente = intval($matches[1]) + 1;
            }
        }

        $numeroContrato = sprintf('%s-%04d-%s-%04d',
            $prefijo,
            $this->empresa_id,
            $anio . $mes,
            $siguiente
        );

        $this->numero_contrato = $numeroContrato;
        $this->save();

        return $numeroContrato;
    }

    public function puedeSerAnulado()
    {
        return !$this->anulado;
    }

    public function anular($razon, $usuario = null)
    {
        if (!$this->puedeSerAnulado()) {
            throw new \Exception('Este movimiento de inventario no puede ser anulado.');
        }

        $this->update([
            'anulado' => true,
            'razon_anulacion' => $razon,
            'anulado_por' => $usuario ? $usuario->id : auth()->id(),
            'fecha_anulacion' => now()
        ]);
    }

    // Accessors
    public function getEstadoTextAttribute()
    {
        return $this->anulado ? 'Anulado' : 'Activo';
    }

    public function getEstadoColorAttribute()
    {
        return $this->anulado ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
    }

    public function getTotalProductosAttribute()
    {
        return $this->productos->sum('cantidad');
    }

    public function getTotalValorAttribute()
    {
        return $this->productos->sum(function ($movimientoProducto) {
            return $movimientoProducto->cantidad * ($movimientoProducto->producto->precio_compra ?? 0);
        });
    }
}
