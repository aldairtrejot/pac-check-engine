<?php

namespace App\Http\Controllers\Constancias;

use App\Http\Controllers\Controller;

class ViewConstanciasController extends Controller
{
    public function view()
    {
        return view('constancias.table');
    }
}