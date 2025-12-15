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
        Schema::table('conceptos', function (Blueprint $table) {
            // Make empresa_id nullable to allow global concepts
            $table->foreignId('empresa_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conceptos', function (Blueprint $table) {
            // Revert empresa_id to NOT NULL (will fail if there are NULL values)
            $table->foreignId('empresa_id')->nullable(false)->change();
        });
    }
};
