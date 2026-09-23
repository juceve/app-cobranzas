<?php

namespace App\Http\Controllers;

use App\Models\Cartera;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Requests\CarteraRequest;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class CarteraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $carteras = Cartera::paginate();

        return view('cartera.index', compact('carteras'))
            ->with('i', ($request->input('page', 1) - 1) * $carteras->perPage());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $cartera = new Cartera();

        return view('cartera.create', compact('cartera'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CarteraRequest $request): RedirectResponse
    {
        Cartera::create($request->validated());

        return Redirect::route('carteras.index')
            ->with('success', 'Cartera created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id): View
    {
        $cartera = Cartera::find($id);

        return view('cartera.show', compact('cartera'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): View
    {
        $cartera = Cartera::find($id);

        return view('cartera.edit', compact('cartera'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CarteraRequest $request, Cartera $cartera): RedirectResponse
    {
        $cartera->update($request->validated());

        return Redirect::route('carteras.index')
            ->with('success', 'Cartera updated successfully');
    }

    public function destroy($id): RedirectResponse
    {
        Cartera::find($id)->delete();

        return Redirect::route('carteras.index')
            ->with('success', 'Cartera deleted successfully');
    }
}
