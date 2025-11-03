<?php

namespace App\Http\Controllers\Tematica;

use App\Http\Controllers\Controller;
use App\Models\Tematica\EntityTematicaModel;

class ViewEditTematicaController extends Controller
{
    public function edit(string $id)
    {
        try {
            if ($id === '') {
                abort(404);
            }

            $tematica = EntityTematicaModel::find($id);

            if (!$tematica) {
                abort(404);
            }

            return view('tematica.form', compact('tematica'));
        } catch (\Exception $e) {
            abort(404);
        }
    }
}
