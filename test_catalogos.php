<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "=== Probando TipoPersona ===\n";
    $tiposPersona = \App\Models\TipoPersona::orderBy('name')->get(['id', 'name', 'code']);
    echo "Resultado: " . json_encode($tiposPersona, JSON_PRETTY_PRINT) . "\n\n";

    echo "=== Probando TipoResponsabilidad ===\n";
    $tiposResp = \App\Models\TipoResponsabilidad::orderBy('name')->get(['id', 'name', 'code']);
    echo "Resultado: " . json_encode($tiposResp, JSON_PRETTY_PRINT) . "\n\n";

    echo "=== Probando TipoDocumento ===\n";
    $tiposDoc = \App\Models\TipoDocumento::orderBy('name')->get(['id', 'name', 'code']);
    echo "Resultado: " . json_encode($tiposDoc, JSON_PRETTY_PRINT) . "\n\n";

    echo "=== Probando Departamento ===\n";
    $departamentos = \App\Models\Departamento::orderBy('name')->get(['id', 'name', 'code']);
    echo "Resultado: " . json_encode($departamentos, JSON_PRETTY_PRINT) . "\n\n";

    echo "✅ Todos los modelos funcionan correctamente\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
