<?php

namespace App\Http\Controllers\Action;

use App\Http\Controllers\Controller;
use App\Models\Pac\Collection\CollectionStatusModel;
use App\Models\Pac\Collection\CollectionTematicaModel;
use App\Models\Action\CollectionActionModel;

class ViewCreateActionController extends Controller
{
    public function create()
    {
        $statusList   = (new CollectionStatusModel)->listCollection();
        $tematicaList = (new CollectionTematicaModel)->listCollection();

        $collectionAction = new CollectionActionModel;
        $tipoCapList      = $collectionAction->listTipoCapacitacion();
        $modalidadList    = $collectionAction->listModalidades();
        $ramoList         = $collectionAction->listRamos();
        $urList           = $collectionAction->listURs();
        $instList         = $collectionAction->listInstituciones();

        return view('action.form', compact(
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
