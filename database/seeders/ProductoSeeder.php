<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear productos
        $productos = [
            [
                'empresa_id' => 1,
                'tipo_producto_id' => 1, // Producto
                'tipo_medida_id' => 1, // Unidad
                'nombre' => 'PRODUCTO DE PRUEBA',
                'descripcion' => 'Producto de prueba para facturación',
                'precio_venta' => 50000,
                'precio_compra' => 30000,
                'stock' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => 1,
                'tipo_producto_id' => 2, // Servicio
                'tipo_medida_id' => 1, // Unidad
                'nombre' => 'SERVICIO DE CONSULTORÍA',
                'descripcion' => 'Servicio de consultoría profesional',
                'precio_venta' => 150000,
                'precio_compra' => 0,
                'stock' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('productos')->insert($productos);

        // Asociar impuestos a los productos
        $productoImpuestos = [
            // Producto 1 - IVA 19%
            [
                'producto_id' => 1,
                'impuesto_id' => 1, // IVA
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Producto 2 - IVA 19%
            [
                'producto_id' => 2,
                'impuesto_id' => 1, // IVA
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('impuesto_producto')->insert($productoImpuestos);
    }
}
