<?php

namespace App\Http\Controllers\Instancia;

use App\Http\Controllers\Controller;

class ViewInstanciaController extends Controller
{
    public function view()
    {
        // 🔐 Restringir acceso por correo
        $this->ensurePacAdmin();

        return view('instancia.instancia');
    }
}
