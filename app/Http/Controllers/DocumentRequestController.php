<?php

namespace App\Http\Controllers;

use App\Mail\DocumentRequestNotification;
use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class DocumentRequestController extends Controller
{
    // Soumettre une demande (tous les utilisateurs connectés)
    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category'    => 'nullable|string|max:100',
            'period'      => 'nullable|string|max:100',
        ]);

        $docRequest = DocumentRequest::create([
            'user_id'     => Auth::id(),
            'title'       => $request->title,
            'description' => $request->description,
            'category'    => $request->category,
            'period'      => $request->period,
            'status'      => 'pending',
        ]);

        // Envoyer email aux admins et DMC
        $recipients = User::whereIn('role', ['admin', 'dmc'])
            ->where('is_active', true)
            ->get();

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)
                ->send(new DocumentRequestNotification($docRequest, Auth::user()));
        }

        return back()->with('success', 'Votre demande a été envoyée. L\'équipe DMC vous contactera.');
    }

    // Liste admin des demandes
    public function index()
    {
        $requests = DocumentRequest::with(['user', 'handler'])
            ->orderByRaw("FIELD(status, 'pending', 'in_progress', 'fulfilled', 'closed')")
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($r) => [
                'id'          => $r->id,
                'title'       => $r->title,
                'description' => $r->description,
                'category'    => $r->category,
                'period'      => $r->period,
                'status'      => $r->status,
                'status_label'=> $r->statusLabel(),
                'response'    => $r->response,
                'user'        => $r->user ? ['name' => $r->user->name, 'email' => $r->user->email, 'role' => $r->user->role] : null,
                'handler'     => $r->handler ? ['name' => $r->handler->name] : null,
                'handled_at'  => $r->handled_at?->toIso8601String(),
                'created_at'  => $r->created_at->toIso8601String(),
            ]);

        return Inertia::render('Admin/DocumentRequests/Index', [
            'requests' => $requests,
            'counts'   => [
                'pending'     => DocumentRequest::where('status', 'pending')->count(),
                'in_progress' => DocumentRequest::where('status', 'in_progress')->count(),
                'fulfilled'   => DocumentRequest::where('status', 'fulfilled')->count(),
                'closed'      => DocumentRequest::where('status', 'closed')->count(),
            ],
        ]);
    }

    // Mettre à jour le statut / répondre
    public function update(Request $request, DocumentRequest $documentRequest)
    {
        $request->validate([
            'status'   => 'required|in:pending,in_progress,fulfilled,closed',
            'response' => 'nullable|string|max:1000',
        ]);

        $documentRequest->update([
            'status'     => $request->status,
            'response'   => $request->response,
            'handled_by' => Auth::id(),
            'handled_at' => in_array($request->status, ['fulfilled', 'closed']) ? now() : $documentRequest->handled_at,
        ]);

        return back()->with('success', 'Demande mise à jour.');
    }
}
