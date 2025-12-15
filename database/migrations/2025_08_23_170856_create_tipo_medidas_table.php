<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tipo_medidas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('abreviatura');
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->softDeletes();
            $table->timestamps();

            // Índices
            $table->index('activo');
            $table->unique(['nombre']);
            $table->unique(['abreviatura']);
        });

        // Insertar registros automáticamente después de crear la tabla
        DB::table('tipo_medidas')->insert([
            ['nombre' => 'Unidad', 'abreviatura' => 'und', 'descripcion' => 'Unidad de medida por defecto', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Kilogramo', 'abreviatura' => 'kg', 'descripcion' => 'Peso en kilogramos', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Gramo', 'abreviatura' => 'gr', 'descripcion' => 'Peso en gramos', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Tonelada', 'abreviatura' => 't', 'descripcion' => 'Peso en toneladas', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Mililitro', 'abreviatura' => 'ml', 'descripcion' => 'Volumen en mililitros', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Centímetro', 'abreviatura' => 'cm', 'descripcion' => 'Longitud en centímetros', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Metro', 'abreviatura' => 'mt', 'descripcion' => 'Longitud en metros', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Hora', 'abreviatura' => 'hr', 'descripcion' => 'Tiempo en horas', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Minuto', 'abreviatura' => 'min', 'descripcion' => 'Tiempo en minutos', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_medidas');
    }
};
