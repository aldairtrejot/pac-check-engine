<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\UserActionLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AdminUsersController extends Controller
{
    private const MAX_LIMIT = 50;
    private static ?bool $catCluesBiAvailable = null;
    private static array $tableExistsCache = [];

    public function index()
    {
        return view('admin.users.index');
    }

    public function options()
    {
        try {
            return response()->json([
                'status' => true,
                'roles' => $this->rolesOptions(),
                'entidades' => $this->entidadesOptions(),
                'tipos_nomina' => $this->tiposNominaOptions(),
                'clues' => $this->cluesOptions(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al cargar opciones de usuarios', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'No se pudieron cargar los catalogos de usuarios.',
                'roles' => [],
                'entidades' => [],
                'tipos_nomina' => [],
                'clues' => [],
            ], 500);
        }
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
            ->leftJoin('administracion.cat_clues as cc', 'cc.id_clues', '=', 'u.id_clues');

        if ($this->catCluesBiAvailable()) {
            $q->leftJoin('public.cat_clues_bi as cbi_id', function ($join) {
                $join->on(
                    'u.id_clues',
                    '=',
                    DB::raw("
                        CASE
                            WHEN BTRIM(COALESCE(cbi_id.idcat, '')) ~ '^[0-9]+$'
                            THEN BTRIM(cbi_id.idcat)::BIGINT
                            ELSE NULL
                        END
                    ")
                );
            });
        }

        $q->select([
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
            ]);

        if ($this->catCluesBiAvailable()) {
            $q->addSelect(DB::raw("
                COALESCE(
                    NULLIF(BTRIM(cc.clues), ''),
                    NULLIF(BTRIM(cbi_id.clave_clues), ''),
                    ''
                ) AS clues_codigo
            "));
        } else {
            $q->addSelect(DB::raw("COALESCE(cc.clues, '') AS clues_codigo"));
        }

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('u.name', 'ILIKE', "%{$search}%")
                    ->orWhere('u.email', 'ILIKE', "%{$search}%")
                    ->orWhere('rr.roles', 'ILIKE', "%{$search}%")
                    ->orWhere('ce.nombre', 'ILIKE', "%{$search}%")
                    ->orWhere('ctn.codigo', 'ILIKE', "%{$search}%")
                    ->orWhere('ctn.nombre', 'ILIKE', "%{$search}%")
                    ->orWhere('cc.clues', 'ILIKE', "%{$search}%");

                if ($this->catCluesBiAvailable()) {
                    $w->orWhere('cbi_id.clave_clues', 'ILIKE', "%{$search}%")
                        ->orWhere('cbi_id.nombre_comercial', 'ILIKE', "%{$search}%")
                        ->orWhere('cbi_id.zona_pago', 'ILIKE', "%{$search}%");
                }
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

        try {
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
        } catch (\Throwable $e) {
            Log::error('Error al cargar tabla de usuarios', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'No se pudo cargar la tabla de usuarios.',
                'list' => [],
                'allRow' => 0,
                'row' => 0,
            ], 500);
        }
    }

    public function save(Request $request)
    {
        try {
            $data = $this->validateUserPayload($request, null, passwordRequired: true);
            $roleIds = $this->normalizeRoleIds($data['role_ids'] ?? []);
            $this->assertRoleIdsExist($roleIds);

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

            $this->syncUserRoles((int) $user->id, $roleIds);

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
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error al crear usuario', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'No se pudo crear el usuario. Revisa los datos e intenta nuevamente.',
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
        $id = (int) $request->input('id');
        $user = User::with('roles')->findOrFail($id);
        $old = $this->userSnapshot($user);

            $data = $this->validateUserPayload($request, $user->id, passwordRequired: false);
            $roleIds = $this->normalizeRoleIds($data['role_ids'] ?? []);
            $this->assertRoleIdsExist($roleIds);

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
            $this->syncUserRoles((int) $user->id, $roleIds);
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
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error al actualizar usuario', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'No se pudo actualizar el usuario. Revisa los datos e intenta nuevamente.',
            ], 500);
        }
    }

    public function toggleStatus(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer', $this->existingUserRule()],
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
            'id' => ['required', 'integer', $this->existingUserRule()],
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
                $this->uniqueEmailRule($userId),
            ],
            'password' => $passwordRules,
            'status' => ['required', 'in:0,1'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['required', 'integer', 'min:1', 'max:32767', $this->existingRoleRule()],
            'id_entidad' => ['nullable', 'integer', $this->existingCatalogIdRule('administracion.cat_entidad', 'id_entidad', 'La entidad seleccionada no existe.')],
            'id_tipo_nomina' => ['nullable', 'integer', $this->existingCatalogIdRule('administracion.cat_tipo_nomina', 'id_tipo_nomina', 'El tipo de nomina seleccionado no existe.')],
            'id_clues' => ['nullable', 'integer', $this->existingCluesRule()],
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
            if ($this->catCluesBiAvailable()) {
                $hasCatClues = $this->tableExists('administracion', 'cat_clues');
                $query = DB::table('public.cat_clues_bi as cbi');

                if ($hasCatClues) {
                    $query->leftJoin('administracion.cat_clues as cc', function ($join) {
                        $join->on(
                            DB::raw('UPPER(BTRIM(cc.clues))'),
                            '=',
                            DB::raw('UPPER(BTRIM(cbi.clave_clues))')
                        )->where('cc.activo', true);
                    });
                }

                $idExpr = $hasCatClues
                    ? 'COALESCE(MAX(cc.id_clues), BTRIM(cbi.idcat)::BIGINT)'
                    : 'BTRIM(cbi.idcat)::BIGINT';

                $source = $query
                    ->whereNotNull('idcat')
                    ->whereRaw("BTRIM(COALESCE(idcat, '')) ~ '^[0-9]+$'")
                    ->whereNotNull('clave_clues')
                    ->whereRaw("BTRIM(COALESCE(clave_clues, '')) <> ''")
                    ->selectRaw("{$idExpr} as id")
                    ->selectRaw("UPPER(BTRIM(cbi.clave_clues)) as clues")
                    ->selectRaw("
                        TRIM(CONCAT_WS(
                            ' - ',
                            NULLIF(UPPER(BTRIM(cbi.clave_clues)), ''),
                            NULLIF(UPPER(BTRIM(COALESCE(cbi.nombre_comercial, cbi.nombre_comercial_equivalencia, cbi.clues_completa, ''))), '')
                        )) as descripcion
                    ")
                    ->selectRaw("UPPER(BTRIM(COALESCE(cbi.zona_pago, ''))) as entidad")
                    ->groupByRaw("
                        BTRIM(cbi.idcat)::BIGINT,
                        UPPER(BTRIM(cbi.clave_clues)),
                        TRIM(CONCAT_WS(
                            ' - ',
                            NULLIF(UPPER(BTRIM(cbi.clave_clues)), ''),
                            NULLIF(UPPER(BTRIM(COALESCE(cbi.nombre_comercial, cbi.nombre_comercial_equivalencia, cbi.clues_completa, ''))), '')
                        )),
                        UPPER(BTRIM(COALESCE(cbi.zona_pago, '')))
                    ");

                return DB::query()
                    ->fromSub($source, 'clues_bi')
                    ->selectRaw('id')
                    ->selectRaw('MIN(clues) as clues')
                    ->selectRaw('MIN(descripcion) as descripcion')
                    ->selectRaw('MIN(entidad) as entidad')
                    ->groupBy('id')
                    ->orderBy('clues')
                    ->limit(5000)
                    ->get()
                    ->map(fn ($row) => [
                        'id' => (int) $row->id,
                        'clues' => (string) $row->clues,
                        'descripcion' => trim((string) $row->descripcion) ?: (string) $row->clues,
                        'entidad' => (string) $row->entidad,
                    ])
                    ->all();
            }

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

    private function syncUserRoles(int $userId, array $roleIds): void
    {
        DB::table('administracion.user_roles')
            ->where('user_id', $userId)
            ->delete();

        $rows = collect($roleIds)
            ->unique()
            ->map(fn ($roleId) => [
                'user_id' => $userId,
                'role_id' => (int) $roleId,
            ])
            ->values()
            ->all();

        if (! empty($rows)) {
            DB::table('administracion.user_roles')->insert($rows);
        }
    }

    private function assertRoleIdsExist(array $roleIds): void
    {
        $roleIds = collect($roleIds)
            ->filter(fn ($roleId) => is_numeric($roleId))
            ->map(fn ($roleId) => (int) $roleId)
            ->unique()
            ->values()
            ->all();

        if (empty($roleIds) || collect($roleIds)->contains(fn ($roleId) => $roleId < 1 || $roleId > 32767)) {
            throw ValidationException::withMessages([
                'role_ids' => 'Selecciona roles validos.',
            ]);
        }

        $existing = DB::table('administracion.roles')
            ->whereIn('id', $roleIds)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($roleId) => (int) $roleId)
            ->all();

        if (count($existing) !== count($roleIds)) {
            throw ValidationException::withMessages([
                'role_ids' => 'Uno o mas roles seleccionados no existen o no estan activos.',
            ]);
        }
    }

    private function uniqueEmailRule(?int $ignoreUserId): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) use ($ignoreUserId): void {
            $email = mb_strtolower(trim((string) $value), 'UTF-8');

            if ($email === '') {
                return;
            }

            $exists = DB::table('administracion.users')
                ->whereRaw('LOWER(BTRIM(email)) = ?', [$email])
                ->when($ignoreUserId, fn ($q) => $q->where('id', '<>', $ignoreUserId))
                ->exists();

            if ($exists) {
                $fail('El correo ya se encuentra registrado.');
            }
        };
    }

    private function existingRoleRule(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail): void {
            if ($value === null || $value === '' || ! is_numeric($value)) {
                return;
            }

            $roleId = (int) $value;

            if ($roleId < 1 || $roleId > 32767) {
                $fail('El rol seleccionado no existe o no esta activo.');
                return;
            }

            $exists = DB::table('administracion.roles')
                ->where('id', $roleId)
                ->where('is_active', true)
                ->exists();

            if (! $exists) {
                $fail('El rol seleccionado no existe o no esta activo.');
            }
        };
    }

    private function existingUserRule(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail): void {
            if ($value === null || $value === '' || ! is_numeric($value)) {
                return;
            }

            if (! DB::table('administracion.users')->where('id', (int) $value)->exists()) {
                $fail('El usuario seleccionado no existe.');
            }
        };
    }

    private function existingCatalogIdRule(string $table, string $column, string $message): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) use ($table, $column, $message): void {
            if ($value === null || $value === '' || ! is_numeric($value)) {
                return;
            }

            if (! DB::table($table)->where($column, (int) $value)->exists()) {
                $fail($message);
            }
        };
    }

    private function existingCluesRule(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail): void {
            if ($value === null || $value === '' || ! is_numeric($value)) {
                return;
            }

            if (! $this->cluesIdExists((int) $value)) {
                $fail('La CLUES seleccionada no existe en el catalogo.');
            }
        };
    }

    private function cluesIdExists(int $idClues): bool
    {
        if ($idClues <= 0) {
            return false;
        }

        if ($this->tableExists('administracion', 'cat_clues')
            && DB::table('administracion.cat_clues')->where('id_clues', $idClues)->exists()) {
            return true;
        }

        if ($this->catCluesBiAvailable()) {
            return DB::table('public.cat_clues_bi')
                ->whereRaw("BTRIM(COALESCE(idcat, '')) ~ '^[0-9]+$'")
                ->whereRaw('BTRIM(idcat)::BIGINT = ?', [$idClues])
                ->exists();
        }

        return false;
    }

    private function catCluesBiAvailable(): bool
    {
        if (self::$catCluesBiAvailable !== null) {
            return self::$catCluesBiAvailable;
        }

        try {
            self::$catCluesBiAvailable = $this->tableExists('public', 'cat_clues_bi')
                && DB::table('public.cat_clues_bi')
                    ->whereNotNull('idcat')
                    ->whereRaw("BTRIM(COALESCE(idcat, '')) ~ '^[0-9]+$'")
                    ->whereNotNull('clave_clues')
                    ->whereRaw("BTRIM(COALESCE(clave_clues, '')) <> ''")
                    ->exists();
        } catch (\Throwable $e) {
            self::$catCluesBiAvailable = false;
        }

        return self::$catCluesBiAvailable;
    }

    private function tableExists(string $schema, string $table): bool
    {
        $key = "{$schema}.{$table}";

        if (! array_key_exists($key, self::$tableExistsCache)) {
            try {
                self::$tableExistsCache[$key] = DB::table('information_schema.tables')
                    ->where('table_schema', $schema)
                    ->where('table_name', $table)
                    ->exists();
            } catch (\Throwable $e) {
                self::$tableExistsCache[$key] = false;
            }
        }

        return self::$tableExistsCache[$key];
    }
}
