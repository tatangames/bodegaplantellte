<?php

namespace Database\Seeders;

use App\Models\TipoCompra;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoCompraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TipoCompra::create([
            'nombre' => 'PROCESO DE COMPRA',
        ]);

        TipoCompra::create([
            'nombre' => 'CAJA CHICA',
        ]);
    }
}
