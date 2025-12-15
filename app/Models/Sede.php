<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Sede extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'direccion',
        'telefono',
        'email',
        'departamento_id',
        'municipio_id',
        'comuna_id',
        'barrio_id',
        'es_principal',
        'activa',
        'observaciones'
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'activa' => 'boolean',
    ];

    // Relaciones
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class);
    }

    public function comuna()
    {
        return $this->belongsTo(Comuna::class);
    }

    public function barrio()
    {
        return $this->belongsTo(Barrio::class);
    }

    // Accessors
    public function getDireccionCompletaAttribute()
    {
        $direccion = $this->direccion;

        $ubicacion = collect([
            $this->barrio?->nombre,
            $this->comuna?->nombre,
            $this->municipio?->name,
            $this->departamento?->name
        ])->filter()->join(', ');

        return $ubicacion ? "{$direccion}, {$ubicacion}" : $direccion;
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    public function scopePrincipal($query)
    {
        return $query->where('es_principal', true);
    }

    public function scopeDeEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }
}
