<?php

namespace Database\Seeders;

use App\Models\Equipos;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EquiposSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Equipos::create([
            'nombre' => 'Salida General',
        ]);

        Equipos::create([
            'nombre' => '1',
        ]);

        Equipos::create([
            'nombre' => '2',
        ]);
    }
}
