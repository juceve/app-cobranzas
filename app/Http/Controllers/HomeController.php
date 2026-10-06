<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function index()
    {
        $usuario = auth()->user();

        if ($usuario->can('cobrador.access') && ! $usuario->can('lotes.view_all')) {
            return redirect()->route('cobrador.lotes');
        }

        return view('home');
    }
}
