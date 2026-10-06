<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            $table->string('ciudad', 100)->nullable()->after('descripcion');
            $table->unsignedInteger('total_deudores')->default(0)->after('total_deudas');
        });

        Schema::table('lote_deuda', function (Blueprint $table) {
            $table->string('codigo_cliente', 50)->nullable()->after('deudacartera_id');
            $table->unsignedInteger('orden_visita')->nullable()->after('codigo_cliente');

            $table->index(['lote_id', 'orden_visita']);
            $table->index(['lote_id', 'codigo_cliente']);
        });

        DB::statement(
            'UPDATE lote_deuda SET codigo_cliente = (SELECT d.codigo_cliente FROM deudacarteras d WHERE d.id = lote_deuda.deudacartera_id)'
        );
    }

    public function down(): void
    {
        Schema::table('lote_deuda', function (Blueprint $table) {
            $table->dropIndex(['lote_id', 'orden_visita']);
            $table->dropIndex(['lote_id', 'codigo_cliente']);
            $table->dropColumn(['codigo_cliente', 'orden_visita']);
        });

        Schema::table('lotes', function (Blueprint $table) {
            $table->dropColumn(['ciudad', 'total_deudores']);
        });
    }
};
