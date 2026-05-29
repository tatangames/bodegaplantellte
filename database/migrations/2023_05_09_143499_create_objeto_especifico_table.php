<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * OBJ ESPECIFICO - CODIGOS
     */
    public function up(): void
    {
        Schema::create('objeto_especifico', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_cuenta')->unsigned();
            $table->string('nombre', 800);
            $table->string('codigo', 100);

            $table->foreign('id_cuenta')->references('id')->on('cuenta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('objeto_especifico');
    }
};
