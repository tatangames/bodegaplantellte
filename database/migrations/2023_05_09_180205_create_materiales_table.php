<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaterialesTable extends Migration
{
    /**
     * CATALOGO DE MATERIALES
     *
     * @return void
     */
    public function up()
    {
        Schema::create('materiales', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_objespecifico')->unsigned()->nullable();
            $table->bigInteger('id_medida')->unsigned()->nullable();

            $table->string('nombre', 300);
            $table->string('codigo', 100)->nullable();

            $table->foreign('id_objespecifico')->references('id')->on('objeto_especifico');
            $table->foreign('id_medida')->references('id')->on('unidadmedida');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('materiales');
    }
}
