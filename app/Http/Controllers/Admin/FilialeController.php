<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\Filiale;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FilialeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Filiales/Index', [
            'filiales' => Filiale::with('entity')->withCount('documents')->get(),
            'entities' => Entity::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:191',
            'entity_id' => 'required|exists:entities,id',
            'country'   => 'nullable|string|max:191',
        ]);

        Filiale::create([
            'name'      => $request->name,
            'slug'      => Str::slug($request->name),
            'entity_id' => $request->entity_id,
            'country'   => $request->country,
            'is_active' => true,
        ]);

        return back()->with('success', 'Filiale créée.');
    }

    public function update(Request $request, Filiale $filiale)
    {
        $request->validate([
            'name'      => 'required|string|max:191',
            'entity_id' => 'required|exists:entities,id',
            'country'   => 'nullable|string|max:191',
            'is_active' => 'boolean',
        ]);

        $filiale->update([
            'name'      => $request->name,
            'slug'      => Str::slug($request->name),
            'entity_id' => $request->entity_id,
            'country'   => $request->country,
            'is_active' => $request->is_active,
        ]);

        return back()->with('success', 'Filiale mise à jour.');
    }

    public function destroy(Filiale $filiale)
    {
        $filiale->delete();
        return back()->with('success', 'Filiale supprimée.');
    }
}
