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
        // Ubicación geográfica (solo si no existen datos)
        if (\App\Models\Pais::count() === 0) {
            $this->call(PaisesSeeder::class);
        }
        if (\App\Models\Departamento::count() === 0) {
            $this->call(DepartamentosSeeder::class);
        }
        if (\App\Models\Municipio::count() === 0) {
            $this->call(MunicipiosSeeder::class);
        }
        if (\App\Models\Comuna::count() === 0) {
            $this->call(ComunaSeeder::class);
        }
        if (\App\Models\Barrio::count() === 0) {
            $this->call(BarrioSeeder::class);
        }
        
        // Roles y permisos
        $this->call([
            EmpresaRolesSeeder::class,
            UserSeeder::class,
        ]);
        
        // Catálogos de facturación
        $this->call([
            TipoFacturaSeeder::class,
            MedioPagoSeeder::class,
            TipoPagoSeeder::class,
            TipoRetencionSeeder::class,
            ConceptoRetencionSeeder::class,
            ImpuestoSeeder::class,
        ]);
        
        // Catálogos de productos
        $this->call([
            TipoProductoSeeder::class,
            TipoMedidaSeeder::class,
        ]);
        
        // Catálogos de clientes
        $this->call([
            TipoDocumentoSeeder::class,
            TipoPersonaSeeder::class,
            TipoResponsabilidadSeeder::class,
        ]);
        
        // Datos de prueba
        $this->call([
            EmpresaSeeder::class,
            ClienteSeeder::class,
            ProductoSeeder::class,
        ]);
    }
}
