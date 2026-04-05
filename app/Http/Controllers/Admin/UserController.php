<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\Filiale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users'    => User::with(['entity', 'filiale'])->orderBy('name')->get(),
            'entities' => Entity::where('is_active', true)->get(),
            'filiales' => Filiale::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:191',
            'email'      => 'required|email|max:191|unique:users',
            'password'   => 'required|string|min:8',
            'role'       => 'required|in:admin,dmc,rmc,visiteur',
            'entity_id'  => 'nullable|exists:entities,id',
            'filiale_id' => 'nullable|exists:filiales,id',
        ]);

        User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'role'       => $request->role,
            'entity_id'  => $request->entity_id,
            'filiale_id' => $request->filiale_id,
            'is_active'  => true,
        ]);

        return back()->with('success', 'Utilisateur créé avec succès.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'       => 'required|string|max:191',
            'email'      => 'required|email|max:191|unique:users,email,' . $user->id,
            'role'       => 'required|in:admin,dmc,rmc,visiteur',
            'entity_id'  => 'nullable|exists:entities,id',
            'filiale_id' => 'nullable|exists:filiales,id',
            'is_active'  => 'boolean',
        ]);

        $user->update($request->only(['name', 'email', 'role', 'entity_id', 'filiale_id', 'is_active']));

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return back()->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return back()->with('success', 'Utilisateur supprimé.');
    }
}
