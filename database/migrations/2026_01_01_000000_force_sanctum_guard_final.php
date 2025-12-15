<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fuerza bruta final: Asegurar que TODO sea sanctum sin excepción.
        // Esto corre DESPUÉS de todos los seeders y migraciones anteriores.
        
        DB::table('roles')->update(['guard_name' => 'sanctum']);
        DB::table('permissions')->update(['guard_name' => 'sanctum']);
        
        if (Schema::hasColumn('model_has_roles', 'guard_name')) {
            DB::table('model_has_roles')->update(['guard_name' => 'sanctum']);
        }
        
        if (Schema::hasColumn('model_has_permissions', 'guard_name')) {
            DB::table('model_has_permissions')->update(['guard_name' => 'sanctum']);
        }
        
        // Limpiamos caché de Spatie explícitamente usando el comando si es posible, o borrando la key
        // app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions(); 
        // No podemos llamar app() aquí fácilmente, pero el update directo arregla la BD.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversible. Sanctum es el camino.
    }
};
