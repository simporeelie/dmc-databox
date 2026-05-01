<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});

const user = usePage().props.auth.user;

const roleLabel = { admin: 'Administrateur', dmc: 'DMC', rmc: 'RMC', viewer: 'Lecteur' };
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


                </div>
            </div>
        </div>
    </AppLayout>
</template>
