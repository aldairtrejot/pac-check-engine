<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;

class ViewPacController extends Controller
{
    /**
     * El metodo retorna la vista para login
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function viewPac()
    {
        return view('pac.pac');
    }
}
