<x-mail::message>
# Nouvelle demande de document

Bonjour,

**{{ $requester->name }}** ({{ $requester->role }}) a soumis une demande de document sur DMC DataBox.

<x-mail::panel>
**Titre :** {{ $request->title }}

@if($request->description)
**Description :** {{ $request->description }}
@endif

@if($request->category)
**Catégorie :** {{ $request->category }}
@endif

@if($request->period)
**Période :** {{ $request->period }}
@endif

**Date de la demande :** {{ $request->created_at->format('d/m/Y à H:i') }}
</x-mail::panel>

Veuillez traiter cette demande dans les meilleurs délais.

<x-mail::button :url="$adminUrl" color="primary">
Voir les demandes
</x-mail::button>

Merci,
**L'équipe DMC — Coris Holding**
</x-mail::message>
