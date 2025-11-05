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
        // 🔐 Restringir acceso por correo
        $this->ensurePacAdmin();

        // Catálogo de finalidades (para el combo en el formulario de acciones)
        $collectionFinalidad = new CollectionFinalidadModel();
        $finalidadList = $collectionFinalidad->listCollection();

        return view('action.action', compact('finalidadList'));
    }
}
