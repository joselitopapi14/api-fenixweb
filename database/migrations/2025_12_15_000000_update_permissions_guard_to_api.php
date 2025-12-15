<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar roles
        DB::table('roles')
            ->where('guard_name', 'web')
            ->update(['guard_name' => 'api']);

        // Actualizar permisos
        DB::table('permissions')
            ->where('guard_name', 'web')
            ->update(['guard_name' => 'api']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir a web si es necesario
        DB::table('roles')
            ->where('guard_name', 'api')
            ->update(['guard_name' => 'web']);

        DB::table('permissions')
            ->where('guard_name', 'api')
            ->update(['guard_name' => 'web']);
    }
};
