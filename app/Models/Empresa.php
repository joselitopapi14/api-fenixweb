<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Empresa extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'nit',
        'dv',
        'razon_social',
        'direccion',
        'departamento_id',
        'municipio_id',
        'comuna_id',
        'barrio_id',
        'telefono_fijo',
        'celular',
        'email',
        'pagina_web',
        'software_id',
        'software_pin',
        'certificate_path',
        'certificate_password',
        'logo',
        'representante_legal',
        'cedula_representante',
        'email_representante',
        'direccion_representante',
        'tipo_persona_id',
        'tipo_responsabilidad_id',
        'tipo_documento_id',
        'activa'
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    /**
     * Hide sensitive attributes when serializing.
     */
    protected $hidden = [
        'certificate_password',
    ];

    // Relaciones de ubicación
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

    // Relaciones con tipos
    public function tipoPersona()
    {
        return $this->belongsTo(TipoPersona::class);
    }

    public function tipoResponsabilidad()
    {
        return $this->belongsTo(TipoResponsabilidad::class);
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    // Relaciones con usuarios
    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'empresa_user')
                    ->withPivot('es_administrador', 'activo')
                    ->withTimestamps();
    }

    public function administradores()
    {
        return $this->belongsToMany(User::class, 'empresa_user')
                    ->wherePivot('es_administrador', true)
                    ->wherePivot('activo', true)
                    ->withPivot('es_administrador', 'activo')
                    ->withTimestamps();
    }

    public function empleados()
    {
        return $this->belongsToMany(User::class, 'empresa_user')
                    ->wherePivot('es_administrador', false)
                    ->wherePivot('activo', true)
                    ->withPivot('es_administrador', 'activo')
                    ->withTimestamps();
    }

    // Relaciones con redes sociales
    public function redesSociales()
    {
        return $this->belongsToMany(RedSocial::class, 'empresa_red_social')
                    ->withPivot('usuario_red_social')
                    ->withTimestamps();
    }

    // Relaciones con tipos de productos y oro
    public function tipoProductos()
    {
        return $this->hasMany(TipoProducto::class);
    }

    public function tipoOros()
    {
        return $this->hasMany(TipoOro::class);
    }

    // Relaciones con tipos de interés y movimientos
    public function tiposInteres()
    {
        return $this->hasMany(TipoInteres::class);
    }

    public function tiposMovimientos()
    {
        return $this->hasMany(TipoMovimiento::class);
    }

    // Relación con sedes
    public function sedes()
    {
        return $this->hasMany(Sede::class);
    }

    public function sedesActivas()
    {
        return $this->hasMany(Sede::class)->where('activa', true);
    }

    public function sedePrincipal()
    {
        return $this->hasOne(Sede::class)->where('es_principal', true);
    }

    // Relación con clientes
    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    // Métodos auxiliares
    public function getNitCompletoAttribute()
    {
        return $this->nit . '-' . $this->dv;
    }

    public function getDireccionCompletaAttribute()
    {
        $direccionParts = array_filter([
            $this->direccion,
            $this->barrio?->nombre,
            $this->comuna?->nombre,
            $this->municipio?->name,
            $this->departamento?->name
        ]);

        return implode(', ', $direccionParts);
    }

    // Verificar si un usuario es administrador de esta empresa
    public function esAdministrador(User $usuario)
    {
        return $this->usuarios()
                    ->wherePivot('user_id', $usuario->id)
                    ->wherePivot('es_administrador', true)
                    ->wherePivot('activo', true)
                    ->exists();
    }

    // Verificar si un usuario pertenece a esta empresa
    public function tieneUsuario(User $usuario)
    {
        return $this->usuarios()
                    ->wherePivot('user_id', $usuario->id)
                    ->wherePivot('activo', true)
                    ->exists();
    }

    /**
     * Accessor para obtener el nombre de la empresa (alias de razon_social)
     */
    public function getNombreAttribute(): string
    {
        return $this->razon_social;
    }

    /**
     * Identificador personalizado para logs
     */
    protected function getActivityIdentifier(): string
    {
        return "Empresa: {$this->razon_social} (NIT: {$this->nit_completo})";
    }
}
