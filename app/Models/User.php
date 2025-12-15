<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\LogsActivity;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Atributos específicos para Activity Log
     */
    protected function getLoggedAttributes(): array
    {
        return ['name', 'email']; // No registrar password por seguridad
    }

    /**
     * Identificador personalizado para logs
     */
    protected function getActivityIdentifier(): string
    {
        return "{$this->name} ({$this->email})";
    }

    // Relaciones con empresas
    public function empresas()
    {
        return $this->belongsToMany(Empresa::class, 'empresa_user')
                    ->withPivot('es_administrador', 'activo')
                    ->withTimestamps();
    }

    public function empresasQueAdministra()
    {
        return $this->belongsToMany(Empresa::class, 'empresa_user')
                    ->wherePivot('es_administrador', true)
                    ->wherePivot('activo', true)
                    ->withPivot('es_administrador', 'activo')
                    ->withTimestamps();
    }

    public function empresasActivas()
    {
        return $this->belongsToMany(Empresa::class, 'empresa_user')
                    ->wherePivot('activo', true)
                    ->withPivot('es_administrador', 'activo')
                    ->withTimestamps();
    }

    // Métodos auxiliares para empresas
    public function esAdministradorGlobal()
    {
        return $this->hasRole('role.admin');
    }

    public function esAdministradorDeEmpresa($empresaId = null)
    {
        if ($empresaId) {
            return $this->empresas()
                        ->wherePivot('empresa_id', $empresaId)
                        ->wherePivot('es_administrador', true)
                        ->wherePivot('activo', true)
                        ->exists();
        }

        return $this->empresasQueAdministra()->exists();
    }

    public function perteneceAEmpresa($empresaId)
    {
        return $this->empresas()
                    ->wherePivot('empresa_id', $empresaId)
                    ->wherePivot('activo', true)
                    ->exists();
    }

    public function getEmpresaPrincipal()
    {
        // Retorna la primera empresa donde es administrador, o la primera empresa asociada
        return $this->empresasQueAdministra()->first() ?? $this->empresasActivas()->first();
    }

    public function puedeAccederAEmpresa($empresaId)
    {
        return $this->esAdministradorGlobal() || $this->perteneceAEmpresa($empresaId);
    }
    // Alias para compatibilidad
    public function esAdministradorEmpresa()
    {
        return $this->esAdministradorDeEmpresa();
    }

    public function esEmpleadoEmpresa($empresaId = null)
    {
        if ($empresaId) {
            return $this->perteneceAEmpresa($empresaId);
        }
        return $this->empresasActivas()->exists();
    }
}
