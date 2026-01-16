<?php

namespace App\Models;

use App\Models\Auth\RoleModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * ✅ IMPORTANTE: apuntar a tu tabla real en PostgreSQL (schema.tabla)
     */
    protected $table = 'administracion.users';

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // tu tabla SI tiene created_at/updated_at

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'remember_token',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // =========================================================================
    // ROLES
    // =========================================================================

    public function roles()
    {
        return $this->belongsToMany(
            RoleModel::class,
            'administracion.user_roles',
            'user_id',
            'role_id'
        );
    }

    public function hasRole(string $code): bool
    {
        $this->loadMissing('roles');
        return $this->roles->contains(fn ($r) => $r->code === $code);
    }

    public function hasAnyRole(array $codes): bool
    {
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
