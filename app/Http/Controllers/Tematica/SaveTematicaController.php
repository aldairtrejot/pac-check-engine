<?php

namespace App\Http\Controllers\Tematica;

use App\Http\Controllers\Controller;
use App\Models\Tematica\EntityTematicaModel;
use App\Support\UserActionLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // 👈 IMPORTANTE

class SaveTematicaController extends Controller
{
    public function save(Request $request)
    {
        $mode = $request->input('mode', 'create');
        $audit = null;

        if ($mode === 'edit') {
            // 🟢 EDITAR: NO se cambia id_tematica ni consecutivo
            $validated = $request->validate([
                'id_tematica' => 'required|string|max:50',
                'ramo'        => 'nullable|string|max:50',
                'ur'          => 'nullable|string|max:50',
                'tematica'    => 'required|string|max:200',
                'categorias'  => 'required|string|max:200',
                'enfoque'     => 'required|string|max:100',
            ]);

            $tematica = EntityTematicaModel::findOrFail($validated['id_tematica']);
            $old = $tematica->toArray();

            $ramo = $validated['ramo'] !== null && $validated['ramo'] !== ''
                ? $validated['ramo']
                : '0';

            $ur = $validated['ur'] !== null && $validated['ur'] !== ''
                ? $validated['ur']
                : '0';

            $tematica->update([
                'ramo'        => $ramo,
                'ur'          => $ur,
                'tematica'    => $validated['tematica'],
                'categorias'  => $validated['categorias'],
                'enfoque'     => $validated['enfoque'],
            ]);

            $tematica = $tematica->fresh();

            $message = 'La temática se actualizó correctamente.';
            $audit = [
                'accion' => 'ACTUALIZAR_TEMATICA',
                'descripcion' => 'Modificación de temática.',
                'id_referencia' => $tematica->id_tematica,
                'old_values' => $old,
                'new_values' => $tematica->toArray(),
            ];
        } else {
            // 🟢 CREAR: consecutivo + id_tematica se generan de forma segura

            $validated = $request->validate([
                'ramo'        => 'nullable|string|max:50',
                'ur'          => 'nullable|string|max:50',
                'tematica'    => 'required|string|max:200',
                'categorias'  => 'required|string|max:200',
                'enfoque'     => 'required|string|max:100',
            ]);

            $ramo = $validated['ramo'] !== null && $validated['ramo'] !== ''
                ? $validated['ramo']
                : '0';

            $ur = $validated['ur'] !== null && $validated['ur'] !== ''
                ? $validated['ur']
                : '0';

            // 🔐 Transacción para evitar problemas con muchos usuarios a la vez
            DB::beginTransaction();

            try {
                // Bloquea la última fila por consecutivo
                $last = EntityTematicaModel::orderBy('consecutivo', 'desc')
                    ->lockForUpdate()
                    ->first();

                $consecutivo = ($last->consecutivo ?? 0) + 1;

                // id_tematica = consecutivo + ramo + ur
                $idTematica = (string) $consecutivo . $ramo . $ur;

                $tematica = EntityTematicaModel::create([
                    'id_tematica' => $idTematica,
                    'consecutivo' => $consecutivo,
                    'ramo'        => $ramo,
                    'ur'          => $ur,
                    'tematica'    => $validated['tematica'],
                    'categorias'  => $validated['categorias'],
                    'enfoque'     => $validated['enfoque'],
                ]);

                $audit = [
                    'accion' => 'CREAR_TEMATICA',
                    'descripcion' => 'Alta de temática.',
                    'id_referencia' => $tematica->id_tematica,
                    'old_values' => null,
                    'new_values' => $tematica->toArray(),
                ];

                DB::commit();

                $message = 'La temática se creó correctamente.';
            } catch (\Throwable $th) {
                DB::rollBack();
                // puedes loguear el error o devolver un mensaje amigable
                throw $th; // o manejarlo como tú prefieras
            }
        }

        if ($audit) {
            UserActionLogger::write(
                idUsuario: auth()->id() ? (int) auth()->id() : null,
                modulo: 'TEMATICAS',
                accion: $audit['accion'],
                descripcion: $audit['descripcion'],
                idReferencia: $audit['id_referencia'],
                oldValues: $audit['old_values'],
                newValues: $audit['new_values']
            );
        }

        return redirect()
            ->route('tematica')
            ->with('success', $message);
    }
}
