<?php

namespace App\Http\Controllers\Action;

use App\Http\Controllers\Controller;
use App\Models\Action\CollectionActionModel;
use App\Models\Action\EntityActionModel;
use App\Models\Pac\Collection\CollectionFinalidadModel;
use App\Models\Pac\Collection\CollectionStatusModel;
use App\Models\Pac\Collection\CollectionTematicaModel;

class ViewEditActionController extends Controller
{
    public function edit(string $id)
    {
        if (!preg_match('/^\d+$/', $id)) {
            abort(404);
        }

        $accion = EntityActionModel::findOrFail($id);

        $statusList    = (new CollectionStatusModel)->listCollection();
        $tematicaList  = (new CollectionTematicaModel)->listCollection();
        $finalidadList = (new CollectionFinalidadModel)->listCollection();

        $collectionAction = new CollectionActionModel;

        $tipoCapList   = $collectionAction->listTipoCapacitacion();
        $modalidadList = $collectionAction->listModalidades();
        $ramoList      = $collectionAction->listRamos();
        $urList        = $collectionAction->listURs();
        $instList      = $collectionAction->listInstituciones();

        return view('action.form', compact(
            'accion',
            'statusList',
            'tematicaList',
            'finalidadList',
            'tipoCapList',
            'modalidadList',
            'ramoList',
            'urList',
            'instList',
        ));
    }
}