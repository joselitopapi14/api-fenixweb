<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            // Ubicación geográfica
            PaisesSeeder::class,
            DepartamentosSeeder::class,
            MunicipiosSeeder::class,
            ComunaSeeder::class,
            BarrioSeeder::class,
            
            // Roles y permisos
            EmpresaRolesSeeder::class,
            UserSeeder::class,
            
            // Catálogos de facturación
            TipoFacturaSeeder::class,
            MedioPagoSeeder::class,
            TipoPagoSeeder::class,
            TipoRetencionSeeder::class,
            ConceptoRetencionSeeder::class,
            ImpuestoSeeder::class,
            
            // Catálogos de productos
            TipoProductoSeeder::class,
            TipoMedidaSeeder::class,
            
            // Catálogos de clientes
            TipoDocumentoSeeder::class,
            TipoPersonaSeeder::class,
            TipoResponsabilidadSeeder::class,
            
            // Datos de prueba
            EmpresaSeeder::class,
            ClienteSeeder::class,
            ProductoSeeder::class,
        ]);
    }
}
