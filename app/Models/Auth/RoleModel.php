<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Model;

class RoleModel extends Model
{
    protected $table = 'administracion.roles';

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'code','name','is_central','is_active'
    ];
}
