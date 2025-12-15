<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use App\Traits\LogsActivity;
use Spatie\Activitylog\Traits\LogsActivity as SpatieLogsActivity;
use Spatie\Activitylog\LogOptions;

class Cliente extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, SpatieLogsActivity;

    protected $table = 'clientes';

    protected $fillable = [
        'nombres',
        'apellidos',
        'razon_social',
        'email',
        'tipo_documento_id',
        'cedula_nit',
        'dv',
        'fecha_nacimiento',
        'representante_legal',
        'cedula_representante',
        'email_representante',
        'direccion_representante',
        'direccion',
        'departamento_id',
        'municipio_id',
        'comuna_id',
        'barrio_id',
        'tipo_persona_id',
        'tipo_responsabilidad_id',
        'telefono_fijo',
        'celular',
        'foto',
        'empresa_id'
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['foto_url'];

    // Relaciones geográficas
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

    // Relación con empresa
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    // Relación con tipo de documento
    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    // Relaciones con tipos
    public function tipoPersona()
    {
        return $this->belongsTo(TipoPersona::class);
    }

    public function tipoResponsabilidad()
    {
        return $this->belongsTo(TipoResponsabilidad::class);
    }

    // Relación many-to-many con redes sociales
    public function redesSociales()
    {
        return $this->belongsToMany(RedSocial::class, 'cliente_red_social')
                    ->withPivot('valor')
                    ->withTimestamps();
    }

    // Scopes
    public function scopeByEmpresa(Builder $query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    public function scopeSearch(Builder $query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('nombres', 'like', "%{$search}%")
              ->orWhere('apellidos', 'like', "%{$search}%")
              ->orWhere('razon_social', 'like', "%{$search}%")
              ->orWhereRaw("CONCAT(nombres, ' ', apellidos) like ?", ["%{$search}%"])
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('cedula_nit', 'like', "%{$search}%")
              ->orWhere('celular', 'like', "%{$search}%")
              ->orWhere('direccion', 'like', "%{$search}%");
        });
    }

    public function scopeByUbicacion(Builder $query, $departamentoId = null, $municipioId = null, $comunaId = null, $barrioId = null)
    {
        if ($departamentoId) {
            $query->where('departamento_id', $departamentoId);
        }
        if ($municipioId) {
            $query->where('municipio_id', $municipioId);
        }
        if ($comunaId) {
            $query->where('comuna_id', $comunaId);
        }
        if ($barrioId) {
            $query->where('barrio_id', $barrioId);
        }
        return $query;
    }

    // Accessors
    public function getNombreCompletoAttribute()
    {
        if ($this->esPersonaJuridica()) {
            return $this->razon_social ?: '';
        }

        return trim($this->nombres . ' ' . $this->apellidos);
    }

    public function getFotoUrlAttribute()
    {
        if ($this->foto && Storage::disk('public')->exists($this->foto)) {
            return Storage::disk('public')->url($this->foto);
        }

        // Default avatar con iniciales
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->nombre_completo) . '&color=7F9CF5&background=EBF4FF&size=200';
    }

    public function getUbicacionCompletaAttribute()
    {
        $ubicacion = [];

        if ($this->barrio) {
            $ubicacion[] = $this->barrio->nombre;
        }
        if ($this->comuna) {
            $ubicacion[] = $this->comuna->nombre;
        }
        if ($this->municipio) {
            $ubicacion[] = $this->municipio->name;
        }
        if ($this->departamento) {
            $ubicacion[] = $this->departamento->name;
        }

        return implode(', ', $ubicacion);
    }

    /**
     * Retorna el teléfono preferido del cliente: primero teléfono fijo, luego celular.
     */
    public function getTelefonoAttribute()
    {
        return $this->telefono_fijo ?: $this->celular ?: null;
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'nombres',
                'apellidos',
                'razon_social',
                'email',
                'tipo_documento_id',
                'cedula_nit',
                'dv',
                'fecha_nacimiento',
                'representante_legal',
                'cedula_representante',
                'email_representante',
                'direccion_representante',
                'direccion',
                'departamento.name',
                'municipio.name',
                'comuna.nombre',
                'barrio.nombre',
                'telefono_fijo',
                'celular',
                'foto'
            ])
            ->logOnlyDirty()
            ->useLogName('clientes')
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Cliente registrado',
                'updated' => 'Cliente actualizado',
                'deleted' => 'Cliente eliminado',
                default => $eventName
            });
    }

    public function getActivityIdentifier(): string
    {
        return "Cliente: {$this->nombre_completo} (ID: {$this->id})";
    }

    // Métodos para manejar tipos de cliente
    public function esPersonaJuridica(): bool
    {
        return $this->tipo_documento_id == 6;
    }

    public function esPersonaNatural(): bool
    {
        return !$this->esPersonaJuridica();
    }

    public function getDocumentoCompletoAttribute(): string
    {
        if ($this->dv) {
            return $this->cedula_nit . '-' . $this->dv;
        }

        return $this->cedula_nit ?: '';
    }

    public function getNumeroDocumentoAttribute(): string
    {
        return $this->getDocumentoCompletoAttribute();
    }

    // Mutators
    public function setNombresAttribute($value)
    {
        $this->attributes['nombres'] = mb_strtoupper(trim($value));
    }

    public function setApellidosAttribute($value)
    {
        $this->attributes['apellidos'] = mb_strtoupper(trim($value));
    }

    public function setCedulaNitAttribute($value)
    {
        $this->attributes['cedula_nit'] = preg_replace('/[^0-9]/', '', $value);
    }

    public function setCelularAttribute($value)
    {
        $this->attributes['celular'] = preg_replace('/[^0-9]/', '', $value);
    }

    public function setTelefonoFijoAttribute($value)
    {
        $this->attributes['telefono_fijo'] = $value ? preg_replace('/[^0-9]/', '', $value) : null;
    }

    public function setDireccionAttribute($value)
    {
        $this->attributes['direccion'] = mb_strtoupper(trim($value));
    }

    public function setRazonSocialAttribute($value)
    {
        $this->attributes['razon_social'] = $value ? mb_strtoupper(trim($value)) : null;
    }

    public function setRepresentanteLegalAttribute($value)
    {
        $this->attributes['representante_legal'] = $value ? mb_strtoupper(trim($value)) : null;
    }

    public function setCedulaRepresentanteAttribute($value)
    {
        $this->attributes['cedula_representante'] = $value ? preg_replace('/[^0-9]/', '', $value) : null;
    }

    public function setDireccionRepresentanteAttribute($value)
    {
        $this->attributes['direccion_representante'] = $value ? mb_strtoupper(trim($value)) : null;
    }
}
