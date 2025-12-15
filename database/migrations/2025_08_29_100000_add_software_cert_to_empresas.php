<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('software_id')->nullable()->after('pagina_web');
            $table->string('software_pin')->nullable()->after('software_id');
            $table->string('certificate_path')->nullable()->after('software_pin');
            $table->string('certificate_password')->nullable()->after('certificate_path');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['software_id', 'software_pin', 'certificate_path', 'certificate_password']);
        });
    }
};
