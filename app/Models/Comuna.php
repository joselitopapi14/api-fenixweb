<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Comuna extends Model
{
    use LogsActivity;
    protected $fillable = [
        'nombre',
        'municipio_id',
    ];

    /**
     * Relación con barrios
     */
    public function barrios()
    {
        return $this->hasMany(Barrio::class);
    }

    /**
     * Relación con municipio
     */
    public function municipio()
    {
        return $this->belongsTo(Municipio::class);
    }

    /**
     * Identificador personalizado para logs
     */
    protected function getActivityIdentifier(): string
    {
        return "Comuna: {$this->nombre}";
    }
}
