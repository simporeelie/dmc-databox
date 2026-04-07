<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-5">

        <!-- Nom -->
        <div>
            <label for="name" class="block text-sm font-medium text-foreground mb-1.5">Nom complet</label>
            <input id="name" v-model="form.name" type="text" required autofocus autocomplete="name"
                class="w-full px-3.5 py-2.5 rounded-lg border border-input bg-background text-foreground text-sm
                       focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary
                       placeholder:text-muted-foreground transition"/>
            <p v-if="form.errors.name" class="mt-1.5 text-xs text-destructive">{{ form.errors.name }}</p>
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-foreground mb-1.5">Adresse email</label>
            <input id="email" v-model="form.email" type="email" required autocomplete="username"
                class="w-full px-3.5 py-2.5 rounded-lg border border-input bg-background text-foreground text-sm
                       focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary
                       placeholder:text-muted-foreground transition"/>
            <p v-if="form.errors.email" class="mt-1.5 text-xs text-destructive">{{ form.errors.email }}</p>
        </div>

        <!-- Email non vérifié -->
        <div v-if="mustVerifyEmail && user.email_verified_at === null"
            class="p-3 rounded-lg bg-amber-500/10 border border-amber-500/20 text-sm text-amber-700 dark:text-amber-400">
            Votre adresse email n'est pas vérifiée.
            <Link :href="route('verification.send')" method="post" as="button"
                class="underline font-medium hover:opacity-80 transition ml-1">
                Renvoyer l'email de vérification
            </Link>
        </div>

        <div v-show="status === 'verification-link-sent'"
            class="p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-sm text-emerald-700 dark:text-emerald-400">
            Un nouveau lien de vérification a été envoyé à votre adresse email.
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-3 pt-1">
            <button type="submit" :disabled="form.processing"
                class="px-5 py-2.5 bg-primary text-primary-foreground text-sm font-medium rounded-lg
                       hover:bg-primary/90 transition disabled:opacity-60 disabled:cursor-not-allowed">
                <span v-if="form.processing" class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Enregistrement...
                </span>
                <span v-else>Enregistrer</span>
            </button>

            <Transition enter-active-class="transition duration-300" enter-from-class="opacity-0 translate-x-2"
                enter-to-class="opacity-100" leave-active-class="transition duration-200" leave-to-class="opacity-0">
                <span v-if="form.recentlySuccessful" class="text-sm text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                    <i class="ki-filled ki-check-circle text-base"></i>
                    Modifications sauvegardées
                </span>
            </Transition>
        </div>
    </form>
</template>
