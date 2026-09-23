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
        Schema::create('deudacarteras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cartera_id')->nullable()->constrained('carteras')->nullOnDelete();
            $table->date('fecha')->nullable()->index();

            // B - Número de documento
            $table->string('num_doc', 50)->nullable()->index();

            // C y D - Montos monetarios
            $table->decimal('importe', 15, 2)->default(0);
            $table->decimal('saldo', 15, 2)->default(0);

            // E - Fecha de vencimiento
            $table->date('vence')->nullable()->index();

            // F - Antigüedad en días
            $table->integer('antiguedad')->nullable();

            // G - Anticuación (texto: "1 a 30", "mas de 150", etc.)
            $table->string('anticuacion', 50)->nullable();

            // H - Rango (VENCIDAS / VIGENTES)
            $table->string('rango', 30)->nullable()->index();

            // I, J - Cliente y lugar
            $table->string('cliente', 255)->nullable()->index();
            $table->string('cli_lugar', 100)->nullable();

            // K, L, M - Jefe, supervisor, vendedor
            $table->string('ent_nombre_jefe_vendedor', 255)->nullable();
            $table->string('ent_nombre_supervisor', 255)->nullable();
            $table->string('ent_nombre_vendedor', 255)->nullable();

            // N - Plazo en días
            $table->integer('plazo')->nullable();

            // O - Fecha último pago (nullable)
            $table->date('fecha_ultimo_pago')->nullable();

            // P - Ciudad
            $table->string('ciu_nombre', 100)->nullable();

            // Q - Código cliente
            $table->string('codigo_cliente', 50)->nullable()->index();

            // R - Límite de crédito
            $table->decimal('limite_credito', 15, 2)->default(0);

            // S - RUT
            $table->string('rut_id', 50)->nullable();

            // T, U - Coordenadas geográficas
            $table->decimal('coordenada_x', 12, 8)->nullable();
            $table->decimal('coordenada_y', 12, 8)->nullable();

            // V - Teléfono (string por formatos variables)
            $table->string('telefono', 50)->nullable();

            // W - Estado
            $table->string('estado', 10)->nullable()->index();

            // X - Dirección
            $table->text('direccion')->nullable();

            // Y - Fecha de carga
            $table->date('fecha_carga')->nullable()->index();

            //Control de ausencia en la siguiente carga
            $table->boolean('ausente')->default(false);

            // Índice compuesto útil para consultas de cartera
            $table->index(['fecha_carga', 'rango']);
            $table->index(['codigo_cliente', 'fecha']);
            $table->index(['ausente']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deudacarteras');
    }
};
