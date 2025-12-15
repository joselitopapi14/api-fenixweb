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
        Schema::table('documento_equivalentes', function (Blueprint $table) {
            $table->unsignedBigInteger('resolucion_id')->nullable()->after('empresa_id');
            $table->foreign('resolucion_id')->references('id')->on('resoluciones_facturacion')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documento_equivalentes', function (Blueprint $table) {
            $table->dropForeign(['resolucion_id']);
            $table->dropColumn('resolucion_id');
        });
    }
};
