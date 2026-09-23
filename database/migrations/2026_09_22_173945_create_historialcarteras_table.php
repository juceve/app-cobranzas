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
        Schema::create('historialcarteras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cartera_id')->nullable()->constrained('carteras')->nullOnDelete();
            $table->dateTime('fecha')->nullable()->index();
            $table->integer('cambios')->nullable();
            $table->integer('nuevos')->nullable();
            $table->integer('salidas')->nullable();
            $table->string('doc_original')->nullable();
            $table->string('hash', 64)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado',['pendiente', 'procesado', 'error'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historialcarteras');
    }
};
