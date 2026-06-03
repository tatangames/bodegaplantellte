<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;

class UsuariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Usuario::create([
            'nombre' => 'Admin',
            'usuario' => 'admin',
            'password' => bcrypt('1234'),
        ])->assignRole('admin');

        Usuario::create([
            'nombre' => 'Inventario',
            'usuario' => 'inventario',
            'password' => bcrypt('1234'),
        ])->assignRole('inventario');
    }
}
