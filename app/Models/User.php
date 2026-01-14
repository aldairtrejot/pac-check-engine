<?php

namespace App\Models;

use App\Models\Auth\RoleModel; // ✅ IMPORTANTE
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

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
