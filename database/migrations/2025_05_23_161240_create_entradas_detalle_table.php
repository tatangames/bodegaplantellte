<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ENTRADAS DETALLE
     */
    public function up(): void
    {
        Schema::create('entradas_detalle', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_entradas')->unsigned();
            $table->bigInteger('id_material')->unsigned();

            // La cantidad registrada que no cambia
            $table->integer('cantidad_inicial');

            // 4 DECIMALES PARA PRECIO UNITARIO
            $table->decimal('precio', 10,4)->default(0);

            // PUEDE SER COMO DETALLE DEL EMPAQUE O CUALQUIER COSA
            $table->string('codigo', 100)->nullable();

            // copia nombre material
            $table->string('nombre', 300)->nullable();

            $table->foreign('id_entradas')->references('id')->on('entradas');
            $table->foreign('id_material')->references('id')->on('materiales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entradas_detalle');
    }
};
