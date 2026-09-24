<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AJUSTES DE 1 FILA
     */
    public function up(): void
    {
        Schema::create('informacion_general', function (Blueprint $table) {
            $table->id();

            // REPORTE PIXELES DISTANCIAS
            $table->integer('px_firmas');
            $table->integer('px_observaciones');

            $table->string('nombre_reporte', 100)->nullable();
            $table->string('nombre_reporte2', 100)->nullable();
            $table->string('nombre_reporte3', 100)->nullable();

            $table->boolean('salto_pagina');

            $table->string('cargo_reporte', 100)->nullable();
            $table->string('cargo_reporte2', 100)->nullable();
            $table->string('cargo_reporte3', 100)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('informacion_general');
    }
};
