<script setup>
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const form = useForm({ code: '' });

const submit = () => form.post(route('two-factor.verify'));
</script>

<template>
    <GuestLayout>
        <Head title="Vérification — DMC DataBox" />

        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center size-14 rounded-2xl bg-primary/10 mb-4">
                <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-foreground">Vérification en deux étapes</h1>
            <p class="text-sm text-muted-foreground mt-1">
                Ouvrez votre application Google Authenticator et entrez le code à 6 chiffres.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">Code d'authentification</label>
                <input v-model="form.code" type="text" inputmode="numeric" maxlength="6"
                    placeholder="000000" autocomplete="one-time-code" autofocus
                    class="w-full px-4 py-3 text-center text-2xl tracking-widest rounded-lg border border-input
                           bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary transition"/>
                <p v-if="form.errors.code" class="mt-1.5 text-xs text-destructive">{{ form.errors.code }}</p>
            </div>

            <button type="submit" :disabled="form.processing || form.code.length !== 6"
                class="w-full py-2.5 bg-primary text-white font-medium rounded-lg hover:bg-primary/90
                       transition disabled:opacity-50 disabled:cursor-not-allowed">
                <span v-if="form.processing">Vérification...</span>
                <span v-else>Confirmer</span>
            </button>
        </form>
    </GuestLayout>
</template>
