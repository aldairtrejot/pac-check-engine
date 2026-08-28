<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Models\Pac\CollectionPacModel;
use App\Support\PacVisibility;
use Illuminate\Support\Facades\Log;

class MainPacController extends Controller
{
    public function mainPac()
    {
        try {
            $collectionPacModel = new CollectionPacModel;
            $listOptionsAcction = $collectionPacModel->listCollection();
            $listSelectAcction = [];

            return response()->json([
                'status' => true,
                'listOptionsAcction' => $listOptionsAcction,
                'listSelectAcction' => $listSelectAcction,
                'is_admin' => PacVisibility::isAdminGlobal(auth()->user()),
            ], 200);
        } catch (\Throwable $th) {
            Log::error('PAC mainPac ERROR', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => false,
                'message' => __('default.error_message'),
            ], 200);
        }
    }
}
