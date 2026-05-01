<x-mail::message>
# Alerte de Sécurité — DMC DataBox

Une activité suspecte a été détectée sur la plateforme.

**Type d'alerte :** {{ $type }}

**Détail :** {{ $detail }}

**Adresse IP :** {{ $ipAddress }}

**Date et heure :** {{ $occurredAt }}

---

Si cette activité est anormale, vérifiez immédiatement les accès utilisateurs dans le panneau d'administration.

<x-mail::button :url="config('app.url') . '/admin/users'" color="red">
Vérifier les utilisateurs
</x-mail::button>

Cordialement,<br>
**DMC DataBox — Système de sécurité**
</x-mail::message>
