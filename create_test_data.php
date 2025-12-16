<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Creando datos de prueba...\n\n";

// 1. Crear Empresa
echo "1. Creando empresa...\n";
$empresa = App\Models\Empresa::create([
    'nit' => '900123456',
    'dv' => '7',
    'razon_social' => 'EMPRESA DE PRUEBA S.A.S',
    'direccion' => 'CALLE 123 # 45-67',
    'email' => 'contacto@empresaprueba.com',
    'celular' => '3001234567',
    'tipo_persona_id' => 2,
    'tipo_responsabilidad_id' => 1,
    'tipo_documento_id' => 6,
    'activa' => true
]);
echo "✅ Empresa creada: ID {$empresa->id}\n\n";

// 2. Crear Clientes
echo "2. Creando clientes...\n";
$cliente1 = App\Models\Cliente::create([
    'empresa_id' => $empresa->id,
    'tipo_documento_id' => 3,
    'tipo_persona_id' => 1,
    'tipo_responsabilidad_id' => 5,
    'nombres' => 'JUAN',
    'apellidos' => 'PÉREZ GÓMEZ',
    'cedula_nit' => '1234567890',
    'email' => 'juan.perez@example.com',
    'celular' => '3001234567',
    'direccion' => 'CALLE 10 # 20-30'
]);
echo "✅ Cliente 1 creado: {$cliente1->nombre_completo}\n";

$cliente2 = App\Models\Cliente::create([
    'empresa_id' => $empresa->id,
    'tipo_documento_id' => 6,
    'tipo_persona_id' => 2,
    'tipo_responsabilidad_id' => 1,
    'cedula_nit' => '900987654',
    'dv' => '3',
    'razon_social' => 'CLIENTE CORPORATIVO S.A.S',
    'email' => 'contacto@clientecorp.com',
    'celular' => '3009876543',
    'telefono_fijo' => '6012345678',
    'direccion' => 'CARRERA 50 # 100-200',
    'representante_legal' => 'MARÍA RODRÍGUEZ',
    'cedula_representante' => '9876543210'
]);
echo "✅ Cliente 2 creado: {$cliente2->razon_social}\n\n";

// 3. Crear Productos
echo "3. Creando productos...\n";
$producto1 = App\Models\Producto::create([
    'empresa_id' => $empresa->id,
    'tipo_producto_id' => 1,
    'tipo_medida_id' => 1,
    'nombre' => 'PRODUCTO DE PRUEBA',
    'descripcion' => 'Producto de prueba para facturación',
    'precio_venta' => 50000,
    'precio_compra' => 30000,
    'stock' => 100
]);
$producto1->impuestos()->attach(1); // IVA
echo "✅ Producto 1 creado: {$producto1->nombre}\n";

$producto2 = App\Models\Producto::create([
    'empresa_id' => $empresa->id,
    'tipo_producto_id' => 2,
    'tipo_medida_id' => 1,
    'nombre' => 'SERVICIO DE CONSULTORÍA',
    'descripcion' => 'Servicio de consultoría profesional',
    'precio_venta' => 150000,
    'precio_compra' => 0,
    'stock' => null
]);
$producto2->impuestos()->attach(1); // IVA
echo "✅ Producto 2 creado: {$producto2->nombre}\n\n";

// 4. Crear Resolución de Facturación
echo "4. Creando resolución de facturación...\n";
$resolucion = App\Models\ResolucionFacturacion::create([
    'empresa_id' => $empresa->id,
    'prefijo' => 'FV',
    'resolucion' => '18760000001',
    'consecutivo_inicial' => 1,
    'consecutivo_actual' => 1,
    'consecutivo_final' => 5000,
    'fecha_inicio' => '2024-01-01',
    'fecha_fin' => '2025-12-31',
    'clave_tecnica' => 'clave_ejemplo',
    'envia_dian' => true
]);
echo "✅ Resolución creada: {$resolucion->prefijo} - {$resolucion->resolucion}\n\n";

echo "========================================\n";
echo "✅ DATOS DE PRUEBA CREADOS EXITOSAMENTE\n";
echo "========================================\n\n";

echo "Resumen:\n";
echo "- Empresa ID: {$empresa->id}\n";
echo "- Cliente 1 ID: {$cliente1->id}\n";
echo "- Cliente 2 ID: {$cliente2->id}\n";
echo "- Producto 1 ID: {$producto1->id}\n";
echo "- Producto 2 ID: {$producto2->id}\n";
echo "- Resolución ID: {$resolucion->id}\n\n";

echo "Ahora puedes crear facturas con estos datos!\n";
