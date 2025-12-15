<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConceptoRetencione extends Model
{
    protected $table = 'concepto_retenciones';

    protected $fillable = [
        'tipo_retencion_id',
        'name',
        'percentage'
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    public function tipoRetencion(): BelongsTo
    {
        return $this->belongsTo(TipoRetencion::class);
    }
}
