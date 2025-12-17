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
        Schema::create('tipo_documentos', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('abreviacion', 10);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Insertar datos iniciales
        DB::table('tipo_documentos')->insert([
            ['code' => '11', 'name' => 'Registro civil', 'abreviacion' => 'RC', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '12', 'name' => 'Tarjeta de identidad', 'abreviacion' => 'TI', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '13', 'name' => 'Cédula de ciudadanía', 'abreviacion' => 'CC', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '21', 'name' => 'Tarjeta de extranjería', 'abreviacion' => 'TE', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '22', 'name' => 'Cédula de extranjería', 'abreviacion' => 'CE', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '31', 'name' => 'NIT', 'abreviacion' => 'NIT', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '41', 'name' => 'Pasaporte', 'abreviacion' => 'PP', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '42', 'name' => 'Documento de identificación extranjero', 'abreviacion' => 'DIE', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '47', 'name' => 'PEP', 'abreviacion' => 'PEP', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '50', 'name' => 'NIT de otro país', 'abreviacion' => 'NITOP', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '91', 'name' => 'NUIP *', 'abreviacion' => 'NUIP', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_documentos');
    }
};
