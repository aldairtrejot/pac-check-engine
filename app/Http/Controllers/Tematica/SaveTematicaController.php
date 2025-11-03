<?php

namespace App\Http\Controllers\Tematica;

use App\Http\Controllers\Controller;
use App\Models\Tematica\EntityTematicaModel;
use Illuminate\Http\Request;

class SaveTematicaController extends Controller
{
    public function save(Request $request)
    {
        $validated = $request->validate([
            'mode'        => 'nullable|string|in:create,edit',
            'id_tematica' => 'required|string|max:50',
            'consecutivo' => 'required|integer',
            'ramo'        => 'nullable|string|max:50',
            'ur'          => 'nullable|string|max:50',
            'tematica'    => 'required|string|max:200',
            'categorias'  => 'required|string|max:200',
            'enfoque'     => 'required|string|max:100',
        ]);

        $mode = $request->input('mode', 'create');

        if ($mode === 'edit') {
            // editar SIN cambiar la PK
            $tematica = EntityTematicaModel::findOrFail($validated['id_tematica']);

            $tematica->update([
                'consecutivo' => $validated['consecutivo'],
                'ramo'        => $validated['ramo'],
                'ur'          => $validated['ur'],
                'tematica'    => $validated['tematica'],
                'categorias'  => $validated['categorias'],
                'enfoque'     => $validated['enfoque'],
            ]);

            $message = 'La temática se actualizó correctamente.';
        } else {
            // crear (el usuario define id_tematica)
            EntityTematicaModel::create($validated);
            $message = 'La temática se creó correctamente.';
        }

        return redirect()
            ->route('tematica')
            ->with('success', $message);
    }
}
