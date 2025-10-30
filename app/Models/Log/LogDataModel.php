<?php

namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;

class LogDataModel extends Model
{
    // table
    protected $table = 'log.log_info';

    // primary field
    protected $primaryKey = 'id_log_info';

    // incremental status
    public $incrementing = true;

    // primary field type
    protected $keyType = 'int';

    // create and update fields
    public $timestamps = false;

    // fields
    protected $fillable = [
        'observaciones',
        'id_usuario',
        'id_cat_estatus',
        'fecha_ini',
        'fecha_fin',
        'id_instancia',
        'id_cat_tematica',
        'creado_en',
        'id_empl_accion',
    ];
}
