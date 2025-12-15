<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoFactura extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code'
    ];

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class);
    }
}
