<?php

namespace Database\Seeders;

use App\Models\TipoEntrada;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoEntradaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TipoEntrada::create([
            'nombre' => 'REPUESTOS',
        ]);

        TipoEntrada::create([
            'nombre' => 'LLANTAS',
        ]);
    }
}
