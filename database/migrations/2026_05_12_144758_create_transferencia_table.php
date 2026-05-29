<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CIERRE DE PROYECTO
     */
    public function up(): void
    {
        Schema::create('transferencia', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('id_tipoproyecto_origen')->unsigned()->nullable();
            $table->bigInteger('id_salida')->unsigned()->nullable();
            $table->bigInteger('id_entrada')->unsigned()->nullable();
            $table->bigInteger('id_tipoproyecto')->unsigned()->nullable();
            $table->date('fecha');
            $table->string('descripcion', 800)->nullable();
            $table->string('documento', 100)->nullable();
            $table->enum('tipo_salida', [
                'proyecto',
                'general',
                'snapshot',
            ])->default('proyecto');

            // 'normal'  = transferencia creada desde el flujo normal
            // 'reserva' = generada por un despacho de reserva (sin datos para PDF)
            $table->string('origen_registro', 20)
                ->default('normal')->nullable();

            $table->foreign('id_salida')->references('id')->on('salidas');
            $table->foreign('id_entrada')->references('id')->on('entradas');
            $table->foreign('id_tipoproyecto_origen')->references('id')->on('tipoproyecto');
            $table->foreign('id_tipoproyecto')->references('id')->on('tipoproyecto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferencia');
    }
};
