<?php

namespace App\Http\Controllers\Tematica;

use App\Http\Controllers\Controller;

class ViewCreateTematicaController extends Controller
{
    public function create()
    {
        // alta nueva, sin datos previos
        return view('tematica.form');
    }
}
