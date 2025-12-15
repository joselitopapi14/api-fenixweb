<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class TipoResponsabilidad extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'tipo_responsabilidades';

    protected $fillable = [
        'name',
        'code',
    ];

    /**
     * Relación con empresas
     */
    public function empresas()
    {
        return $this->hasMany(Empresa::class);
    }

    /**
     * Relación con clientes
     */
    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }
}
