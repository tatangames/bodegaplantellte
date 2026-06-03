<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SALIDAS
     */
    public function up(): void
    {
        Schema::create('salidas', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_equipo')->unsigned();
            $table->date('fecha');
            $table->string('descripcion', 800)->nullable();

            // FICHA DE SALIDA
            $table->string('ficha_nombre', 100)->nullable();
            $table->string('ficha_talonario', 100)->nullable();

            $table->foreign('id_equipo')->references('id')->on('equipos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salidas');
    }
};
