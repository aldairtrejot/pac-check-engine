<?php

namespace App\Http\Controllers\Action;

use App\Http\Controllers\Controller;

class ViewActioController extends Controller
{
    /**
     * El metodo retorna la vista para login
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function view()
    {
        return view('action.action');
    }
}
