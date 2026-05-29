<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RUBRO - CODIGOS
     */
    public function up(): void
    {
        Schema::create('rubro', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 100);
            $table->string('nombre', 800);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rubro');
    }
};
