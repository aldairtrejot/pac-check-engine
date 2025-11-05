<?php

namespace App\Http\Controllers\Action;

use App\Http\Controllers\Controller;
use App\Models\Pac\Collection\CollectionFinalidadModel;

class ViewActioController extends Controller
{
    /**
     * Retorna la vista de acciones (tabla).
     */
    public function view()
    {
        // Catálogo de finalidades (no es estrictamente necesario aquí)
        $collectionFinalidad = new CollectionFinalidadModel();
        $finalidadList = $collectionFinalidad->listCollection();

        return view('action.action', compact('finalidadList'));
    }
}
