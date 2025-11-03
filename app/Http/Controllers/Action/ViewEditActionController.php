<?php

namespace App\Http\Controllers\Action;

use App\Http\Controllers\Controller;
use App\Models\Action\EntityActionModel;
use App\Models\Pac\Collection\CollectionStatusModel;
use App\Models\Pac\Collection\CollectionTematicaModel;
use App\Models\Action\CollectionActionModel;

class ViewEditActionController extends Controller
{
    public function edit(string $id)
    {
        if (!preg_match('/^\d+$/', $id)) {
            abort(404);
        }

        $accion = EntityActionModel::find($id);
        if (!$accion) {
            abort(404);
        }

        $statusList   = (new CollectionStatusModel)->listCollection();
        $tematicaList = (new CollectionTematicaModel)->listCollection();

        $collectionAction = new CollectionActionModel;
        $tipoCapList      = $collectionAction->listTipoCapacitacion();
        $modalidadList    = $collectionAction->listModalidades();
        $ramoList         = $collectionAction->listRamos();
        $urList           = $collectionAction->listURs();
        $instList         = $collectionAction->listInstituciones();

        return view('action.form', compact(
            'accion',
            'statusList',
            'tematicaList',
            'tipoCapList',
            'modalidadList',
            'ramoList',
            'urList',
            'instList',
        ));
    }
}
