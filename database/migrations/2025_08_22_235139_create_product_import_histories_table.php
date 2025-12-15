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
        Schema::create('product_import_histories', function (Blueprint $table) {
            $table->id();

            // Información del archivo
            $table->string('filename'); // Nombre original del archivo Excel subido
            $table->string('stored_path')->nullable(); // Ruta donde se almacenó el archivo en storage/app/imports

            // Usuario y empresa
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Usuario que realizó la subida
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('set null'); // Empresa seleccionada (puede ser null para globales)

            // Configuración de importación
            $table->enum('modo_importacion', ['crear', 'actualizar', 'crear_actualizar'])->default('crear'); // Modo de importación seleccionado

            // Estadísticas de procesamiento
            $table->integer('total_rows')->default(0); // Total de filas procesadas en el archivo
            $table->integer('successful_imports')->default(0); // Número de registros importados exitosamente
            $table->integer('skipped_duplicates')->default(0); // Número de registros omitidos por ser duplicados
            $table->integer('failed_imports')->default(0); // Número de registros que fallaron durante la importación
            $table->integer('created_products')->default(0); // Productos nuevos creados
            $table->integer('updated_products')->default(0); // Productos existentes actualizados

            // Información de errores y duplicados
            $table->json('duplicate_products')->nullable(); // Array JSON con los nombres de productos duplicados encontrados
            $table->json('errors')->nullable(); // Array JSON con todos los errores ocurridos durante la importación

            // Estado de la importación
            $table->enum('status', ['completed', 'completed_with_errors', 'failed'])->default('completed'); // Estado de la importación

            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['user_id', 'created_at']);
            $table->index(['empresa_id', 'created_at']);
            $table->index(['status']);
            $table->index(['modo_importacion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_import_histories');
    }
};
