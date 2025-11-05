<?php

namespace App\Http\Controllers\Action;

use App\Http\Controllers\Controller;
use App\Models\Action\EntityActionModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaveActionController extends Controller
{
    public function save(Request $request)
    {
        $validated = $request->validate([
            'id_accion'     => 'nullable|integer',
            'ramo'          => 'required|integer',
            'ur'            => 'required|string|max:255',
            'institucion'   => 'required|string|max:255',
            'estatus'       => 'required|string|max:255',
            'nombre_accion' => 'required|string|max:255',
            'tematica'      => 'nullable|string|max:255',
            'duracion_hrs'  => 'nullable|numeric',
            'tipo_capacitacion' => 'nullable|string|max:255',
            'modalidad'         => 'nullable|string|max:255',
            // finalidad se guarda como TEXTO (descripción)
            'finalidad'         => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($validated) {

            // 👉 EDITAR
            if (!empty($validated['id_accion'])) {
                $accion = EntityActionModel::findOrFail($validated['id_accion']);
                $accion->update($validated);
                $message = 'La acción se actualizó correctamente.';

            // 👉 CREAR
            } else {
                // siguiente id_accion: MAX + 1
                $nextId = (EntityActionModel::max('id_accion') ?? 0) + 1;
                $validated['id_accion'] = $nextId;

                // Si no viene finalidad, default F6-SENSIBILIZACION
                if (empty($validated['finalidad'])) {
                    $validated['finalidad'] = 'F6-SENSIBILIZACION';
                }

                EntityActionModel::create($validated);
                $message = 'La acción se creó correctamente.';
            }

            return redirect()
                ->route('action')
                ->with('success', $message);
        });
    }
}
