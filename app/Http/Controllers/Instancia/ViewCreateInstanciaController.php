<?php

namespace App\Http\Controllers\Instancia;

use App\Http\Controllers\Controller;

class ViewCreateInstanciaController extends Controller
{
    public function create()
    {
        return view('instancia.form', [
            'instancia' => null,
        ]);
    }
}
