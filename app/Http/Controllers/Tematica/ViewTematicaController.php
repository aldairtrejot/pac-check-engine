<?php

namespace App\Http\Controllers\Tematica;

use App\Http\Controllers\Controller;

class ViewTematicaController extends Controller
{
    public function view()
    {
        return view('tematica.tematica');
    }
}
