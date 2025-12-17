<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tipo_responsabilidades', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('code', 10);
            $table->timestamps();
            $table->softDeletes();
        });

        // Insert default records
        DB::table('tipo_responsabilidades')->insert([
            ['name' => 'Gran contribuyente', 'code' => 'O-13'],
            ['name' => 'Autorretenedor', 'code' => 'O-15'],
            ['name' => 'Agente de retención IVA', 'code' => 'O-23'],
            ['name' => 'Régimen simple de tributación', 'code' => 'O-47'],
            ['name' => 'No responsable', 'code' => 'R-99-PN'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_responsabilidades');
    }
};
