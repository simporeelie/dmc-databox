<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Connexion — DMC DataBox" />

    <div class="min-h-screen w-full flex">

        <!-- ── Panneau gauche — branding ── -->
        <div class="hidden lg:flex lg:w-1/2 flex-col relative overflow-hidden bg-black">

            <!-- Image de fond -->
            <div class="absolute inset-0 bg-cover bg-center bg-no-repeat"
                style="background-image: url('/images/login-bg.jpg')"></div>

            <!-- Overlay léger pour lisibilité du texte -->
            <div class="absolute inset-0"
                style="background: linear-gradient(to bottom, rgba(0,20,60,0.50) 0%, rgba(0,30,80,0.20) 50%, rgba(0,20,60,0.55) 100%)"></div>

            <!-- Contenu : flex colonne, haut / centre / bas -->
            <div class="relative flex flex-col justify-between h-full py-10 px-10 text-center">

                <!-- Logo en haut -->
                <div class="flex flex-col items-center gap-3">
                    <img src="/images/logo-icon.svg" alt="Coris Holding" class="h-16 w-16 drop-shadow-2xl" />
                    <div>
                        <p class="text-white text-lg font-bold tracking-tight" style="text-shadow: 0 2px 12px rgba(0,0,0,0.8)">Coris Holding</p>
                        <p class="text-blue-100 text-xs font-semibold tracking-widest uppercase mt-0.5" style="text-shadow: 0 1px 8px rgba(0,0,0,0.9)">DMC DataBox</p>
                    </div>
                </div>

                <!-- Titre au centre -->
                <div class="space-y-4">
                    <h1 class="text-5xl font-extrabold text-white leading-tight tracking-tight"
                        style="text-shadow: 0 2px 20px rgba(0,0,0,0.9), 0 4px 40px rgba(0,0,0,0.6)">
                        Gestion<br>
                        <span style="color:#93c5fd; text-shadow: 0 2px 20px rgba(0,0,0,0.9)">documentaire</span><br>
                        centralisée
                    </h1>
                    <p class="text-white/90 text-sm leading-relaxed max-w-xs mx-auto"
                        style="text-shadow: 0 1px 10px rgba(0,0,0,0.9)">
                        Archivez, organisez et accédez à tous les documents de la DMC Coris Holding.
                    </p>

                    <!-- Feature pills -->
                    <div class="flex justify-center gap-3 pt-2">
                        <span class="flex items-center gap-1.5 text-xs text-white font-medium px-3 py-1.5 rounded-full"
                            style="background:rgba(0,0,0,0.35); backdrop-filter:blur(8px); border:1px solid rgba(255,255,255,0.2); text-shadow:0 1px 4px rgba(0,0,0,0.8)">
                            <i class="ki-filled ki-photo text-blue-200 text-sm"></i> Graphisme
                        </span>
                        <span class="flex items-center gap-1.5 text-xs text-white font-medium px-3 py-1.5 rounded-full"
                            style="background:rgba(0,0,0,0.35); backdrop-filter:blur(8px); border:1px solid rgba(255,255,255,0.2); text-shadow:0 1px 4px rgba(0,0,0,0.8)">
                            <i class="ki-filled ki-film text-blue-200 text-sm"></i> Audiovisuel
                        </span>
                        <span class="flex items-center gap-1.5 text-xs text-white font-medium px-3 py-1.5 rounded-full"
                            style="background:rgba(0,0,0,0.35); backdrop-filter:blur(8px); border:1px solid rgba(255,255,255,0.2); text-shadow:0 1px 4px rgba(0,0,0,0.8)">
                            <i class="ki-filled ki-file-text text-blue-200 text-sm"></i> Documents
                        </span>
                    </div>
                </div>

                <!-- Copyright bas -->
                <p class="text-white/50 text-xs" style="text-shadow: 0 1px 6px rgba(0,0,0,0.8)">
                    © {{ new Date().getFullYear() }} Coris Holding — DMC DataBox v1.0
                </p>

            </div>
        </div>

        <!-- ── Panneau droit — formulaire ── -->
        <div class="w-full lg:w-1/2 flex items-center justify-center bg-background p-8 sm:p-12">
            <div class="w-full max-w-sm">

                <!-- Logo mobile -->
                <div class="lg:hidden mb-10 flex items-center justify-center">
                    <img src="/images/logo.svg" alt="Coris Holding Hub" class="h-10" />
                </div>

                <!-- Titre formulaire -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-foreground">Connexion</h2>
                    <p class="text-muted-foreground text-sm mt-1.5">
                        Entrez vos identifiants pour accéder à la plateforme
                    </p>
                </div>

                <!-- Status message -->
                <div v-if="status"
                    class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-sm">
                    <i class="ki-filled ki-check-circle text-base flex-shrink-0"></i>
                    {{ status }}
                </div>

                <!-- Formulaire -->
                <form @submit.prevent="submit" class="space-y-5">

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5" for="email">
                            Adresse email
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-muted-foreground pointer-events-none">
                                <i class="ki-filled ki-sms text-base"></i>
                            </span>
                            <input id="email" v-model="form.email" type="email" required autofocus
                                autocomplete="username" placeholder="votre@email.com"
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border bg-background text-foreground text-sm
                                       placeholder:text-muted-foreground transition outline-none
                                       focus:ring-2 focus:ring-primary/30 focus:border-primary"
                                :class="form.errors.email ? 'border-destructive' : 'border-input'" />
                        </div>
                        <p v-if="form.errors.email" class="text-destructive text-xs mt-1.5 flex items-center gap-1">
                            <i class="ki-filled ki-information-2 text-xs"></i>{{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Mot de passe -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-sm font-medium text-foreground" for="password">Mot de passe</label>
                            <a v-if="canResetPassword" :href="route('password.request')"
                                class="text-xs text-primary hover:underline">
                                Mot de passe oublié ?
                            </a>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-muted-foreground pointer-events-none">
                                <i class="ki-filled ki-lock text-base"></i>
                            </span>
                            <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'"
                                required autocomplete="current-password" placeholder="••••••••"
                                class="w-full pl-10 pr-10 py-2.5 rounded-xl border bg-background text-foreground text-sm
                                       placeholder:text-muted-foreground transition outline-none
                                       focus:ring-2 focus:ring-primary/30 focus:border-primary"
                                :class="form.errors.password ? 'border-destructive' : 'border-input'" />
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-muted-foreground hover:text-foreground transition">
                                <i :class="showPassword ? 'ki-filled ki-eye-slash' : 'ki-filled ki-eye'" class="text-base"></i>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="text-destructive text-xs mt-1.5 flex items-center gap-1">
                            <i class="ki-filled ki-information-2 text-xs"></i>{{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Se souvenir -->
                    <label class="flex items-center gap-2.5 cursor-pointer select-none">
                        <input type="checkbox" v-model="form.remember" class="kt-checkbox" />
                        <span class="text-sm text-foreground/80">Se souvenir de moi</span>
                    </label>

                    <!-- Bouton connexion -->
                    <button type="submit" :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-semibold
                               text-white transition disabled:opacity-60"
                        style="background: linear-gradient(135deg, #004991 0%, #0060c0 100%);">
                        <span v-if="form.processing" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Connexion en cours...
                        </span>
                        <span v-else class="flex items-center gap-2">
                            <i class="ki-filled ki-entrance-right text-base"></i>
                            Se connecter
                        </span>
                    </button>

                </form>

                <!-- Footer -->
                <p class="mt-10 text-center text-xs text-muted-foreground/60">
                    © {{ new Date().getFullYear() }} Coris Holding — Accès réservé
                </p>
            </div>
        </div>

    </div>
</template>
