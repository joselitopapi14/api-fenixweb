<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BolletaEmpeno extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'boletas_empeno';

    protected $fillable = [
        'numero_contrato',
        'cliente_id',
        'empresa_id',
        'sede_id',
        'user_id',
        'monto_prestamo',
        'estado',
        'fecha_vencimiento',
        'fecha_prestamo',
        'observaciones',
        'anulada',
        'razon_anulacion',
        'anulada_por',
        'fecha_anulacion',
         'tipo_interes_id',
        'tipo_movimiento_id',
        'foto_prenda',
        'qr_code',
        'ubicacion'
    ];

    protected $casts = [
        'monto_prestamo' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'fecha_prestamo' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'anulada' => 'boolean',
        'fecha_anulacion' => 'datetime'
    ];

    // Relaciones
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function productos()
    {
        return $this->hasMany(BolletaEmpenoProducto::class, 'boleta_empeno_id');
    }

    public function cuotas()
    {
        return $this->hasMany(Cuota::class, 'bolleta_empeno_id');
    }

    public function boletaDesempenos()
    {
        return $this->hasMany(BoletaDesempeno::class, 'bolleta_empeno_id');
    }

    public function anuladaPor()
    {
        return $this->belongsTo(User::class, 'anulada_por');
    }

    public function tipoInteres()
    {
        return $this->belongsTo(\App\Models\TipoInteres::class, 'tipo_interes_id');
    }

    public function tipoMovimiento()
    {
        return $this->belongsTo(\App\Models\TipoMovimiento::class, 'tipo_movimiento_id');
    }

    // Generar número de contrato
    public static function generarNumeroContrato()
    {
        $mesesAbrev = [
            1 => 'ENE', 2 => 'FEB', 3 => 'MAR', 4 => 'ABR',
            5 => 'MAY', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO',
            9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DIC'
        ];

        $mesActual = $mesesAbrev[Carbon::now()->month];
        $anoActual = Carbon::now()->year;
        $prefijoActual = $mesActual . $anoActual;

        // Buscar el último número de contrato con el mismo prefijo (mes/año)
        $ultimaBoleta = static::where('numero_contrato', 'like', $prefijoActual . '%')
            ->orderBy('numero_contrato', 'desc')
            ->first();

        if ($ultimaBoleta) {
            // Extraer el consecutivo del último número de contrato
            $ultimoConsecutivo = intval(substr($ultimaBoleta->numero_contrato, strlen($prefijoActual)));
            $nuevoConsecutivo = $ultimoConsecutivo + 1;
        } else {
            // Es el primer contrato del mes, empezar en 1
            $nuevoConsecutivo = 1;
        }

        $consecutivo = str_pad($nuevoConsecutivo, 4, '0', STR_PAD_LEFT);

        return $prefijoActual . $consecutivo;
    }

    // Scopes
    public function scopeDeEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }

    // Accessors
    public function getEsVencidaAttribute()
    {
        return $this->fecha_vencimiento && $this->fecha_vencimiento->isPast() && $this->estado === 'activa';
    }

    public function getFotoPrendaUrlAttribute()
    {
        if ($this->foto_prenda) {
            return asset('storage/' . $this->foto_prenda);
        }
        return null;
    }

    public function getUrlValidacionAttribute()
    {
        return route('boletas-empeno.validar', ['token' => $this->qr_code]);
    }

    public function generarQrCode()
    {
        if (!$this->qr_code) {
            $token = Str::random(32) . '_' . $this->id;
            $this->update(['qr_code' => $token]);
        }
        return $this->qr_code;
    }

    public function getQrCodeImageAttribute()
    {
        if (!$this->qr_code) {
            $this->generarQrCode();
        }

        // Generar QR como string base64 para usar en dompdf
        $url = $this->url_validacion;
        return \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(120)
            ->margin(1)
            ->generate($url);
    }

    protected function getActivityIdentifier(): string
    {
        $empresa = $this->empresa ? " - {$this->empresa->razon_social}" : "";
        return "Boleta Empeño: {$this->numero_contrato}{$empresa}";
    }

    // Boot para generar número de contrato automáticamente
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($boleta) {
            if (empty($boleta->numero_contrato)) {
                $boleta->numero_contrato = static::generarNumeroContrato();
            }
        });
    }
}
