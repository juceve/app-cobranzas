<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('deudacarteras')->update([
            'num_doc' => DB::raw('UPPER(TRIM(num_doc))'),
            'codigo_cliente' => DB::raw('UPPER(TRIM(codigo_cliente))'),
        ]);

        Schema::table('deudacarteras', function (Blueprint $table) {
            $table->unique(['cartera_id', 'num_doc', 'codigo_cliente'], 'deudacarteras_cartera_doc_cliente_unique');
        });
    }

    public function down(): void
    {
        Schema::table('deudacarteras', function (Blueprint $table) {
            $table->dropUnique('deudacarteras_cartera_doc_cliente_unique');
        });
    }
};
