<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recibos', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 30)->nullable()->unique();
            $table->string('token', 64)->unique();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('lote_id')->constrained('lotes');
            $table->foreignId('gestion_id')->nullable()->constrained('gestiones')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('codigo_cliente', 50);
            $table->string('cliente');
            $table->string('empresa');
            $table->string('cobrador');
            $table->string('forma_pago', 20);
            $table->string('referencia', 100)->nullable();
            $table->decimal('monto_total', 15, 2);
            $table->date('fecha_cobro');
            $table->timestamp('emitido_en');
            $table->timestamps();

            $table->index(['lote_id', 'codigo_cliente']);
            $table->index(['user_id', 'fecha_cobro']);
        });

        Schema::table('cobros', function (Blueprint $table) {
            $table->foreignId('recibo_id')->nullable()->after('gestion_id')->constrained('recibos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cobros', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recibo_id');
        });

        Schema::dropIfExists('recibos');
    }
};
