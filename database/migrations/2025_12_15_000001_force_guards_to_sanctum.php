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
        // Fuerza bruta: mover TODO lo que sea 'api' o 'web' a 'sanctum'
        DB::table('roles')
            ->whereIn('guard_name', ['web', 'api'])
            ->update(['guard_name' => 'sanctum']);

        DB::table('permissions')
            ->whereIn('guard_name', ['web', 'api'])
            ->update(['guard_name' => 'sanctum']);
            
        // También actualizar la asignación de roles a usuarios si la tabla tiene guard_name
        if (Schema::hasColumn('model_has_roles', 'guard_name')) {
             DB::table('model_has_roles')
            ->whereIn('guard_name', ['web', 'api'])
            ->update(['guard_name' => 'sanctum']);
        }
        
        if (Schema::hasColumn('model_has_permissions', 'guard_name')) {
             DB::table('model_has_permissions')
            ->whereIn('guard_name', ['web', 'api'])
            ->update(['guard_name' => 'sanctum']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertimos automáticamente para no causar inconsistencias, 
        // pero idealmente volvería a 'web' que es el default de Laravel.
    }
};
