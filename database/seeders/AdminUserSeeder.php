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
        // ✅ Usa el correo que ya tienes en allowedEmails
        $email = 'soporte_rh@imssbienestar.gob.mx';

        // 1) Crear o actualizar usuario
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Soporte RH',
                'password' => Hash::make('soporte2025@'),
            ]
        );

        // 2) Buscar id del rol admin_oc
        $roleId = DB::table('administracion.roles')
            ->where('code', 'admin_oc')
            ->value('id');

        // 3) Asignar rol en la pivot administracion.user_roles
        if ($roleId) {
            DB::table('administracion.user_roles')->updateOrInsert(
                ['user_id' => $user->id, 'role_id' => $roleId],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
