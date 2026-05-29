<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CUENTA - CODIGOS
     */
    public function up(): void
    {
        Schema::create('cuenta', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_rubro')->unsigned();
            $table->string('codigo',100);
            $table->string('nombre', 300);

            $table->foreign('id_rubro')->references('id')->on('rubro');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuenta');
    }
};
