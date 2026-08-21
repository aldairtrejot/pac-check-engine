<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\UserActionLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUsersController extends Controller
{
    private const MAX_LIMIT = 50;

    public function index()
    {
        return view('admin.users.index');
    }

    public function options()
    {
        return response()->json([
            'status' => true,
            'roles' => $this->rolesOptions(),
            'entidades' => $this->entidadesOptions(),
            'tipos_nomina' => $this->tiposNominaOptions(),
            'clues' => $this->cluesOptions(),
        ]);
    }

    public function table(Request $request)
    {
        $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_LIMIT],
            'offset' => ['nullable', 'integer', 'min:0'],
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:0,1'],
            'role_id' => ['nullable', 'integer'],
            'id_entidad' => ['nullable', 'integer'],
            'id_tipo_nomina' => ['nullable', 'integer'],
            'id_clues' => ['nullable', 'integer'],
        ]);

        $limit = max(1, min((int) $request->input('limit', 5), self::MAX_LIMIT));
        $offset = max(0, (int) $request->input('offset', 0));
        $search = trim((string) $request->input('search', ''));

        $roleAgg = DB::raw("
            (
                SELECT
                    ur.user_id,
                    STRING_AGG(r.name, ', ' ORDER BY r.name) AS roles,
                    STRING_AGG(r.code, ',' ORDER BY r.code) AS role_codes
                FROM administracion.user_roles ur
                INNER JOIN administracion.roles r ON r.id = ur.role_id
                WHERE r.is_active = true
                GROUP BY ur.user_id
            ) AS rr
        ");

        $q = DB::table('administracion.users as u')
            ->leftJoin($roleAgg, 'rr.user_id', '=', 'u.id')
            ->leftJoin('administracion.cat_entidad as ce', 'ce.id_entidad', '=', 'u.id_entidad')
            ->leftJoin('administracion.cat_tipo_nomina as ctn', 'ctn.id_tipo_nomina', '=', 'u.id_tipo_nomina')
            ->leftJoin('administracion.cat_clues as cc', 'cc.id_clues', '=', 'u.id_clues')
            ->select([
                'u.id',
                'u.name',
                'u.email',
                'u.status',
                'u.id_entidad',
                'u.id_tipo_nomina',
                'u.id_clues',
                'u.created_at',
                'u.updated_at',
                DB::raw("COALESCE(rr.roles, '') AS roles"),
                DB::raw("COALESCE(rr.role_codes, '') AS role_codes"),
                DB::raw("COALESCE(ce.nombre, '') AS entidad_nombre"),
                DB::raw("COALESCE(ctn.codigo, '') AS tipo_nomina_codigo"),
                DB::raw("COALESCE(ctn.nombre, '') AS tipo_nomina_nombre"),
                DB::raw("COALESCE(cc.clues, '') AS clues_codigo"),
            ]);

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('u.name', 'ILIKE', "%{$search}%")
                    ->orWhere('u.email', 'ILIKE', "%{$search}%")
                    ->orWhere('rr.roles', 'ILIKE', "%{$search}%")
                    ->orWhere('ce.nombre', 'ILIKE', "%{$search}%")
                    ->orWhere('ctn.codigo', 'ILIKE', "%{$search}%")
                    ->orWhere('ctn.nombre', 'ILIKE', "%{$search}%")
                    ->orWhere('cc.clues', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $q->where('u.status', (bool) ((int) $request->input('status')));
        }

        if ($request->filled('id_entidad')) {
            $q->where('u.id_entidad', (int) $request->input('id_entidad'));
        }

        if ($request->filled('id_tipo_nomina')) {
            $q->where('u.id_tipo_nomina', (int) $request->input('id_tipo_nomina'));
        }

        if ($request->filled('id_clues')) {
            $q->where('u.id_clues', (int) $request->input('id_clues'));
        }

        if ($request->filled('role_id')) {
            $roleId = (int) $request->input('role_id');

            $q->whereExists(function ($exists) use ($roleId) {
                $exists->select(DB::raw(1))
                    ->from('administracion.user_roles as ur_filter')
                    ->whereColumn('ur_filter.user_id', 'u.id')
                    ->where('ur_filter.role_id', $roleId);
            });
        }

        $allRow = (clone $q)->count();

        $list = $q->orderByDesc('u.id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($u) {
                $roleCodes = $this->splitRoleCodes((string) $u->role_codes);

                return [
                    'id' => (int) $u->id,
                    'name' => (string) $u->name,
                    'email' => (string) $u->email,
                    'is_admin' => $this->roleCodesContainAdmin($roleCodes),
                    'status' => (bool) $u->status,
                    'id_entidad' => $u->id_entidad !== null ? (int) $u->id_entidad : null,
                    'id_tipo_nomina' => $u->id_tipo_nomina !== null ? (int) $u->id_tipo_nomina : null,
                    'id_clues' => $u->id_clues !== null ? (int) $u->id_clues : null,
                    'roles' => (string) $u->roles,
                    'role_codes' => $roleCodes,
                    'entidad_nombre' => (string) $u->entidad_nombre,
                    'tipo_nomina_codigo' => (string) $u->tipo_nomina_codigo,
                    'tipo_nomina_nombre' => (string) $u->tipo_nomina_nombre,
                    'clues_codigo' => (string) $u->clues_codigo,
                    'created_at' => $u->created_at ? date('Y-m-d H:i', strtotime((string) $u->created_at)) : '',
                    'updated_at' => $u->updated_at ? date('Y-m-d H:i', strtotime((string) $u->updated_at)) : '',
                ];
            });

        return response()->json([
            'status' => true,
            'list' => $list,
            'allRow' => $allRow,
            'row' => $list->count(),
        ]);
    }

    public function save(Request $request)
    {
        $data = $this->validateUserPayload($request, null, passwordRequired: true);
        $roleIds = $this->normalizeRoleIds($data['role_ids'] ?? []);

        $user = DB::transaction(function () use ($data, $roleIds) {
            $user = User::create([
                'name' => mb_strtoupper(trim($data['name']), 'UTF-8'),
                'email' => mb_strtolower(trim($data['email']), 'UTF-8'),
                'password' => Hash::make($data['password']),
                'status' => isset($data['status']) ? (bool) ((int) $data['status']) : true,
                'id_entidad' => $data['id_entidad'] ?? null,
                'id_tipo_nomina' => $data['id_tipo_nomina'] ?? null,
                'id_clues' => $data['id_clues'] ?? null,
            ]);

            $user->roles()->sync($roleIds);

            return $user->fresh(['roles']);
        });

        UserActionLogger::write(
            idUsuario: auth()->id() ? (int) auth()->id() : null,
            modulo: 'USUARIOS',
            accion: 'CREAR_USUARIO',
            descripcion: 'Alta de usuario desde el módulo de administración.',
            idReferencia: $user->id,
            payload: ['roles' => $user->roles->pluck('code')->values()->all()],
            newValues: $this->userSnapshot($user)
        );

        return response()->json([
            'status' => true,
            'message' => 'Usuario creado correctamente.',
        ]);
    }

    public function update(Request $request)
    {
        $id = (int) $request->input('id');
        $user = User::with('roles')->findOrFail($id);
        $old = $this->userSnapshot($user);

        $data = $this->validateUserPayload($request, $user->id, passwordRequired: false);
        $roleIds = $this->normalizeRoleIds($data['role_ids'] ?? []);

        if ((int) auth()->id() === $user->id && isset($data['status']) && (int) $data['status'] === 0) {
            return response()->json([
                'status' => false,
                'message' => 'No puedes desactivar tu propia cuenta.',
            ], 422);
        }

        DB::transaction(function () use ($user, $data, $roleIds) {
            $update = [
                'name' => mb_strtoupper(trim($data['name']), 'UTF-8'),
                'email' => mb_strtolower(trim($data['email']), 'UTF-8'),
                'status' => isset($data['status']) ? (bool) ((int) $data['status']) : true,
                'id_entidad' => $data['id_entidad'] ?? null,
                'id_tipo_nomina' => $data['id_tipo_nomina'] ?? null,
                'id_clues' => $data['id_clues'] ?? null,
            ];

            if (! empty($data['password'])) {
                $update['password'] = Hash::make($data['password']);
            }

            $user->update($update);
            $user->roles()->sync($roleIds);
        });

        $user = $user->fresh(['roles']);

        UserActionLogger::write(
            idUsuario: auth()->id() ? (int) auth()->id() : null,
            modulo: 'USUARIOS',
            accion: 'ACTUALIZAR_USUARIO',
            descripcion: 'Modificación de usuario, alcance o roles.',
            idReferencia: $user->id,
            oldValues: $old,
            newValues: $this->userSnapshot($user)
        );

        return response()->json([
            'status' => true,
            'message' => 'Usuario actualizado correctamente.',
        ]);
    }

    public function toggleStatus(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer', Rule::exists('administracion.users', 'id')],
            'status' => ['required', 'in:0,1'],
        ]);

        if ((int) auth()->id() === (int) $data['id'] && (int) $data['status'] === 0) {
            return response()->json([
                'status' => false,
                'message' => 'No puedes desactivar tu propia cuenta.',
            ], 422);
        }

        $user = User::with('roles')->findOrFail((int) $data['id']);
        $old = $this->userSnapshot($user);

        $user->update([
            'status' => (bool) ((int) $data['status']),
        ]);

        $user = $user->fresh(['roles']);

        UserActionLogger::write(
            idUsuario: auth()->id() ? (int) auth()->id() : null,
            modulo: 'USUARIOS',
            accion: (bool) $user->status ? 'ACTIVAR_USUARIO' : 'DESACTIVAR_USUARIO',
            descripcion: 'Cambio de estatus de cuenta de usuario.',
            idReferencia: $user->id,
            oldValues: $old,
            newValues: $this->userSnapshot($user)
        );

        return response()->json([
            'status' => true,
            'message' => (bool) $user->status ? 'Usuario activado.' : 'Usuario desactivado.',
        ]);
    }

    public function delete(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer', Rule::exists('administracion.users', 'id')],
        ]);

        if ((int) auth()->id() === (int) $data['id']) {
            return response()->json([
                'status' => false,
                'message' => 'No puedes desactivar tu propia cuenta.',
            ], 422);
        }

        $request->merge(['status' => 0]);

        return $this->toggleStatus($request);
    }

    private function validateUserPayload(Request $request, ?int $userId, bool $passwordRequired): array
    {
        $passwordRules = $passwordRequired
            ? ['required', 'string', 'min:8', 'confirmed']
            : ['nullable', 'string', 'min:8', 'confirmed'];

        return $request->validate([
            'id' => [$userId ? 'required' : 'nullable', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('administracion.users', 'email')->ignore($userId),
            ],
            'password' => $passwordRules,
            'status' => ['required', 'in:0,1'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['required', 'integer', Rule::exists('administracion.roles', 'id')],
            'id_entidad' => ['nullable', 'integer'],
            'id_tipo_nomina' => ['nullable', 'integer'],
            'id_clues' => ['nullable', 'integer'],
        ]);
    }

    private function rolesOptions(): array
    {
        try {
            return DB::table('administracion.roles')
                ->select([
                    'id',
                    'code',
                    'name',
                    DB::raw("name AS descripcion"),
                    'is_central',
                    'is_active',
                ])
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn ($role) => [
                    'id' => (int) $role->id,
                    'code' => (string) $role->code,
                    'name' => (string) $role->name,
                    'descripcion' => (string) $role->descripcion,
                    'is_central' => (bool) $role->is_central,
                    'is_active' => (bool) $role->is_active,
                ])
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function entidadesOptions(): array
    {
        try {
            return DB::table('administracion.cat_entidad')
                ->select([
                    'id_entidad as id',
                    DB::raw("COALESCE(nombre, '') AS descripcion"),
                ])
                ->orderBy('nombre')
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'descripcion' => (string) $row->descripcion,
                ])
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function tiposNominaOptions(): array
    {
        try {
            return DB::table('administracion.cat_tipo_nomina')
                ->select([
                    'id_tipo_nomina as id',
                    'codigo',
                    'nombre',
                    DB::raw("TRIM(CONCAT_WS(' - ', NULLIF(codigo, ''), NULLIF(nombre, ''))) AS descripcion"),
                ])
                ->orderBy('codigo')
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'codigo' => (string) $row->codigo,
                    'nombre' => (string) $row->nombre,
                    'descripcion' => (string) ($row->descripcion ?: $row->codigo),
                ])
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function cluesOptions(): array
    {
        try {
            return DB::table('administracion.cat_clues')
                ->select([
                    'id_clues as id',
                    'clues',
                    DB::raw("clues AS descripcion"),
                ])
                ->orderBy('clues')
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'clues' => (string) $row->clues,
                    'descripcion' => (string) ($row->descripcion ?: $row->clues),
                ])
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function normalizeRoleIds(array $roleIds): array
    {
        return collect($roleIds)
            ->filter(fn ($id) => $id !== null && $id !== '' && is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function roleIdsContainAdmin(array $roleIds): bool
    {
        if (empty($roleIds)) {
            return false;
        }

        return DB::table('administracion.roles')
            ->whereIn('id', $roleIds)
            ->whereIn(DB::raw("UPPER(TRIM(code))"), ['ADMIN_OC', 'ADMIN'])
            ->exists();
    }

    private function userSnapshot(User $user): array
    {
        $user->loadMissing('roles');
        $roleCodes = $user->roles->pluck('code')->map(fn ($code) => trim((string) $code))->values()->all();

        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'status' => (bool) $user->status,
            'is_admin' => $this->roleCodesContainAdmin($roleCodes),
            'id_entidad' => $user->id_entidad,
            'id_tipo_nomina' => $user->id_tipo_nomina,
            'id_clues' => $user->id_clues,
            'roles' => $roleCodes,
        ];
    }

    private function splitRoleCodes(string $roleCodes): array
    {
        if (trim($roleCodes) === '') {
            return [];
        }

        return collect(explode(',', $roleCodes))
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->values()
            ->all();
    }

    private function roleCodesContainAdmin(array $roleCodes): bool
    {
        $codes = collect($roleCodes)
            ->map(fn ($code) => mb_strtoupper(trim((string) $code), 'UTF-8'))
            ->all();

        return in_array('ADMIN_OC', $codes, true) || in_array('ADMIN', $codes, true);
    }
}
