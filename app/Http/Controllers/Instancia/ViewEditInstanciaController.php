<?php

namespace App\Http\Controllers\Instancia;

use App\Http\Controllers\Controller;
use App\Models\Instancia\EntityInstanciaModel;

class ViewEditInstanciaController extends Controller
{
    public function edit(string $id)
    {
        // Validación sencilla de formato
        if (! preg_match('/^[0-9A-Za-z]+$/', $id)) {
            abort(404);
        }

        $instancia = EntityInstanciaModel::find($id);

        if (! $instancia) {
            abort(404);
        }

        return view('instancia.form', compact('instancia'));
    }
}
