<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$producto = App\Models\Producto::with('impuestos.impuestoPorcentajes')->find(1);

if ($producto) {
    echo "Producto: {$producto->nombre}\n";
    echo "Precio: {$producto->precio_venta}\n";
    echo "Empresa ID: {$producto->empresa_id}\n";
    echo "Impuestos count: " . $producto->impuestos->count() . "\n";
    
    foreach ($producto->impuestos as $impuesto) {
        echo "  - Impuesto: {$impuesto->name}\n";
        foreach ($impuesto->impuestoPorcentajes as $porcentaje) {
            echo "    Porcentaje: {$porcentaje->percentage}%\n";
        }
    }
} else {
    echo "Producto no encontrado\n";
}

// Verificar cliente
$cliente = App\Models\Cliente::find(1);
if ($cliente) {
    echo "\nCliente: {$cliente->nombre_completo}\n";
    echo "Empresa ID: {$cliente->empresa_id}\n";
} else {
    echo "\nCliente no encontrado\n";
}

// Verificar empresa
$empresa = App\Models\Empresa::find(1);
if ($empresa) {
    echo "\nEmpresa: {$empresa->razon_social}\n";
} else {
    echo "\nEmpresa no encontrada\n";
}
