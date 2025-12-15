<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImpuestosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('impuestos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        DB::table('impuestos')->insert([
            ['name' => 'IVA', 'code' => '01'],
            ['name' => 'IC', 'code' => '02'],
            ['name' => 'ICA', 'code' => '03'],
            ['name' => 'INC', 'code' => '04'],
            ['name' => 'FtoHorticultura', 'code' => '20'],
            ['name' => 'Timbre', 'code' => '21'],
            ['name' => 'INC Bolsas', 'code' => '22'],
            ['name' => 'INCarbono', 'code' => '23'],
            ['name' => 'INCombustibles', 'code' => '24'],
            ['name' => 'Sobretasa Combustibles', 'code' => '25'],
            ['name' => 'Sordicom', 'code' => '26'],
            ['name' => 'Nombre de la figura tributaria**', 'code' => 'ZZ*'],
            ['name' => 'ICL', 'code' => '32'],
            ['name' => 'INPP', 'code' => '33'],
            ['name' => 'IBUA', 'code' => '34'],
            ['name' => 'ICUI', 'code' => '35'],
            ['name' => 'ADV', 'code' => '36'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('impuestos');
    }
}
