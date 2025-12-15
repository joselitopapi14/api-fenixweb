<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoRetencion extends Model
{
    use SoftDeletes;

    protected $table = 'tipo_retenciones';

    protected $fillable = [
        'impuesto_id',
        'name',
        'code'
    ];

    public function impuesto(): BelongsTo
    {
        return $this->belongsTo(Impuesto::class);
    }
}
