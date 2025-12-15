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
            $table->foreignId('tipo_pago_id')->nullable()->after('cliente_id')->constrained('tipo_pagos')->onDelete('set null');
            $table->foreignId('medio_pago_id')->nullable()->after('tipo_pago_id')->constrained('medio_pagos')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documento_equivalentes', function (Blueprint $table) {
            $table->dropForeign(['tipo_pago_id']);
            $table->dropForeign(['medio_pago_id']);
            $table->dropColumn(['tipo_pago_id', 'medio_pago_id']);
        });
    }
};
