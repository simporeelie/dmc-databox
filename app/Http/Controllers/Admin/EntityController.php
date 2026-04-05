<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EntityController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Entities/Index', [
            'entities' => Entity::withCount(['filiales', 'documents'])->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191|unique:entities',
        ]);

        Entity::create([
            'name'      => $request->name,
            'slug'      => Str::slug($request->name),
            'is_active' => true,
        ]);

        return back()->with('success', 'Entité créée.');
    }

    public function update(Request $request, Entity $entity)
    {
        $request->validate([
            'name'      => 'required|string|max:191|unique:entities,name,' . $entity->id,
            'is_active' => 'boolean',
        ]);

        $entity->update([
            'name'      => $request->name,
            'slug'      => Str::slug($request->name),
            'is_active' => $request->is_active,
        ]);

        return back()->with('success', 'Entité mise à jour.');
    }

    public function destroy(Entity $entity)
    {
        $entity->delete();
        return back()->with('success', 'Entité supprimée.');
    }
}
