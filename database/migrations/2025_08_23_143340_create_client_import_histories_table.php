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
        Schema::create('client_import_histories', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('stored_path')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('empresa_id');
            $table->enum('modo_importacion', ['crear', 'actualizar', 'crear_actualizar']);
            $table->integer('total_rows')->default(0);
            $table->integer('successful_imports')->default(0);
            $table->integer('skipped_duplicates')->default(0);
            $table->integer('failed_imports')->default(0);
            $table->integer('created_clients')->default(0);
            $table->integer('updated_clients')->default(0);
            $table->json('duplicate_clients')->nullable();
            $table->json('errors')->nullable();
            $table->enum('status', ['completed', 'completed_with_errors', 'failed'])->default('completed');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('empresa_id')->references('id')->on('empresas');

            $table->index(['empresa_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_import_histories');
    }
};
