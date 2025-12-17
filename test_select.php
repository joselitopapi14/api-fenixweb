<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "=== Probando consultas SELECT específicas ===\n\n";

try {
    echo "1. TipoPersona con select específico:\n";
    $result = \App\Models\TipoPersona::select(['id', 'name', 'code'])->orderBy('name')->get();
    echo "   Resultado: " . json_encode($result->toArray(), JSON_PRETTY_PRINT) . "\n\n";

    echo "2. TipoResponsabilidad con select específico:\n";
    $result = \App\Models\TipoResponsabilidad::select(['id', 'name', 'code'])->orderBy('name')->get();
    echo "   Resultado: " . json_encode($result->toArray(), JSON_PRETTY_PRINT) . "\n\n";

    echo "3. TipoDocumento con select específico:\n";
    $result = \App\Models\TipoDocumento::select(['id', 'name', 'code'])->orderBy('name')->get();
    echo "   Resultado: " . json_encode($result->toArray(), JSON_PRETTY_PRINT) . "\n\n";

    echo "✅ Todas las consultas funcionaron\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
}
