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


            $table->string('acta_numero', 50)->nullable()->after('descripcion');
            $table->string('acta_referencia', 200)->nullable()->after('acta_numero');
            $table->bigInteger('acta_id_departamento')->unsigned()->nullable()->after('acta_referencia');
            $table->string('acta_nombre_solic', 200)->nullable()->after('acta_id_departamento');
            $table->string('acta_cargo_solic', 200)->nullable()->after('acta_nombre_solic');
            $table->text('acta_observaciones')->nullable()->after('acta_cargo_solic');
            $table->string('acta_tipo_destino', 300)->nullable()->after('acta_observaciones');


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
