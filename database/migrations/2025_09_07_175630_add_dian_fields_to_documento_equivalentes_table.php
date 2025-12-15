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
            $table->string('xml_url')->nullable()->after('estado');
            $table->string('cuds')->nullable()->after('xml_url');
            $table->string('qr_code')->nullable()->after('cuds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documento_equivalentes', function (Blueprint $table) {
            $table->dropColumn(['xml_url', 'cuds', 'qr_code']);
        });
    }
};
