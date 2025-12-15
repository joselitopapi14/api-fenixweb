@extends('boletas-empeno.validacion.layout')

@section('title', 'Validación: ' . $boleta->numero_contrato)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">
        <!-- Membrete de la Empresa -->
        <div class="card mb-4">
            <div class="card-body text-center py-3">
                <h2 class="h3 mb-1 text-success">{{ $boleta->empresa->razon_social }}</h2>
                <p class="mb-0 text-muted">
                    <strong>NIT:</strong> {{ $boleta->empresa->nit }}{{ $boleta->empresa->dv ? '-' . $boleta->empresa->dv : '' }}
                </p>
            </div>
        </div>

        <!-- Estado de la Boleta -->
        <div class="card mb-4">
            <div class="card-header text-center">
                <h2 class="h4 mb-0">
                    <i class="fas fa-certificate me-2"></i>
                    Boleta Verificada Exitosamente
                </h2>
            </div>
            <div class="card-body text-center py-4">
                <div class="icon-status status-{{ $estadoInfo['clase_css'] }} mb-3">
                    @switch($estadoInfo['clase_css'])
                        @case('success')
                            <i class="fas fa-check-circle"></i>
                            @break
                        @case('warning')
                            <i class="fas fa-clock"></i>
                            @break
                        @case('danger')
                            <i class="fas fa-times-circle"></i>
                            @break
                        @case('info')
                            <i class="fas fa-info-circle"></i>
                            @break
                        @default
                            <i class="fas fa-file-contract"></i>
                    @endswitch
                </div>

                <span class="status-badge status-{{ $estadoInfo['clase_css'] }}">
                    {{ $estadoInfo['estado'] }}
                </span>

                <p class="text-muted mt-3 mb-0">{{ $estadoInfo['descripcion'] }}</p>

                @if(isset($estadoInfo['razon_anulacion']) && $estadoInfo['razon_anulacion'])
                    <div class="alert alert-danger mt-3">
                        <strong>Razón de anulación:</strong> {{ $estadoInfo['razon_anulacion'] }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Información de la Boleta -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="h5 mb-0">
                    <i class="fas fa-file-contract me-2"></i>
                    Información de la Boleta
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row d-flex justify-content-between">
                            <span class="info-label">Número de Contrato:</span>
                            <span class="fw-bold">{{ $boleta->numero_contrato }}</span>
                        </div>
                        <div class="info-row d-flex justify-content-between">
                            <span class="info-label">Fecha de Préstamo:</span>
                            <span>{{ $boleta->fecha_prestamo->format('d/m/Y') }}</span>
                        </div>
                        <div class="info-row d-flex justify-content-between">
                            <span class="info-label">Fecha de Vencimiento:</span>
                            <span>{{ $boleta->fecha_vencimiento->format('d/m/Y') }}</span>
                        </div>
                        <div class="info-row d-flex justify-content-between">
                            <span class="info-label">Plazo Total:</span>
                            <span>{{ $plazoInfo['plazo_total'] }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row d-flex justify-content-between">
                            <span class="info-label">Monto del Préstamo:</span>
                            <span class="fw-bold text-success">${{ number_format($boleta->monto_prestamo, 0, ',', '.') }}</span>
                        </div>
                        @if($plazoInfo['dias_restantes'] >= 0)
                            <div class="info-row d-flex justify-content-between">
                                <span class="info-label">Días Restantes:</span>
                                <span class="badge bg-{{ $plazoInfo['por_vencer'] ? 'warning' : 'success' }}">
                                    {{ intval($plazoInfo['dias_restantes']) }} días
                                </span>
                            </div>
                        @else
                            <div class="info-row d-flex justify-content-between">
                                <span class="info-label">Días Vencidos:</span>
                                <span class="badge bg-danger">
                                    {{ intval(abs($plazoInfo['dias_restantes'])) }} días
                                </span>
                            </div>
                        @endif
                        @if($boleta->tipoInteres)
                            <div class="info-row d-flex justify-content-between">
                                <span class="info-label">Tipo de Interés:</span>
                                <span>{{ $boleta->tipoInteres->nombre }} ({{ $boleta->tipoInteres->porcentaje }}%)</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Información del Cliente -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="h5 mb-0">
                    <i class="fas fa-user me-2"></i>
                    Información del Cliente
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row d-flex justify-content-between">
                            <span class="info-label">Nombre:</span>
                            <span>{{ $boleta->cliente->nombre_completo ?? $boleta->cliente->razon_social }}</span>
                        </div>
                        <div class="info-row d-flex justify-content-between">
                            <span class="info-label">Documento:</span>
                            <span>
                                {{ $boleta->cliente->tipoDocumento->abreviacion ?? '' }}
                                {{ $boleta->cliente->cedula_nit }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        @if($boleta->cliente->telefono)
                            <div class="info-row d-flex justify-content-between">
                                <span class="info-label">Teléfono:</span>
                                <span>{{ $boleta->cliente->telefono }}</span>
                            </div>
                        @endif
                        @if($boleta->cliente->direccion)
                            <div class="info-row d-flex justify-content-between">
                                <span class="info-label">Dirección:</span>
                                <span>{{ $boleta->cliente->direccion }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de la Empresa -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="h5 mb-0">
                    <i class="fas fa-building me-2"></i>
                    Información de la Empresa
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row d-flex justify-content-between">
                            <span class="info-label">Razón Social:</span>
                            <span>{{ $boleta->empresa->razon_social }}</span>
                        </div>
                        <div class="info-row d-flex justify-content-between">
                            <span class="info-label">NIT:</span>
                            <span>{{ $boleta->empresa->nit }}{{ $boleta->empresa->dv ? '-' . $boleta->empresa->dv : '' }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        @if($boleta->empresa->telefono_fijo)
                            <div class="info-row d-flex justify-content-between">
                                <span class="info-label">Teléfono:</span>
                                <span>{{ $boleta->empresa->telefono_fijo }}</span>
                            </div>
                        @endif
                        @if($boleta->empresa->direccion)
                            <div class="info-row d-flex justify-content-between">
                                <span class="info-label">Dirección:</span>
                                <span>{{ $boleta->empresa->direccion }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Historial de Cuotas -->
        @if($boleta->cuotas && $boleta->cuotas->count() > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="h5 mb-0">
                        <i class="fas fa-receipt me-2"></i>
                        Historial de Cuotas Pagadas
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha Abono</th>
                                    <th>Monto Pagado</th>
                                    <th>Estado</th>
                                    <th>Atendido por</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($boleta->cuotas->sortByDesc('fecha_abono') as $cuota)
                                <tr>
                                    <td>
                                        <strong>{{ $cuota->fecha_abono->format('d/m/Y') }}</strong>
                                        <small class="text-muted d-block">{{ $cuota->created_at->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">
                                            ${{ number_format($cuota->monto_pagado, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $cuota->estado === 'pagada' ? 'bg-success' : 'bg-warning' }}">
                                            {{ ucfirst($cuota->estado) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $cuota->usuario->name ?? 'No registrado' }}
                                        </small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Resumen de cuotas -->
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="text-center">
                                <span class="text-muted small">Total Cuotas</span>
                                <div class="fw-bold h5 mb-0">{{ $boleta->cuotas->count() }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <span class="text-muted small">Total Abonado</span>
                                <div class="fw-bold h5 mb-0 text-success">
                                    ${{ number_format($boleta->cuotas->sum('monto_pagado'), 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <span class="text-muted small">Última Cuota</span>
                                <div class="fw-bold h5 mb-0">
                                    {{ $boleta->cuotas->sortByDesc('fecha_abono')->first()?->fecha_abono->format('d/m/Y') ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="h5 mb-0">
                        <i class="fas fa-receipt me-2"></i>
                        Historial de Cuotas
                    </h3>
                </div>
                <div class="card-body text-center">
                    <div class="text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p class="mb-0">Aún no se han registrado cuotas para esta boleta de empeño.</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Productos Empeñados -->
        @if($boleta->productos && $boleta->productos->count() > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="h5 mb-0">
                        <i class="fas fa-gem me-2"></i>
                        Productos Empeñados
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive productos-table">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Descripción Adicional</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($boleta->productos as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->producto->nombre ?? 'Producto' }}</strong>
                                            @if($item->producto->tipoProducto)
                                                <br><small class="text-muted">{{ $item->producto->tipoProducto->nombre }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $item->cantidad }}
                                            {{ $item->producto->tipoMedida->abreviatura ?? '' }}
                                        </td>
                                        <td>
                                            @if($item->descripcion_adicional)
                                                {{ $item->descripcion_adicional }}
                                            @else
                                                <small class="text-muted">Sin descripción adicional</small>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Observaciones -->
        @if($boleta->observaciones)
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="h5 mb-0">
                        <i class="fas fa-sticky-note me-2"></i>
                        Observaciones
                    </h3>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $boleta->observaciones }}</p>
                </div>
            </div>
        @endif

        <!-- Acciones -->
        <div class="card">
            <div class="card-body text-center">
                                <a href="{{ route('boletas-empeno.validar.pdf', $token) }}"
                   class="btn btn-primary btn-lg"
                   target="_blank">
                    <i class="fas fa-file-pdf me-2"></i>
                    Descargar PDF
                </a>
            </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus en el botón principal para mejor UX
    const mainButton = document.querySelector('.btn-primary');
    if (mainButton) {
        mainButton.focus();
    }
});
</script>
@endsection
