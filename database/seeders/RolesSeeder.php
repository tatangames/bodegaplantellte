<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Administrador
        $roleAdmin = Role::create(['name' => 'admin', 'guard_name' => 'admin']);

        // Inventario
        $roleInventario = Role::create(['name' => 'inventario', 'guard_name' => 'admin']);


        // solo para administrador
        Permission::create(['name' => 'sidebar.roles.y.permisos', 'description' => 'sidebar seccion roles y permisos'])->syncRoles($roleAdmin);
        Permission::create(['name' => 'sidebar.inventario', 'description' => 'contenedor de catalogo'])->syncRoles($roleInventario);






    }
}
