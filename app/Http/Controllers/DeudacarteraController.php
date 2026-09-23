<?php

namespace App\Http\Controllers;

use App\Models\Deudacartera;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Requests\DeudacarteraRequest;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class DeudacarteraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $deudacarteras = Deudacartera::paginate();

        return view('deudacartera.index', compact('deudacarteras'))
            ->with('i', ($request->input('page', 1) - 1) * $deudacarteras->perPage());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $deudacartera = new Deudacartera();

        return view('deudacartera.create', compact('deudacartera'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DeudacarteraRequest $request): RedirectResponse
    {
        Deudacartera::create($request->validated());

        return Redirect::route('deudacarteras.index')
            ->with('success', 'Deudacartera created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id): View
    {
        $deudacartera = Deudacartera::find($id);

        return view('deudacartera.show', compact('deudacartera'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): View
    {
        $deudacartera = Deudacartera::find($id);

        return view('deudacartera.edit', compact('deudacartera'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DeudacarteraRequest $request, Deudacartera $deudacartera): RedirectResponse
    {
        $deudacartera->update($request->validated());

        return Redirect::route('deudacarteras.index')
            ->with('success', 'Deudacartera updated successfully');
    }

    public function destroy($id): RedirectResponse
    {
        Deudacartera::find($id)->delete();

        return Redirect::route('deudacarteras.index')
            ->with('success', 'Deudacartera deleted successfully');
    }
}
