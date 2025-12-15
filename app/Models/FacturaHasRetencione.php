<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaHasRetencione extends Model
{
    protected $table = 'factura_has_retenciones';

    protected $fillable = [
        'factura_id',
        'retencion_id',
        'concepto_retencion_id',
        'valor',
        'percentage'
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    public function retencion(): BelongsTo
    {
        return $this->belongsTo(TipoRetencion::class, 'retencion_id');
    }

    public function conceptoRetencione(): BelongsTo
    {
        return $this->belongsTo(ConceptoRetencione::class);
    }
}
