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

        // Insert initial data
        DB::table('impuesto_porcentajes')->insert([
            // impuesto_id = 1
            ['impuesto_id' => 1, 'percentage' => 0.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 1, 'percentage' => 16.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 1, 'percentage' => 19.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 1, 'percentage' => 20.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 1, 'percentage' => 35.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 1, 'percentage' => 5.00, 'created_at' => now(), 'updated_at' => now()],

            // impuesto_id = 4
            ['impuesto_id' => 4, 'percentage' => 16.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 4, 'percentage' => 2.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 4, 'percentage' => 4.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 4, 'percentage' => 6.00, 'created_at' => now(), 'updated_at' => now()],

            // impuesto_id = 10
            ['impuesto_id' => 10, 'percentage' => 0.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 10, 'percentage' => 16.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 10, 'percentage' => 19.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 10, 'percentage' => 20.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 10, 'percentage' => 35.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 10, 'percentage' => 5.00, 'created_at' => now(), 'updated_at' => now()],

            // impuesto_id = 16
            ['impuesto_id' => 16, 'percentage' => 20.00, 'created_at' => now(), 'updated_at' => now()],

            // impuesto_id = 17
            ['impuesto_id' => 17, 'percentage' => 20.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 17, 'percentage' => 25.00, 'created_at' => now(), 'updated_at' => now()],
        ]);
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
