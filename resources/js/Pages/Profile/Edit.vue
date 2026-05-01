<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';

const props = defineProps({
    mustVerifyEmail: { type: Boolean },
    status:          { type: String },
    hasTwoFactor:    { type: Boolean, default: false },
});

const user = usePage().props.auth.user;

const roleLabel = { admin: 'Administrateur', dmc: 'DMC', rmc: 'RMC', visiteur: 'Visiteur' };

const disableForm = useForm({ password: '' });
const disable2FA = () => disableForm.post(route('two-factor.disable'), { preserveScroll: true });
</script>

<template>
    <Head title="Mon profil — DMC DataBox" />
    <AppLayout>
        <div class="kt-container-fixed py-8">

            <!-- En-tête profil -->
            <div class="flex items-center gap-5 mb-8">
                <div class="size-16 rounded-2xl bg-primary flex items-center justify-center shrink-0 shadow-lg">
                    <span class="text-2xl font-bold text-primary-foreground uppercase">{{ user.name.charAt(0) }}</span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-foreground">{{ user.name }}</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-sm text-muted-foreground">{{ user.email }}</span>
                        <span class="px-2 py-0.5 bg-primary/10 text-primary text-xs font-semibold rounded-full uppercase">
                            {{ roleLabel[user.role] || user.role }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Colonne principale -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Informations du profil -->
                    <div class="bg-background rounded-2xl border border-border shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-border flex items-center gap-3">
                            <div class="size-8 rounded-lg bg-primary/10 flex items-center justify-center">
                                <i class="ki-filled ki-user-square text-primary text-base"></i>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-foreground">Informations personnelles</h2>
                                <p class="text-xs text-muted-foreground">Nom et adresse email</p>
                            </div>
                        </div>
                        <div class="p-6">
                            <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                        </div>
                    </div>

                    <!-- Mot de passe -->
                    <div class="bg-background rounded-2xl border border-border shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-border flex items-center gap-3">
                            <div class="size-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                                <i class="ki-filled ki-lock text-amber-600 dark:text-amber-400 text-base"></i>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-foreground">Mot de passe</h2>
                                <p class="text-xs text-muted-foreground">Utilisez un mot de passe long et aléatoire</p>
                            </div>
                        </div>
                        <div class="p-6">
                            <UpdatePasswordForm />
                        </div>
                    </div>

                </div>

                <!-- Colonne droite -->
                <div class="space-y-6">

                    <!-- Carte récapitulatif -->
                    <div class="bg-background rounded-2xl border border-border shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-border">
                            <h2 class="text-sm font-semibold text-foreground">Récapitulatif</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Rôle</span>
                                <span class="font-medium text-foreground uppercase text-xs px-2 py-1 bg-primary/10 text-primary rounded-full">{{ roleLabel[user.role] || user.role }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Nom</span>
                                <span class="font-medium text-foreground truncate max-w-[150px]">{{ user.name }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Email</span>
                                <span class="font-medium text-foreground truncate max-w-[150px]">{{ user.email }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Carte Double authentification -->
                    <div class="bg-background rounded-2xl border border-border shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-border flex items-center gap-3">
                            <div class="size-8 rounded-lg flex items-center justify-center"
                                :class="hasTwoFactor ? 'bg-emerald-500/10' : 'bg-muted'">
                                <i class="ki-filled ki-shield-tick text-base"
                                    :class="hasTwoFactor ? 'text-emerald-500' : 'text-muted-foreground'"></i>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-foreground">Double authentification</h2>
                                <p class="text-xs text-muted-foreground">Sécurisez votre compte avec un code TOTP</p>
                            </div>
                        </div>
                        <div class="p-6">
                            <!-- 2FA activée -->
                            <div v-if="hasTwoFactor" class="space-y-4">
                                <div class="flex items-center gap-2 text-sm text-emerald-600 dark:text-emerald-400 font-medium">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    2FA activée
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Votre compte est protégé par un code d'authentification à usage unique.
                                </p>
                                <button @click="disable2FA"
                                    :disabled="disableForm.processing"
                                    class="w-full py-2 text-sm font-medium rounded-lg border border-red-300 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition disabled:opacity-50">
                                    Désactiver la 2FA
                                </button>
                            </div>

                            <!-- 2FA désactivée -->
                            <div v-else class="space-y-4">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground font-medium">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    2FA non activée
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Activez la double authentification pour renforcer la sécurité de votre compte.
                                </p>
                                <a :href="route('two-factor.setup')"
                                    class="block w-full py-2 text-center text-sm font-medium rounded-lg bg-primary text-white hover:bg-primary/90 transition">
                                    Activer la 2FA
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
