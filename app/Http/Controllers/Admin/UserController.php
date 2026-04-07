<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\UserInvitation;
use App\Models\Entity;
use App\Models\Filiale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users'    => User::with(['entity', 'filiale'])
                            ->orderBy('name')
                            ->get()
                            ->map(fn($u) => [
                                'id'            => $u->id,
                                'name'          => $u->name,
                                'email'         => $u->email,
                                'role'          => $u->role,
                                'is_active'     => $u->is_active,
                                'entity_id'     => $u->entity_id,
                                'filiale_id'    => $u->filiale_id,
                                'entity'        => $u->entity,
                                'filiale'       => $u->filiale,
                                'last_login_at' => $u->last_login_at?->toIso8601String(),
                                'login_count'   => $u->login_count,
                                'created_at'    => $u->created_at->toIso8601String(),
                            ]),
            'entities' => Entity::where('is_active', true)->get(),
            'filiales' => Filiale::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:191',
            'email'      => 'required|email|max:191|unique:users',
            'role'       => 'required|in:admin,dmc,rmc,viewer',
            'entity_id'  => 'nullable|exists:entities,id',
            'filiale_id' => 'nullable|exists:filiales,id',
        ]);

        $user = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make(Str::random(32)),
            'role'       => $request->role,
            'entity_id'  => $request->entity_id,
            'filiale_id' => $request->filiale_id,
            'is_active'  => true,
        ]);

        // Générer un token de réinitialisation et envoyer l'invitation
        $token = Password::broker()->createToken($user);
        Mail::to($user->email)->send(new UserInvitation($user, $token));

        return back()->with('success', "Compte créé. Un email d'invitation a été envoyé à {$user->email}.");
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'       => 'required|string|max:191',
            'email'      => 'required|email|max:191|unique:users,email,' . $user->id,
            'role'       => 'required|in:admin,dmc,rmc,viewer',
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

    public function resendInvitation(User $user)
    {
        $token = Password::broker()->createToken($user);
        Mail::to($user->email)->send(new UserInvitation($user, $token));

        return back()->with('success', "Invitation renvoyée à {$user->email}.");
    }
}
