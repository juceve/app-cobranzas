<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cartera_id')->constrained('carteras')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users'); // cobrador asignado
            $table->foreignId('created_by')->constrained('users'); // jefe de ventas
            $table->string('codigo')->unique(); // ej: LOTE-2026-0001
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['borrador', 'asignado', 'en_proceso', 'cerrado', 'cancelado'])
                  ->default('borrador');
            $table->date('fecha_asignacion')->nullable();
            $table->date('fecha_limite')->nullable();
            $table->integer('total_deudas')->default(0);
            $table->decimal('total_saldo', 15, 2)->default(0);
            $table->decimal('total_importe', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'estado']);
            $table->index(['cartera_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};