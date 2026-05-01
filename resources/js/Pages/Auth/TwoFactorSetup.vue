<script setup>
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PasswordStrength from '@/Components/PasswordStrength.vue';

const props = defineProps({
    qrUrl:  { type: String, required: true },
    secret: { type: String, required: true },
});

const form = useForm({ code: '' });
const submit = () => form.post(route('two-factor.enable'));
</script>

<template>
    <AppLayout>
        <Head title="Activer le 2FA — DMC DataBox" />

        <div class="max-w-lg mx-auto py-10 px-4">
            <div class="bg-background rounded-2xl border border-border shadow-sm p-8">

                <h1 class="text-xl font-bold text-foreground mb-1">Activer la double authentification</h1>
                <p class="text-sm text-muted-foreground mb-6">
                    Scannez le QR code avec Google Authenticator, puis entrez le code généré pour confirmer.
                </p>

                <!-- Étapes -->
                <div class="space-y-5 mb-8">
                    <div class="flex gap-3">
                        <span class="size-7 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">1</span>
                        <div>
                            <p class="text-sm font-medium text-foreground">Installez Google Authenticator</p>
                            <p class="text-xs text-muted-foreground">Disponible sur l'App Store et Google Play</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <span class="size-7 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">2</span>
                        <div>
                            <p class="text-sm font-medium text-foreground">Scannez ce QR code</p>
                            <div class="mt-3 flex justify-center">
                                <img :src="qrUrl" alt="QR Code 2FA" class="w-44 h-44 rounded-lg border border-border"/>
                            </div>
                            <p class="text-xs text-muted-foreground mt-2 text-center">
                                Ou entrez manuellement : <code class="font-mono font-bold text-foreground">{{ secret }}</code>
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <span class="size-7 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">3</span>
                        <div class="w-full">
                            <p class="text-sm font-medium text-foreground mb-2">Entrez le code de confirmation</p>
                            <form @submit.prevent="submit" class="flex gap-2">
                                <input v-model="form.code" type="text" inputmode="numeric" maxlength="6"
                                    placeholder="000000" autocomplete="off"
                                    class="flex-1 px-3 py-2 text-center text-lg tracking-widest rounded-lg border border-input
                                           bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary transition"/>
                                <button type="submit" :disabled="form.processing || form.code.length !== 6"
                                    class="px-5 py-2 bg-primary text-white font-medium rounded-lg hover:bg-primary/90
                                           transition disabled:opacity-50 text-sm">
                                    Activer
                                </button>
                            </form>
                            <p v-if="form.errors.code" class="mt-1.5 text-xs text-destructive">{{ form.errors.code }}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
