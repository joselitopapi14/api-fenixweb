<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Impuesto extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'impuestos';

    protected $fillable = [
        'name',
        'code',
    ];

    // Activity Log Configuration
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Scopes
    public function scopeBuscar($query, $termino)
    {
        return $query->where(function($q) use ($termino) {
            $q->where('name', 'like', "%{$termino}%")
              ->orWhere('code', 'like', "%{$termino}%");
        });
    }

    // Relaciones
    public function impuestoPorcentajes(): HasMany
    {
        return $this->hasMany(ImpuestoPorcentaje::class);
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_impuesto')
                    ->withTimestamps();
    }
}
