<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class DocumentoEquivalente extends Model
{
    use LogsActivity;

    protected $fillable = [
        'fecha_documento',
        'empresa_id',
        'resolucion_id',
        'concepto_id',
        'cliente_id',
        'tipo_pago_id',
        'medio_pago_id',
        'monto',
        'descripcion',
        'estado',
        'xml_url',
        'cuds',
        'qr_code'
    ];

    protected $casts = [
        'fecha_documento' => 'date',
        'monto' => 'decimal:2',
    ];

    // Relación con resolución de facturación
    public function resolucion()
    {
        return $this->belongsTo(ResolucionFacturacion::class, 'resolucion_id');
    }

    // Relación con empresa
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    // Relación con concepto
    public function concepto()
    {
        return $this->belongsTo(Concepto::class);
    }

    // Relación con cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    // Relación con tipo de pago
    public function tipoPago()
    {
        return $this->belongsTo(TipoPago::class);
    }

    // Relación con medio de pago
    public function medioPago()
    {
        return $this->belongsTo(MedioPago::class);
    }

    // Scope para filtrar por empresa
    public function scopeDeEmpresa($query, $empresaId)
    {
        if ($empresaId) {
            return $query->where('empresa_id', $empresaId);
        }
        return $query;
    }

    // Scope para documentos activos
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    // Scope para documentos inactivos
    public function scopeInactivos($query)
    {
        return $query->where('estado', 'inactivo');
    }

    // Método para anular documento
    public function anular()
    {
        $this->update(['estado' => 'inactivo']);
    }

    // Método para activar documento
    public function activar()
    {
        $this->update(['estado' => 'activo']);
    }

    // Accessor para número de documento
    public function getNumeroDocumentoAttribute()
    {
        return str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    // Método para generar la URL del QR Code según el ambiente de la empresa
    public function getQrCodeUrlAttribute()
    {
        if (!$this->cuds || !$this->empresa) {
            return null;
        }

        $baseUrl = env('DIAN_ENV') === 'production'
            ? 'https://catalogo-vpfe.dian.gov.co/document/searchqr?documentkey='
            : 'https://catalogo-vpfe-hab.dian.gov.co/document/searchqr?documentkey=';

        return trim($baseUrl . $this->cuds);
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

    /**
     * Identificador personalizado para logs
     */
    protected function getActivityIdentifier(): string
    {
        $empresa = $this->empresa ? " - {$this->empresa->razon_social}" : "";
        return "Documento Equivalente: {$this->id} - {$this->descripcion}{$empresa}";
    }
}
