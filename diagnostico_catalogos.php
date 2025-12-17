<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Verificando datos en la BD ===\n\n";

try {
    echo "1. Tipos de Persona:\n";
    $tiposPersona = \App\Models\TipoPersona::all(['id', 'name', 'code']);
    foreach ($tiposPersona as $tipo) {
        echo "   ID: {$tipo->id} | Name: {$tipo->name} | Code: {$tipo->code}\n";
    }
    echo "\n";

    echo "2. Tipos de Responsabilidad:\n";
    $tiposResp = \App\Models\TipoResponsabilidad::all(['id', 'name', 'code']);
    foreach ($tiposResp as $tipo) {
        echo "   ID: {$tipo->id} | Name: {$tipo->name} | Code: {$tipo->code}\n";
    }
    echo "\n";

    echo "3. Tipos de Documento:\n";
    $tiposDoc = \App\Models\TipoDocumento::all(['id', 'name', 'code']);
    foreach ($tiposDoc as $tipo) {
        echo "   ID: {$tipo->id} | Name: {$tipo->name} | Code: {$tipo->code}\n";
    }
    echo "\n";

    echo "4. Departamentos:\n";
    $departamentos = \App\Models\Departamento::all(['id', 'name', 'code']);
    foreach ($departamentos as $dep) {
        echo "   ID: {$dep->id} | Name: {$dep->name} | Code: {$dep->code}\n";
    }
    echo "\n";

    echo "5. Municipios:\n";
    $municipios = \App\Models\Municipio::all(['id', 'name', 'code', 'departamento_id']);
    foreach ($municipios as $mun) {
        echo "   ID: {$mun->id} | Name: {$mun->name} | Code: {$mun->code} | Depto: {$mun->departamento_id}\n";
    }
    echo "\n";

    echo "6. Comunas:\n";
    $comunas = \App\Models\Comuna::all(['id', 'nombre', 'municipio_id']);
    foreach ($comunas as $com) {
        echo "   ID: {$com->id} | Nombre: {$com->nombre} | Municipio: {$com->municipio_id}\n";
    }
    echo "\n";

    echo "7. Barrios:\n";
    $barrios = \App\Models\Barrio::all(['id', 'nombre', 'comuna_id']);
    foreach ($barrios as $bar) {
        echo "   ID: {$bar->id} | Nombre: {$bar->nombre} | Comuna: {$bar->comuna_id}\n";
    }
    echo "\n";

    echo "✅ Todos los datos se obtuvieron correctamente\n\n";

    // Ahora verificar si existen los IDs del payload
    echo "=== Verificando IDs del payload ===\n\n";
    
    $payload = [
        'departamento_id' => 1,
        'municipio_id' => 1,
        'comuna_id' => 1,
        'barrio_id' => 1,
        'tipo_persona_id' => 2,
        'tipo_responsabilidad_id' => 1,
        'tipo_documento_id' => 1,
    ];

    $checks = [
        'departamento_id' => \App\Models\Departamento::find($payload['departamento_id']),
        'municipio_id' => \App\Models\Municipio::find($payload['municipio_id']),
        'comuna_id' => \App\Models\Comuna::find($payload['comuna_id']),
        'barrio_id' => \App\Models\Barrio::find($payload['barrio_id']),
        'tipo_persona_id' => \App\Models\TipoPersona::find($payload['tipo_persona_id']),
        'tipo_responsabilidad_id' => \App\Models\TipoResponsabilidad::find($payload['tipo_responsabilidad_id']),
        'tipo_documento_id' => \App\Models\TipoDocumento::find($payload['tipo_documento_id']),
    ];

    foreach ($checks as $field => $record) {
        if ($record) {
            echo "✅ {$field}: {$payload[$field]} - EXISTE\n";
        } else {
            echo "❌ {$field}: {$payload[$field]} - NO EXISTE\n";
        }
    }

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
