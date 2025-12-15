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
        Schema::create('tipo_movimientos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->boolean('es_suma')->default(true); // true = suma, false = resta
            $table->boolean('activo')->default(true);
            $table->text('descripcion')->nullable();

            // Relación con empresa
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['activo']);
            $table->index(['empresa_id']);
            $table->index(['es_suma']);
            $table->unique(['nombre', 'empresa_id']); // Nombre único por empresa
        });

        DB::table('tipo_movimientos')->insert([
            ['nombre' => 'Compra/Venta de Oro', 'es_suma' => true, 'activo' => true, 'descripcion' => null, 'empresa_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Venta de Oro', 'es_suma' => false, 'activo' => true, 'descripcion' => null, 'empresa_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_movimientos');
    }
};
