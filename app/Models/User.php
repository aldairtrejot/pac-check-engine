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
        'status' => 'boolean',
        'id_entidad' => 'integer',
        'id_tipo_nomina' => 'integer',
        'id_clues' => 'integer',
    ];

    // =========================================================================
    // ROLES
    // =========================================================================

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
            return !empty($row?->t);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function hasRole(string $code): bool
    {
        if (! $this->rolesPivotExists()) {
            return false;
        }

        $this->loadMissing('roles');
        return $this->roles->contains(fn ($r) => $r->code === $code);
    }

    public function hasAnyRole(array $codes): bool
    {
        if (! $this->rolesPivotExists()) {
            return false;
        }

        $this->loadMissing('roles');
        return $this->roles->whereIn('code', $codes)->isNotEmpty();
    }

    public function isCentral(): bool
    {
        return $this->hasAnyRole(['admin_oc', 'supervisor_oc']);
    }

    public function isOperative(): bool
    {
        return $this->hasAnyRole(['revisor_est', 'supervisor_est']);
    }
}