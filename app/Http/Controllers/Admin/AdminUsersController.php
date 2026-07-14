<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUsersController extends Controller
{
    public function index()
    {
        return view('admin.users.index');
    }

    public function table(Request $request)
    {
        $limit  = max(1, min((int) $request->input('limit', 5), 50));
        $offset = max(0, (int) $request->input('offset', 0));
        $search = trim((string) $request->input('search', ''));

        $q = User::query()
            ->select([
                'id',
                'name',
                'email',
                'is_admin',
                'status',
                'id_entidad',
                'id_tipo_nomina',
                'id_clues',
                'created_at',
            ]);

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $allRow = (clone $q)->count();

        $list = $q->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'is_admin' => (bool) $u->is_admin,
                'status' => (bool) $u->status,
                'id_entidad' => $u->id_entidad,
                'id_tipo_nomina' => $u->id_tipo_nomina,
                'id_clues' => $u->id_clues,
                'created_at' => optional($u->created_at)->format('Y-m-d H:i'),
            ]);

        return response()->json([
            'list' => $list,
            'allRow' => $allRow,
            'row' => $list->count(),
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('administracion.users', 'email'),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_admin' => ['required', 'in:0,1'],
            'status' => ['nullable', 'in:0,1'],
            'id_entidad' => ['nullable', 'integer'],
            'id_tipo_nomina' => ['nullable', 'integer'],
            'id_clues' => ['nullable', 'integer'],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => mb_strtoupper(trim($data['name']), 'UTF-8'),
                'email' => strtolower(trim($data['email'])),
                'password' => Hash::make($data['password']),
                'is_admin' => (int) $data['is_admin'],
                'status' => isset($data['status']) ? (int) $data['status'] : 1,
                'id_entidad' => $data['id_entidad'] ?? null,
                'id_tipo_nomina' => $data['id_tipo_nomina'] ?? null,
                'id_clues' => $data['id_clues'] ?? null,
            ]);

            if ((int) $data['is_admin'] === 1) {
                $roleId = DB::table('administracion.roles')
                    ->whereRaw('UPPER(TRIM(code)) = ?', ['ADMIN_OC'])
                    ->value('id');

                if ($roleId) {
                    DB::table('administracion.user_roles')->updateOrInsert(
                        ['user_id' => $user->id, 'role_id' => (int) $roleId],
                        []
                    );
                }
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Usuario creado correctamente.',
        ]);
    }

    public function delete(Request $request)
    {
        $data = $request->validate([
            'id' => [
                'required',
                'integer',
                Rule::exists('administracion.users', 'id'),
            ],
        ]);

        if ((int) auth()->id() === (int) $data['id']) {
            return response()->json([
                'status' => false,
                'message' => 'No puedes eliminar tu propio usuario.',
            ]);
        }

        User::where('id', $data['id'])->delete();

        return response()->json([
            'status' => true,
            'message' => 'Usuario eliminado.',
        ]);
    }
}
