<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Model;

class RoleModel extends Model
{
    protected $table = 'administracion.roles';

    protected $fillable = [
        'code','name','is_central','is_active'
    ];
}
