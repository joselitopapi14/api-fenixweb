@extends('boletas-empeno.validacion.layout')

@section('title', 'Boleta No Encontrada')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-6">
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="icon-status status-danger mb-4">
                    <i class="fas fa-times-circle"></i>
                </div>

                <h2 class="h3 mb-3 text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Boleta No Encontrada
                </h2>

                <p class="lead text-muted mb-4">
                    No se pudo encontrar una boleta de empeño válida con el código escaneado.
                </p>
                </p>

                <div class="alert alert-light border" role="alert">
                    <strong>Código consultado:</strong><br>
                    <code class="text-muted">{{ $token }}</code>
                </div>

                <div class="mt-4">
                    <h5 class="h6 mb-3">Posibles causas:</h5>
                    <ul class="list-unstyled text-start">
                        <li class="mb-2">
                            <i class="fas fa-info-circle text-info me-2"></i>
                            El código QR puede estar dañado o corrupto
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-info-circle text-info me-2"></i>
                            La boleta puede haber sido anulada
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-info-circle text-info me-2"></i>
                            El enlace puede ser inválido o haber expirado
                        </li>
                    </ul>
                </div>

                <div class="mt-4">
                    <p class="text-muted small">
                        Si considera que esto es un error, póngase en contacto con la empresa emisora de la boleta.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
