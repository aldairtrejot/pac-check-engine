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
        /*
        |--------------------------------------------------------------------------
        | Normalización de campos manuales
        |--------------------------------------------------------------------------
        | Todo lo que el usuario escriba manualmente se convierte a mayúsculas
        | antes de validar y guardar.
        */
        $request->merge([
            'ur'                => $this->toUpper($request->input('ur')),
            'institucion'       => $this->toUpper($request->input('institucion')),
            'estatus'           => $this->toUpper($request->input('estatus')),
            'nombre_accion'     => $this->toUpper($request->input('nombre_accion')),
            'tematica'          => $this->toUpper($request->input('tematica')),
            'tipo_capacitacion' => $this->toUpper($request->input('tipo_capacitacion')),
            'modalidad'         => $this->toUpper($request->input('modalidad')),
            'finalidad'         => $this->toUpper($request->input('finalidad')),
        ]);

        $validated = $request->validate([
            'id_accion'         => 'nullable|integer',
            'ramo'              => 'required|integer',
            'ur'                => 'required|string|max:255',
            'institucion'       => 'required|string|max:255',
            'estatus'           => 'required|string|max:255',
            'nombre_accion'     => 'required|string|max:255',
            'tematica'          => 'nullable|string|max:255',
            'duracion_hrs'      => 'nullable|numeric',
            'tipo_capacitacion' => 'nullable|string|max:255',
            'modalidad'         => 'nullable|string|max:255',

            // Finalidad se guarda como texto/descripción
            'finalidad'         => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($validated) {

            /*
            |--------------------------------------------------------------------------
            | Editar acción
            |--------------------------------------------------------------------------
            */
            if (!empty($validated['id_accion'])) {
                $accion = EntityActionModel::findOrFail($validated['id_accion']);

                // Evita actualizar accidentalmente el identificador
                unset($validated['id_accion']);

                $accion->update($validated);

                $message = 'La acción se actualizó correctamente.';

            /*
            |--------------------------------------------------------------------------
            | Crear acción
            |--------------------------------------------------------------------------
            */
            } else {
                // Siguiente id_accion: MAX + 1, excluyendo cursos especiales
                $maxId = EntityActionModel::query()
                    ->whereNotIn('id_accion', [
                        1000001, 1000002, 1000003, 1000004, 1000005,
                        1000006, 1000007, 1000008, 1000009, 1000010,
                    ])
                    ->lockForUpdate()
                    ->max('id_accion');

                $nextId = ($maxId ?? 0) + 1;

                $validated['id_accion'] = $nextId;

                // Si no viene finalidad, se asigna default
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

    /**
     * Convierte texto a mayúsculas respetando acentos y caracteres UTF-8.
     */
    private function toUpper($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? mb_strtoupper($value, 'UTF-8') : null;
    }
}