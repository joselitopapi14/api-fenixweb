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
        Schema::create('redes_sociales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('redes_sociales')->insert([
            ['nombre' => 'Facebook', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Twitter', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'X', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'TikTok', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'YouTube', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'WhatsApp', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Telegram', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Instagram', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'LinkedIn', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redes_sociales');
    }
};
