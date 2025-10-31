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

