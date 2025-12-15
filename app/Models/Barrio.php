<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Barrio extends Model
{
    use LogsActivity;
    protected $fillable = [
        'nombre',
        'comuna_id',
    ];

    /**
     * Relación con comuna
     */
    public function comuna()
    {
        return $this->belongsTo(Comuna::class);
    }

    /**
     * Relación con líderes
     */
    public function lideres()
    {
        return $this->hasMany(Lider::class);
    }

    /**
     * Identificador personalizado para logs
     */
    protected function getActivityIdentifier(): string
    {
        return "Barrio: {$this->nombre}";
    }
}
