<?php

namespace App\Http\Controllers\Action;

use App\Http\Controllers\Controller;

class ViewCreateActionController extends Controller
{
    /**
     * El metodo retorna la vista para login
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return view('action.form');
    }
}
