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
        Schema::create('boleta_empeno_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boleta_empeno_id')->constrained('boletas_empeno')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained()->onDelete('cascade');
            $table->decimal('cantidad', 15, 2);
            $table->text('descripcion_adicional')->nullable();
            $table->timestamps();
            $table->index(['boleta_empeno_id', 'producto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boleta_empeno_productos');
    }
};
