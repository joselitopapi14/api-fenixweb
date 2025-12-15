<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;
use Carbon\Carbon;

class Cuota extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'bolleta_empeno_id',
        'user_id',
        'monto_sugerido',
        'monto_pagado',
        'interes_calculado',
        'fecha_abono',
        'observaciones',
        'estado'
    ];

    protected $casts = [
        'monto_sugerido' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'interes_calculado' => 'decimal:2',
        'fecha_abono' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relaciones
    public function boletaEmpeno()
    {
        return $this->belongsTo(BolletaEmpeno::class, 'bolleta_empeno_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopePagadas($query)
    {
        return $query->where('estado', 'pagada');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeDeEmpresa($query, $empresaId)
    {
        return $query->whereHas('boletaEmpeno', function($q) use ($empresaId) {
            $q->where('empresa_id', $empresaId);
        });
    }

    // Métodos de cálculo
    public static function calcularMontoSugerido($boletaEmpeno, $fechaAbono = null)
    {
        if (!$fechaAbono) {
            $fechaAbono = Carbon::now()->startOfDay();
        } else {
            $fechaAbono = Carbon::parse($fechaAbono)->startOfDay();
        }

        $fechaPrestamo = ($boletaEmpeno->fecha_prestamo ?? $boletaEmpeno->created_at)->startOfDay();
        $fechaVencimiento = $boletaEmpeno->fecha_vencimiento ? $boletaEmpeno->fecha_vencimiento->startOfDay() : null;

        // Obtener tasa de interés
        $tasaInteresMensual = 0;
        if ($boletaEmpeno->tipoInteres) {
            $tasaInteresMensual = $boletaEmpeno->tipoInteres->porcentaje;
        } elseif ($boletaEmpeno->empresa->tiposInteres->where('activo', true)->count() > 0) {
            $tasaInteresMensual = $boletaEmpeno->empresa->tiposInteres->where('activo', true)->avg('porcentaje');
        } else {
            $tasaInteresMensual = 3.0; // 3% mensual por defecto
        }

        // Obtener total ya abonado y fecha de última cuota
        $totalAbonado = $boletaEmpeno->cuotas()->sum('monto_pagado');
        $ultimaCuota = $boletaEmpeno->cuotas()->orderBy('fecha_abono', 'desc')->first();

        // Determinar fecha desde la cual calcular intereses (solo día, sin horas)
        $fechaBase = $ultimaCuota ? $ultimaCuota->fecha_abono->startOfDay() : $fechaPrestamo;

        // Calcular días transcurridos desde la última cuota (o desde el préstamo inicial)
        // Usar diffInDays() que cuenta días completos sin considerar horas
        $diasTranscurridos = $fechaBase->diffInDays($fechaAbono);

        // Calcular intereses acumulados desde la última cuota
        $interesesCalculados = ($boletaEmpeno->monto_prestamo * $tasaInteresMensual / 100) * ($diasTranscurridos / 30);

        // Calcular meses totales del préstamo
        $mesesPrestamo = $fechaVencimiento ? $fechaPrestamo->diffInMonths($fechaVencimiento) : 1;
        $mesesPrestamo = $mesesPrestamo > 0 ? $mesesPrestamo : 1;

        // Calcular cuota mensual teórica (capital + intereses totales) / meses
        $interesTotalEstimado = ($boletaEmpeno->monto_prestamo * $tasaInteresMensual / 100) * $mesesPrestamo;
        $totalEstimado = $boletaEmpeno->monto_prestamo + $interesTotalEstimado;
        $cuotaMensualCompleta = $totalEstimado / $mesesPrestamo;

        // El monto sugerido debe ser solo el interés acumulado desde la última cuota
        // Si no hay cuotas previas, puede incluir una fracción del capital
        if (!$ultimaCuota) {
            // Primera cuota: incluir proporción del capital más interés
            $cuotaSugerida = $cuotaMensualCompleta;
        } else {
            // Cuotas siguientes: solo interés acumulado desde la última cuota
            $cuotaSugerida = $interesesCalculados;
        }

        return [
            'monto_sugerido' => max(0, $cuotaSugerida),
            'interes_calculado' => $interesesCalculados,
            'interes_minimo' => $interesesCalculados, // Interés mínimo por defecto
            'cuota_mensual_completa' => $cuotaMensualCompleta, // Cuota completa mensual
            'total_abonado' => $totalAbonado,
            'dias_transcurridos' => $diasTranscurridos,
            'tasa_interes' => $tasaInteresMensual
        ];
    }

    /**
     * Calcular información de cuotas (número actual y total)
     */
    public static function calcularInfoCuotas($boletaEmpeno, $fechaAbono = null)
    {
        if (!$fechaAbono) {
            $fechaAbono = Carbon::now()->startOfDay();
        } else {
            $fechaAbono = Carbon::parse($fechaAbono)->startOfDay();
        }

        $fechaPrestamo = ($boletaEmpeno->fecha_prestamo ?? $boletaEmpeno->created_at)->startOfDay();
        $fechaVencimiento = $boletaEmpeno->fecha_vencimiento ? $boletaEmpeno->fecha_vencimiento->startOfDay() : null;

        // Calcular total de cuotas (meses del préstamo)
        $totalCuotas = $fechaVencimiento ? $fechaPrestamo->diffInMonths($fechaVencimiento) : 1;
        $totalCuotas = $totalCuotas > 0 ? $totalCuotas : 1;

        // Calcular cuota actual basada en meses transcurridos desde el préstamo
        $mesesTranscurridos = $fechaPrestamo->diffInMonths($fechaAbono);
        $cuotaActual = min($mesesTranscurridos + 1, $totalCuotas);

        // Contar cuotas ya pagadas
        $cuotasPagadas = $boletaEmpeno->cuotas()->count();

        return [
            'cuota_actual' => $cuotaActual,
            'total_cuotas' => $totalCuotas,
            'cuotas_pagadas' => $cuotasPagadas,
            'meses_transcurridos' => $mesesTranscurridos
        ];
    }

    // Accessor
    public function getDiferenciaPagoAttribute()
    {
        return $this->monto_pagado - $this->monto_sugerido;
    }

    protected function getActivityIdentifier(): string
    {
        $boleta = $this->boletaEmpeno ? " - Boleta: {$this->boletaEmpeno->numero_contrato}" : "";
        return "Cuota ID: {$this->id}{$boleta}";
    }
}
