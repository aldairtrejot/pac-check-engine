<?php

namespace App\Models;

use App\Models\Auth\RoleModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'administracion.users';

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'remember_token',
        'is_admin',
        'status',
        'id_entidad',
        'id_tipo_nomina',
        'id_clues',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'status' => 'boolean',
        'id_entidad' => 'integer',
        'id_tipo_nomina' => 'integer',
        'id_clues' => 'integer',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            RoleModel::class,
            'administracion.user_roles',
            'user_id',
            'role_id'
        );
    }

    private function rolesPivotExists(): bool
    {
        try {
            $row = DB::selectOne("SELECT to_regclass('administracion.user_roles') AS t");
            return ! empty($row?->t);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function normalizeRoleCode(?string $code): string
    {
        return mb_strtoupper(trim((string) $code), 'UTF-8');
    }

    public function hasRole(string $code): bool
    {
        if (! $this->rolesPivotExists()) {
            return false;
        }

        $wanted = $this->normalizeRoleCode($code);

        $this->loadMissing('roles');

        return $this->roles->contains(function ($role) use ($wanted) {
            return $this->normalizeRoleCode($role->code ?? '') === $wanted;
        });
    }

    public function hasAnyRole(array $codes): bool
    {
        if (! $this->rolesPivotExists()) {
            return false;
        }

        $wanted = collect($codes)
            ->map(fn ($code) => $this->normalizeRoleCode($code))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->loadMissing('roles');

        return $this->roles->contains(function ($role) use ($wanted) {
            return in_array($this->normalizeRoleCode($role->code ?? ''), $wanted, true);
        });
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole([
            'ADMIN_OC',
            'ADMIN',
        ]);
    }

    public function isCentral(): bool
    {
        return $this->hasAnyRole([
            'ADMIN_OC',
            'SUPERVISOR_OC',
        ]);
    }

    public function isOperative(): bool
    {
        return $this->hasAnyRole([
            'REVISOR_EST',
            'SUPERVISOR_EST',
        ]);
    }
}
