<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'soporte_rh@imssbienestar.gob.mx';

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Soporte RH',
                'password' => Hash::make('soporte2025@'),
            ]
        );

        $roleId = DB::table('administracion.roles')
            ->where('code', 'admin_oc')
            ->value('id');

        if ($roleId) {
            DB::table('administracion.user_roles')->updateOrInsert(
                ['user_id' => $user->id, 'role_id' => $roleId],
                [] // ✅ NO metas timestamps si la tabla no los tiene
            );
        }
    }
}
