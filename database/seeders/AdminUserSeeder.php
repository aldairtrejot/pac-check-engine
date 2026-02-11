<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'soporte_rh@imssbienestar.gob.mx';

        // Crea o actualiza el usuario admin base
        // OJO: tu modelo User tiene cast 'password' => 'hashed'
        // así que puedes pasar la contraseña en texto plano.
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'SOPORTE RH',
                'password' => 'soporte2025@',
                'status' => true,

                // Estas pueden quedarse null
                'id_entidad' => null,
                'id_tipo_nomina' => null,
                'id_clues' => null,
            ]
        );

        // Buscar el rol por code (según tu tabla administracion.roles)
        $roleId = DB::table('administracion.roles')
            ->where('code', 'admin_oc')
            ->value('id');

        if (! $roleId) {
            // Si no existe el rol, no hacemos nada (evita errores)
            return;
        }

        // Insertar en la tabla pivote administracion.user_roles
        // Nota: la pivote NO tiene timestamps
        DB::table('administracion.user_roles')->updateOrInsert(
            ['user_id' => $user->id, 'role_id' => $roleId],
            []
        );
    }
}