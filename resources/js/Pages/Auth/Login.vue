<script setup>
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

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
    <Head title="DMC DataBox — Connexion" />

    <div class="min-h-screen flex" style="background: linear-gradient(135deg, #0f2a5e 0%, #1a4a9e 50%, #0f2a5e 100%);">

        <!-- Panneau gauche -->
        <div class="hidden lg:flex lg:w-1/2 flex-col items-center justify-center p-12">
            <div class="text-center">
                <div class="mb-8">
                    <svg class="w-24 h-24 mx-auto text-white opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-white mb-2">DMC DATA BOX</h1>
                <p class="text-blue-200 text-lg">Plateforme d'archivage des données de la DMC</p>
                <p class="text-blue-300 text-sm mt-2">Coris Holding</p>

                <div class="mt-12 flex justify-center gap-6 flex-wrap">
                    <div class="bg-white bg-opacity-10 rounded-lg px-4 py-2 text-white text-xs">
                        Productions graphiques
                    </div>
                    <div class="bg-white bg-opacity-10 rounded-lg px-4 py-2 text-white text-xs">
                        Productions audiovisuelles
                    </div>
                    <div class="bg-white bg-opacity-10 rounded-lg px-4 py-2 text-white text-xs">
                        Productions documentaires
                    </div>
                </div>
            </div>
        </div>

        <!-- Panneau droit — formulaire -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <div class="bg-white rounded-2xl shadow-2xl p-8">

                    <!-- Logo mobile -->
                    <div class="lg:hidden text-center mb-8">
                        <svg class="w-16 h-16 mx-auto text-blue-800 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        <h1 class="text-2xl font-bold text-blue-900">DMC DATA BOX</h1>
                    </div>

                    <h2 class="text-2xl font-bold text-gray-800 mb-1">Connexion</h2>
                    <p class="text-gray-500 text-sm mb-8">Entrez vos identifiants pour accéder à la plateforme</p>

                    <div v-if="status" class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                Adresse email
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                    </svg>
                                </span>
                                <input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    required
                                    autofocus
                                    placeholder="votre@email.com"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-gray-800"
                                    :class="{ 'border-red-400': form.errors.email }"
                                />
                            </div>
                            <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                Mot de passe
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </span>
                                <input
                                    id="password"
                                    type="password"
                                    v-model="form.password"
                                    required
                                    placeholder="••••••••"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-gray-800"
                                    :class="{ 'border-red-400': form.errors.password }"
                                />
                            </div>
                            <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="form.remember"
                                    class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                <span class="text-sm text-gray-600">Se souvenir de moi</span>
                            </label>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3 px-4 bg-blue-800 hover:bg-blue-900 text-white font-semibold rounded-xl transition duration-200 flex items-center justify-center gap-2 disabled:opacity-60"
                        >
                            <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ form.processing ? 'Connexion...' : 'Se connecter' }}
                        </button>
                    </form>
                </div>

                <!-- Logos entités -->
                <div class="mt-8 flex justify-center items-center gap-4 flex-wrap">
                    <span class="text-white text-xs opacity-60 bg-white bg-opacity-10 rounded px-3 py-1">Coris Holding</span>
                    <span class="text-white text-xs opacity-60 bg-white bg-opacity-10 rounded px-3 py-1">Coris Bank International</span>
                    <span class="text-white text-xs opacity-60 bg-white bg-opacity-10 rounded px-3 py-1">CBI Baraka</span>
                    <span class="text-white text-xs opacity-60 bg-white bg-opacity-10 rounded px-3 py-1">Coris Money</span>
                </div>
            </div>
        </div>
    </div>
</template>
