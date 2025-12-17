<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImpuestoPorcentajesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('impuesto_porcentajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('impuesto_id')
                ->constrained('impuestos')
                ->onDelete('cascade');
            $table->decimal('percentage', 5, 2)->default(0.00);
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
        Schema::dropIfExists('impuesto_porcentajes');
    }
}
