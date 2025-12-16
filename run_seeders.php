#!/usr/bin/env php
<?php

$seeders = [
    'TipoPagoSeeder',
    'TipoRetencionSeeder',
    'ConceptoRetencionSeeder',
    'ImpuestoSeeder',
    'TipoProductoSeeder',
    'TipoMedidaSeeder',
    'TipoDocumentoSeeder',
    'TipoPersonaSeeder',
    'TipoResponsabilidadSeeder',
    'EmpresaSeeder',
    'ClienteSeeder',
    'ProductoSeeder',
];

foreach ($seeders as $seeder) {
    echo "Ejecutando: {$seeder}...\n";
    $output = [];
    $returnCode = 0;
    exec("php artisan db:seed --class={$seeder} 2>&1", $output, $returnCode);
    
    if ($returnCode !== 0) {
        echo "❌ ERROR en {$seeder}:\n";
        echo implode("\n", $output) . "\n";
        break;
    } else {
        echo "✅ {$seeder} completado\n";
    }
}

echo "\n✅ Todos los seeders completados exitosamente!\n";
