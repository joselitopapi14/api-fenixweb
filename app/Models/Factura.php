<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Factura extends Model
{
    protected $fillable = [
        'numero_factura',
        'cliente_id',
        'user_id',
        'tipo_movimiento_id',
        'tipo_factura_id',
        'empresa_id',
        'medio_pago_id',
        'tipo_pago_id',
        'tipo_pagos_id',
        'total',
        'valor_impuestos',
        'issue_date',
        'due_date',
        'cufe',
        'qr_code',
        'valor_recibido',
        'cambio',
        'subtotal',
        'estado',
        'xml_url',
        'observaciones'
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'valor_impuestos' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tipoMovimiento(): BelongsTo
    {
        return $this->belongsTo(ResolucionFacturacion::class, 'tipo_movimiento_id');
    }

    public function tipoFactura(): BelongsTo
    {
        return $this->belongsTo(TipoFactura::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function medioPago(): BelongsTo
    {
        return $this->belongsTo(MedioPago::class);
    }

    public function tipoPago(): BelongsTo
    {
        return $this->belongsTo(TipoPago::class);
    }

    public function facturaHasImpuestos(): HasMany
    {
        return $this->hasMany(FacturaHasImpuesto::class);
    }

    public function facturaHasProducts(): HasMany
    {
        return $this->hasMany(FacturaHasProduct::class);
    }

    public function facturaHasRetenciones(): HasMany
    {
        return $this->hasMany(FacturaHasRetencione::class);
    }

    // Método para generar la URL del QR Code según el ambiente de la empresa
    public function getQrCodeUrlAttribute()
    {
        if (!$this->cufe || !$this->empresa) {
            return null;
        }

        $baseUrl = env('DIAN_ENV') === 'production'
            ? 'https://catalogo-vpfe.dian.gov.co/document/searchqr?documentkey='
            : 'https://catalogo-vpfe-hab.dian.gov.co/document/searchqr?documentkey=';

        return trim($baseUrl . $this->cufe);
    }

    // Método para generar QR code como imagen base64
    public function getQrCodeImageAttribute()
    {
        $url = $this->qr_code_url;

        if (!$url) {
            return null;
        }

        // Generar QR como datos binarios
        $qrBinary = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(120)
            ->margin(1)
            ->generate($url);

        // Convertir a base64 para usar en HTML/PDF
        return base64_encode($qrBinary);
    }
}
