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
        Schema::create('tipo_interes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->decimal('porcentaje', 5, 2); // Hasta 999.99%
            $table->boolean('activo')->default(true);
            $table->text('descripcion')->nullable();

            // Relación con empresa (opcional)
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['activo']);
            $table->index(['empresa_id']);
            $table->unique(['nombre', 'empresa_id']); // Nombre único por empresa (null permitido)
        });

        DB::table('tipo_interes')->insert([
            ['nombre' => 'Lista de Precio 1', 'porcentaje' => 10.00, 'activo' => true, 'descripcion' => 'Sin descripción', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Lista de Precio 2', 'porcentaje' => 8.00, 'activo' => true, 'descripcion' => 'Sin descripción', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Lista de Precio 3', 'porcentaje' => 7.00, 'activo' => true, 'descripcion' => 'Sin descripción', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Lista de Precio 4', 'porcentaje' => 5.00, 'activo' => true, 'descripcion' => 'Sin descripción', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_interes');
    }
};
