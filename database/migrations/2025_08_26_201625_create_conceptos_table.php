<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conceptos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('cascade');
            $table->timestamps();

            // Índices para mejorar rendimiento
            $table->index(['empresa_id', 'activo']);
            $table->index('nombre');
        });

        DB::table('conceptos')->insert([
            ['nombre' => 'Nómina', 'descripcion' => 'Sin descripción', 'activo' => true, 'empresa_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Arriendo', 'descripcion' => 'Sin descripción', 'activo' => true, 'empresa_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Cafeteria', 'descripcion' => 'Sin descripción', 'activo' => true, 'empresa_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Uniformes', 'descripcion' => 'Sin descripción', 'activo' => true, 'empresa_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Servicios Públicos', 'descripcion' => 'Sin descripción', 'activo' => true, 'empresa_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Administración', 'descripcion' => 'Sin descripción', 'activo' => true, 'empresa_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Créditos', 'descripcion' => 'Sin descripción', 'activo' => true, 'empresa_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Representación', 'descripcion' => 'Sin descripción', 'activo' => true, 'empresa_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conceptos');
    }
};
