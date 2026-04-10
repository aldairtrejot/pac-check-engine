<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class ViewLoginController extends Controller
{
    /**
     * El metodo retorna la vista para login
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function viewLogin()
    {
        return view('auth.login');
    }
}