<script setup>
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PasswordStrength from '@/Components/PasswordStrength.vue';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submit = () => form.put(route('password.expired.update'));
</script>

<template>
    <GuestLayout>
        <Head title="Renouvellement du mot de passe — DMC DataBox" />

        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center size-14 rounded-2xl bg-amber-500/10 mb-4">
                <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-foreground">Mot de passe expiré</h1>
            <p class="text-sm text-muted-foreground mt-1">
                Votre mot de passe a plus de 90 jours. Vous devez le renouveler pour continuer.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">Mot de passe actuel</label>
                <input v-model="form.current_password" type="password" autocomplete="current-password"
                    class="w-full px-3.5 py-2.5 rounded-lg border border-input bg-background text-foreground
                           text-sm focus:outline-none focus:ring-2 focus:ring-primary transition"/>
                <p v-if="form.errors.current_password" class="mt-1 text-xs text-destructive">{{ form.errors.current_password }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">Nouveau mot de passe</label>
                <input v-model="form.password" type="password" autocomplete="new-password"
                    class="w-full px-3.5 py-2.5 rounded-lg border border-input bg-background text-foreground
                           text-sm focus:outline-none focus:ring-2 focus:ring-primary transition"/>
                <PasswordStrength :password="form.password" />
                <p v-if="form.errors.password" class="mt-1 text-xs text-destructive">{{ form.errors.password }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">Confirmer le nouveau mot de passe</label>
                <input v-model="form.password_confirmation" type="password" autocomplete="new-password"
                    class="w-full px-3.5 py-2.5 rounded-lg border border-input bg-background text-foreground
                           text-sm focus:outline-none focus:ring-2 focus:ring-primary transition"/>
                <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-destructive">{{ form.errors.password_confirmation }}</p>
            </div>

            <button type="submit" :disabled="form.processing"
                class="w-full py-2.5 bg-primary text-white font-medium rounded-lg hover:bg-primary/90
                       transition disabled:opacity-50 mt-2">
                {{ form.processing ? 'Enregistrement...' : 'Mettre à jour mon mot de passe' }}
            </button>
        </form>
    </GuestLayout>
</template>
