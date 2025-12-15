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
        Schema::create('tipo_oros', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->decimal('valor_de_mercado', 10, 2);
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        DB::table('tipo_oros')->insert([
            ['nombre' => 'Oro 24K', 'valor_de_mercado' => 437024.00, 'observacion' => 'Oro puro de 24 quilates', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Oro 18K', 'valor_de_mercado' => 321298.00, 'observacion' => 'Oro de 18 quilates', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_oros');
    }
};
