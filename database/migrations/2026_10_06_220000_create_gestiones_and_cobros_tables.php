<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_id')->constrained('lotes')->cascadeOnDelete();
            $table->string('codigo_cliente', 50);
            $table->foreignId('user_id')->constrained('users');
            $table->string('tipo', 20);
            $table->string('resultado', 30);
            $table->text('observacion')->nullable();
            $table->date('fecha_promesa')->nullable();
            $table->decimal('monto_prometido', 15, 2)->nullable();
            $table->timestamp('gestionado_en')->useCurrent();
            $table->timestamps();

            $table->index(['lote_id', 'codigo_cliente']);
            $table->index(['user_id', 'gestionado_en']);
        });

        Schema::create('cobros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_id')->constrained('lotes')->cascadeOnDelete();
            $table->foreignId('deudacartera_id')->constrained('deudacarteras')->cascadeOnDelete();
            $table->foreignId('gestion_id')->nullable()->constrained('gestiones')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('monto', 15, 2);
            $table->date('fecha_cobro');
            $table->string('forma_pago', 20);
            $table->string('referencia', 100)->nullable();
            $table->string('estado', 20)->default('registrado');
            $table->foreignId('validado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validado_en')->nullable();
            $table->timestamps();

            $table->index(['lote_id', 'estado']);
            $table->index(['user_id', 'fecha_cobro']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cobros');
        Schema::dropIfExists('gestiones');
    }
};
