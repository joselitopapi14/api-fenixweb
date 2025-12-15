<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaHasImpuesto extends Model
{
    protected $table = 'factura_has_impuestos';

    protected $fillable = [
        'factura_id',
        'impuesto_id',
        'valor'
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    public function impuesto(): BelongsTo
    {
        return $this->belongsTo(Impuesto::class);
    }
}
