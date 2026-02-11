<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            [
                'code' => 'admin_oc',
                'name' => 'Administrador (Oficinas Centrales)',
                'is_central' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'supervisor_oc',
                'name' => 'Supervisor (Oficinas Centrales)',
                'is_central' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'revisor_est',
                'name' => 'Revisor (Operativo)',
                'is_central' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'supervisor_est',
                'name' => 'Supervisor (Operativo)',
                'is_central' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // upsert:
        // - inserta si no existe (usa created_at/updated_at)
        // - si ya existe por code, SOLO actualiza estas columnas (NO pisa created_at)
        DB::table('administracion.roles')->upsert(
            $rows,
            ['code'], // clave única
            ['name', 'is_central', 'is_active', 'updated_at'] // columnas a actualizar
        );
    }
}