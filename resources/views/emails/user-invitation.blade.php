<x-mail::message>
# Bienvenue sur DMC DataBox — Coris Holding

Bonjour **{{ $user->name }}**,

Un compte a été créé pour vous sur la plateforme **DMC DataBox** de Coris Holding.

Votre rôle : **{{ strtoupper($user->role) }}**

Pour activer votre compte et définir votre mot de passe, cliquez sur le bouton ci-dessous :

<x-mail::button :url="$resetUrl" color="primary">
Activer mon compte
</x-mail::button>

Ce lien est valable **24 heures**. Si vous n'avez pas demandé ce compte, ignorez cet email.

---

**Accès à la plateforme :** {{ url('/') }}

Merci,
**L'équipe DMC — Coris Holding**
</x-mail::message>
