<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImpuestoPorcentaje extends Model
{
    protected $table = 'impuesto_porcentajes';

    protected $fillable = [
        'impuesto_id',
        'percentage'
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    public function impuesto(): BelongsTo
    {
        return $this->belongsTo(Impuesto::class);
    }
}
