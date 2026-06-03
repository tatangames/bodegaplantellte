<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ENTRADAS POR DIFERENTES AREAS
     */
    public function up(): void
    {
        Schema::create('entradas', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_tipoentrada')->unsigned();
            $table->bigInteger('id_tipocompra')->unsigned();

            $table->date('fecha');
            $table->string('factura', 100)->nullable();
            $table->string('descripcion', 800)->nullable();

            $table->foreign('id_tipoentrada')->references('id')->on('tipo_entrada');
            $table->foreign('id_tipocompra')->references('id')->on('tipo_compra');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entradas');
    }
};
