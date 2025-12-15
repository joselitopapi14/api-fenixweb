<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ResolucionFacturacion extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'resoluciones_facturacion';

    protected $fillable = [
        'empresa_id',
        'prefijo',
        'resolucion',
        'fecha_resolucion',
        'fecha_inicial',
        'fecha_final',
        'clave_tecnica',
        'consecutivo_inicial',
        'consecutivo_final',
        'consecutivo_actual',
        'envia_dian',
        'activo',
    ];

    protected $casts = [
        'fecha_resolucion' => 'date',
        'fecha_inicial' => 'date',
        'fecha_final' => 'date',
        'consecutivo_inicial' => 'integer',
        'consecutivo_final' => 'integer',
        'consecutivo_actual' => 'integer',
        'envia_dian' => 'boolean',
        'activo' => 'boolean',
    ];

    // Activity Log Configuration
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'empresa_id', 'prefijo', 'resolucion', 'fecha_resolucion',
                'fecha_inicial', 'fecha_final', 'clave_tecnica',
                'consecutivo_inicial', 'consecutivo_final', 'consecutivo_actual',
                'envia_dian', 'activo'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->where(function($q) use ($termino) {
            $q->where('prefijo', 'like', "%{$termino}%")
              ->orWhere('resolucion', 'like', "%{$termino}%")
              ->orWhere('clave_tecnica', 'like', "%{$termino}%")
              ->orWhereHas('empresa', function($eq) use ($termino) {
                  $eq->where('nombre', 'like', "%{$termino}%");
              });
        });
    }

    // Accessors
    public function getEstadoTextoAttribute()
    {
        return $this->activo ? 'Activo' : 'Inactivo';
    }

    public function getEnviaDianTextoAttribute()
    {
        return $this->envia_dian ? 'Sí' : 'No';
    }

    public function getConsecutivosDisponiblesAttribute()
    {
        return $this->consecutivo_final - $this->consecutivo_actual + 1;
    }

    public function getRangoConsecutivosAttribute()
    {
        return number_format($this->consecutivo_inicial) . ' - ' . number_format($this->consecutivo_final);
    }

    // Validation rules
    public static function rules($id = null)
    {
        return [
            'empresa_id' => 'required|exists:empresas,id',
            'prefijo' => 'required|string|max:10',
            'resolucion' => 'nullable|string|max:100',
            'fecha_resolucion' => 'nullable|date',
            'fecha_inicial' => 'nullable|date',
            'fecha_final' => 'nullable|date|after_or_equal:fecha_inicial',
            'clave_tecnica' => 'nullable|string|max:255',
            'consecutivo_inicial' => 'required|integer|min:1',
            'consecutivo_final' => 'required|integer|gte:consecutivo_inicial',
            'consecutivo_actual' => 'required|integer|gte:consecutivo_inicial|lte:consecutivo_final',
            'envia_dian' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    // Custom validation messages
    public static function messages()
    {
        return [
            'empresa_id.required' => 'La empresa es obligatoria.',
            'empresa_id.exists' => 'La empresa seleccionada no existe.',
            'prefijo.required' => 'El prefijo es obligatorio.',
            'prefijo.max' => 'El prefijo no puede tener más de 10 caracteres.',
            'fecha_final.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
            'consecutivo_inicial.required' => 'El consecutivo inicial es obligatorio.',
            'consecutivo_inicial.min' => 'El consecutivo inicial debe ser mayor a 0.',
            'consecutivo_final.required' => 'El consecutivo final es obligatorio.',
            'consecutivo_final.gte' => 'El consecutivo final debe ser mayor o igual al consecutivo inicial.',
            'consecutivo_actual.required' => 'El consecutivo actual es obligatorio.',
            'consecutivo_actual.gte' => 'El consecutivo actual debe ser mayor o igual al consecutivo inicial.',
            'consecutivo_actual.lte' => 'El consecutivo actual debe ser menor o igual al consecutivo final.',
        ];
    }
}
