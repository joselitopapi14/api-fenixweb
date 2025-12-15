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
        Schema::create('tipo_productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });


        DB::table('tipo_productos')->insert([
            ['nombre' => 'Oro', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Varios', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Vehiculos', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Electrodomesticos', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Tecnologia', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Productos de Belleza', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Otros', 'created_at' => now(), 'updated_at' => now()]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_productos');
    }
};
