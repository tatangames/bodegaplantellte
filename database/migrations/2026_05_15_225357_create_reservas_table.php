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
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_entrada_detalle')->unsigned();
            $table->bigInteger('id_tipoproyecto')->unsigned();          // proyecto cerrado origen
            $table->bigInteger('id_tipoproyecto_destino')->unsigned()->nullable(); // se llena al despachar
            $table->integer('cantidad');
            $table->string('descripcion', 800)->nullable();
            $table->date('fecha_reserva');
            $table->date('fecha_despacho')->nullable();                 // se llena al despachar
            $table->boolean('despachado')->default(false);
            $table->string('tipo_destino', 20)->nullable();            // 'proyecto' | 'general' — se llena al despachar
            $table->timestamps();

            $table->bigInteger('id_salida')->unsigned()->nullable();
            $table->bigInteger('id_entrada')->unsigned()->nullable();

            $table->foreign('id_salida')->references('id')->on('salidas');
            $table->foreign('id_entrada')->references('id')->on('entradas');
            $table->foreign('id_entrada_detalle')->references('id')->on('entradas_detalle');
            $table->foreign('id_tipoproyecto')->references('id')->on('tipoproyecto');
            $table->foreign('id_tipoproyecto_destino')->references('id')->on('tipoproyecto');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
