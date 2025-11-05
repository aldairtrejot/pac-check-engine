<?php

namespace App\Http\Controllers\Tematica;

use App\Http\Controllers\Controller;

class ViewTematicaController extends Controller
{
    public function view()
    {
        // 🔐 Restringir acceso por correo
        $this->ensurePacAdmin();

        return view('tematica.tematica');
    }
}
