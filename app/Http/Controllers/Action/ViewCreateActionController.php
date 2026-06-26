<?php

namespace App\Http\Controllers\Action;

use App\Http\Controllers\Controller;
use App\Models\Action\CollectionActionModel;
use App\Models\Pac\Collection\CollectionFinalidadModel;
use App\Models\Pac\Collection\CollectionStatusModel;
use App\Models\Pac\Collection\CollectionTematicaModel;

class ViewCreateActionController extends Controller
{
    public function create()
    {
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