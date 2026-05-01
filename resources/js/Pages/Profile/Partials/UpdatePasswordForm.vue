<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PasswordStrength from '@/Components/PasswordStrength.vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);
const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <form @submit.prevent="updatePassword" class="space-y-5">

        <!-- Mot de passe actuel -->
        <div>
            <label for="current_password" class="block text-sm font-medium text-foreground mb-1.5">Mot de passe actuel</label>
            <div class="relative">
                <input id="current_password" ref="currentPasswordInput" v-model="form.current_password"
                    :type="showCurrent ? 'text' : 'password'" autocomplete="current-password"
                    class="w-full px-3.5 py-2.5 pr-10 rounded-lg border border-input bg-background text-foreground text-sm
                           focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition"/>
                <button type="button" @click="showCurrent = !showCurrent"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition">
                    <i class="ki-filled text-base" :class="showCurrent ? 'ki-eye-slash' : 'ki-eye'"></i>
                </button>
            </div>
            <p v-if="form.errors.current_password" class="mt-1.5 text-xs text-destructive">{{ form.errors.current_password }}</p>
        </div>

        <!-- Nouveau mot de passe -->
        <div>
            <label for="password" class="block text-sm font-medium text-foreground mb-1.5">Nouveau mot de passe</label>
            <div class="relative">
                <input id="password" ref="passwordInput" v-model="form.password"
                    :type="showNew ? 'text' : 'password'" autocomplete="new-password"
                    class="w-full px-3.5 py-2.5 pr-10 rounded-lg border border-input bg-background text-foreground text-sm
                           focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition"/>
                <button type="button" @click="showNew = !showNew"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition">
                    <i class="ki-filled text-base" :class="showNew ? 'ki-eye-slash' : 'ki-eye'"></i>
                </button>
            </div>
            <PasswordStrength :password="form.password" />
            <p v-if="form.errors.password" class="mt-1.5 text-xs text-destructive">{{ form.errors.password }}</p>
        </div>

        <!-- Confirmation -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-foreground mb-1.5">Confirmer le mot de passe</label>
            <div class="relative">
                <input id="password_confirmation" v-model="form.password_confirmation"
                    :type="showConfirm ? 'text' : 'password'" autocomplete="new-password"
                    class="w-full px-3.5 py-2.5 pr-10 rounded-lg border border-input bg-background text-foreground text-sm
                           focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition"/>
                <button type="button" @click="showConfirm = !showConfirm"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition">
                    <i class="ki-filled text-base" :class="showConfirm ? 'ki-eye-slash' : 'ki-eye'"></i>
                </button>
            </div>
            <p v-if="form.errors.password_confirmation" class="mt-1.5 text-xs text-destructive">{{ form.errors.password_confirmation }}</p>
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
                <span v-else>Mettre à jour</span>
            </button>

            <Transition enter-active-class="transition duration-300" enter-from-class="opacity-0 translate-x-2"
                enter-to-class="opacity-100" leave-active-class="transition duration-200" leave-to-class="opacity-0">
                <span v-if="form.recentlySuccessful" class="text-sm text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                    <i class="ki-filled ki-check-circle text-base"></i>
                    Mot de passe mis à jour
                </span>
            </Transition>
        </div>
    </form>
</template>
