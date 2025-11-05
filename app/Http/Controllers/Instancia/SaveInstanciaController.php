<?php

namespace App\Http\Controllers\Instancia;

use App\Http\Controllers\Controller;
use App\Models\Instancia\EntityInstanciaModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaveInstanciaController extends Controller
{
    public function save(Request $request)
    {
        $mode = $request->input('mode', 'create');

        if ($mode === 'edit') {
            // EDITAR: NO cambiamos id_instancia ni consecutivo
            $validated = $request->validate([
                'id_instancia' => 'required|string|max:10',
                'ramo'         => 'nullable|string|max:50',
                'ur'           => 'nullable|string|max:50',
                'instancia'    => 'required|string|max:150',
                'anio'         => 'required|integer',
                'estatus'      => 'required|string|max:20',
            ]);

            $row = EntityInstanciaModel::findOrFail($validated['id_instancia']);

            $ramo = $validated['ramo'] !== null && $validated['ramo'] !== ''
                ? $validated['ramo']
                : '0';

            $ur = $validated['ur'] !== null && $validated['ur'] !== ''
                ? $validated['ur']
                : '0';

            $row->update([
                'ramo'      => $ramo,
                'ur'        => $ur,
                'instancia' => $validated['instancia'],
                'anio'      => $validated['anio'],
                'estatus'   => $validated['estatus'],
            ]);

            $message = 'La instancia se actualizó correctamente.';
        } else {
            // CREAR: generamos consecutivo + id_instancia
            $validated = $request->validate([
                'ramo'      => 'nullable|string|max:50',
                'ur'        => 'nullable|string|max:50',
                'instancia' => 'required|string|max:150',
                'anio'      => 'required|integer',
                'estatus'   => 'required|string|max:20',
            ]);

            $ramo = $validated['ramo'] !== null && $validated['ramo'] !== ''
                ? $validated['ramo']
                : '0';

            $ur = $validated['ur'] !== null && $validated['ur'] !== ''
                ? $validated['ur']
                : '0';

            DB::beginTransaction();

            try {
                // Obtenemos la última instancia para calcular el consecutivo
                $last = EntityInstanciaModel::orderBy('consecutivo', 'desc')
                    ->lockForUpdate()
                    ->first();

                $consecutivo = ($last->consecutivo ?? 0) + 1;

                // Construir id_instancia:
                // - Si hay ramo y ur válidos => 47AYO152
                // - Si no, sólo consecutivo con ceros => 00152
                if ($ramo !== '0' && $ur !== '0') {
                    $idInstancia = $ramo . $ur . $consecutivo;
                } else {
                    $idInstancia = str_pad((string) $consecutivo, 5, '0', STR_PAD_LEFT);
                }

                EntityInstanciaModel::create([
                    'id_instancia' => $idInstancia,
                    'ramo'         => $ramo,
                    'ur'           => $ur,
                    'consecutivo'  => $consecutivo,
                    'instancia'    => $validated['instancia'],
                    'anio'         => $validated['anio'],
                    'estatus'      => $validated['estatus'],
                ]);

                DB::commit();
                $message = 'La instancia se creó correctamente.';
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        }

        return redirect()
            ->route('instancia')
            ->with('success', $message);
    }
}
