<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deudacarteras', function (Blueprint $table) {
            if (!Schema::hasColumn('deudacarteras', 'ausente')) {
                $table->boolean('ausente')->default(false)->after('fecha_carga');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deudacarteras', function (Blueprint $table) {
            $table->dropColumn('ausente');
        });
    }
};