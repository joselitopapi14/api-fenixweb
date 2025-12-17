<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTipoRetencionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tipo_retenciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('impuesto_id')->nullable()->constrained('impuestos')->onDelete('cascade');
            $table->string('name');
            $table->string('code');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('tipo_retenciones')->insert([
            ['name' => 'ReteIVA', 'code' => '05', 'impuesto_id' => 1],
            ['name' => 'ReteRenta', 'code' => '06', 'impuesto_id' => null],
            ['name' => 'ReteFuente', 'code' => '06', 'impuesto_id' => null],
            ['name' => 'ReteICA', 'code' => '07', 'impuesto_id' => 3],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tipo_retenciones');
    }
}
