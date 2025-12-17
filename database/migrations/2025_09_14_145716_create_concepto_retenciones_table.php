<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConceptoRetencionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('concepto_retenciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_retencion_id')
                ->constrained('tipo_retenciones')
                ->onDelete('cascade');
            $table->string('name', 999);
            $table->decimal('percentage', 5, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('concepto_retenciones');
    }
}
