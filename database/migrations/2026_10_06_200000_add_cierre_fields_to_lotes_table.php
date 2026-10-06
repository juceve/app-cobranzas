<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            $table->timestamp('cerrado_en')->nullable()->after('fecha_limite');
            $table->foreignId('cerrado_por')->nullable()->after('cerrado_en')->constrained('users')->nullOnDelete();
            $table->text('motivo_cierre')->nullable()->after('cerrado_por');
        });
    }

    public function down(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cerrado_por');
            $table->dropColumn(['cerrado_en', 'motivo_cierre']);
        });
    }
};
