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
            $table->bigInteger('id_tipoproyecto')->unsigned();
            $table->date('fecha');
            $table->string('descripcion', 800)->nullable();

            $table->boolean('es_transferencia')->default(false);
            // SABER A DONDE MANDE ESTE MATERIAL (X PROYECTO)
            $table->bigInteger('id_tipoproyecto_transferencia')->unsigned()->nullable();

            $table->string('acta_numero', 50)->nullable();
            $table->string('acta_referencia', 200)->nullable();
            $table->bigInteger('acta_id_departamento')->unsigned()->nullable();
            $table->string('acta_nombre_solic', 200)->nullable();
            $table->string('acta_cargo_solic', 200)->nullable();
            $table->text('acta_observaciones')->nullable();
            $table->string('acta_tipo_destino', 300)->nullable();

            // TRANSFERENCIA DE PROYECTO, NOMBRES PARA LAS FIRMAS
            $table->string('firma_1', 200)->nullable();
            $table->string('firma_2', 200)->nullable();

            $table->foreign('id_tipoproyecto')->references('id')->on('tipoproyecto');
            $table->foreign('id_tipoproyecto_transferencia')->references('id')->on('tipoproyecto');
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
