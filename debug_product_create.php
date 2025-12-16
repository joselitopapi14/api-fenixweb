<?php

use App\Http\Controllers\Api\ProductoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Login as admin to avoid issues if any auth logic is hit (though we bypass middleware)
    $user = User::first(); 
    if ($user) auth()->login($user);

    $data = [
      "nombre" => "Anillo de Oro 18K",
      "descripcion" => "Anillo de oro de 18 quilates con diseño clásico y elegante",
      "tipo_producto_id" => 1,
      "tipo_oro_id" => 2,
      "empresa_id" => null,
      "codigo_barras" => "78917890" . rand(100, 999), // Unique
      "precio_venta" => 350.50,
      "precio_compra" => 280.00,
      "tipo_medida_id" => 1,
      "impuestos" => [1, 4]
    ];

    // Ensure dependent IDs exist
    if (!\App\Models\TipoOro::find(2)) {
        echo "TipoOro 2 not found, using first available\n";
        $data['tipo_oro_id'] = \App\Models\TipoOro::first()->id ?? null;
    }
    
    $request = Request::create('/api/productos', 'POST', $data);
    
    $controller = new ProductoController();
    $response = $controller->store($request);
    
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";

} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
