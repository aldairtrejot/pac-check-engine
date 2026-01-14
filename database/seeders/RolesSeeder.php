<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['code' => 'admin_oc',       'name' => 'Administrador (Oficinas Centrales)', 'is_central' => true],
            ['code' => 'supervisor_oc',  'name' => 'Supervisor (Oficinas Centrales)',    'is_central' => true],
            ['code' => 'revisor_est',    'name' => 'Revisor (Operativo)',               'is_central' => false],
            ['code' => 'supervisor_est', 'name' => 'Supervisor (Operativo)',            'is_central' => false],
        ];

        foreach ($roles as $r) {
            DB::table('administracion.roles')->updateOrInsert(
                ['code' => $r['code']],
                [
                    'name'       => $r['name'],
                    'is_central'  => $r['is_central'],
                    'is_active'   => true,
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ]
            );
        }
    }
}
