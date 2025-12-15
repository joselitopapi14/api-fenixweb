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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_factura')->nullable();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tipo_movimiento_id')->constrained('tipo_movimientos')->onDelete('cascade');
            $table->foreignId('tipo_factura_id')->constrained('tipo_facturas')->onDelete('cascade');
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('medio_pago_id')->constrained('medio_pagos')->onDelete('cascade');
            $table->foreignId('tipo_pago_id')->constrained('tipo_pagos')->onDelete('cascade');
            $table->decimal('total', 15, 2);
            $table->decimal('valor_impuestos', 15, 2)->default(0);
            $table->string('issue_date')->nullable();
            $table->string('due_date')->nullable();
            $table->string('cufe')->nullable();
            $table->string('qr_code')->nullable();
            $table->string('valor_recibido')->nullable();
            $table->string('cambio')->nullable();
            $table->string('subtotal')->nullable();
            $table->string('estado')->nullable();
            $table->string('xml_url')->nullable();
            $table->string('obseraciones')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
