<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helpers\TemplateTableController;
use App\Models\Pac\TablePacModel;
use Illuminate\Http\Request;

class TablePacController extends Controller
{
    /**
     * The function cleans, sanitizes and validates the query information of the table to obtain
     * the SQL and send it to the client.
     *
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function table(Request $request)
    {
        $templateTableController = new TemplateTableController;   // Create instance of the Table controller/helper
        $objectModel = new TablePacModel;     // Create instance of the User model
        try {
            // Validate and sanitize pagination parameters from the request
            $data = $templateTableController->validateAndSanitizePagination($request);

            // Call the list method with validated pagination and search parameters
            $result = $objectModel->list(
                $data['limit'],
                $data['offset'],
                $data['search'],
                $data['select'],
                $request
            );

            // Return the response with paginated data
            return response()->json([
                'status' => true,
                'allRow' => $result['allRow'], // Total records
                'list' => $result['list'],     // Paginated records
                'row' => $result['row'],       // Current page row number
            ], 200);
        } catch (\Exception $e) {
            // Catch all other exceptions and return a general error message
            \Log::info($e);

            return response()->json([
                'status' => false,
                'message' => 'Error',
            ], 200); // HTTP 200 with failure status
        }
    }
}

/*
SELECT
    public.a2_acciones_empleados.id_empl_accion AS id,
    public.a2_acciones_capacitacion.nombre AS nombre,
    public.a2_acciones_capacitacion.apellido_paterno || ' ' ||
        public.a2_acciones_capacitacion.apellido_materno AS apellido,
    public.a2_acciones_empleados.curp AS curp,
    public.a1_cat_acciones.nombre_accion AS accion,
    CASE
    WHEN (
        public.a2_acciones_empleados.id_cat_estatus IS NOT NULL
        OR public.a2_acciones_empleados.fecha_ini IS NOT NULL
        OR public.a2_acciones_empleados.fecha_fin IS NOT NULL
        OR public.a2_acciones_empleados.id_trimestre IS NOT NULL
        OR public.a2_acciones_empleados.id_instancia IS NOT NULL
        OR public.a2_acciones_empleados.id_cat_tematica IS NOT NULL
    )
    THEN 'RESUELTO'
    ELSE 'SIN ATENDER'
END AS atendido
FROM public.a2_acciones_empleados
    INNER JOIN public.a1_cat_acciones
        ON public.a2_acciones_empleados.id_accion =
            public.a1_cat_acciones.id_accion
    INNER JOIN public.a2_acciones_capacitacion
        ON public.a2_acciones_empleados.id_puesto::INTEGER =
            public.a2_acciones_capacitacion.id_puesto
ORDER BY public.a2_acciones_empleados.curp ASC
*/
