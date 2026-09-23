<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lote_deuda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_id')->constrained('lotes')->cascadeOnDelete();
            $table->foreignId('deudacartera_id')->constrained('deudacarteras')->cascadeOnDelete();
            $table->enum('estado_gestion', ['pendiente', 'visitado', 'promesa_pago', 'pagado', 'no_contactado', 'rechazado'])
                  ->default('pendiente');
            $table->date('fecha_gestion')->nullable();
            $table->text('observacion')->nullable();
            $table->decimal('monto_recuperado', 15, 2)->default(0);
            $table->foreignId('gestionado_por')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['lote_id', 'deudacartera_id']);
            $table->index(['lote_id', 'estado_gestion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lote_deuda');
    }
};