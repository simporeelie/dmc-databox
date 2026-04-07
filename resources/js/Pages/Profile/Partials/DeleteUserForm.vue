<script setup>
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);
const showPassword = ref(false);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <div class="space-y-4">
        <p class="text-sm text-muted-foreground leading-relaxed">
            Une fois votre compte supprimé, toutes vos données seront définitivement effacées.
            Cette action est irréversible.
        </p>

        <button type="button" @click="confirmUserDeletion"
            class="px-4 py-2.5 bg-destructive text-destructive-foreground text-sm font-medium rounded-lg
                   hover:bg-destructive/90 transition flex items-center gap-2">
            <i class="ki-filled ki-trash text-base"></i>
            Supprimer mon compte
        </button>
    </div>

    <!-- Modal de confirmation -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="confirmingUserDeletion"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="closeModal">

                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

                <!-- Dialog -->
                <Transition
                    enter-active-class="transition duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95">
                    <div v-if="confirmingUserDeletion"
                        class="relative bg-background rounded-2xl border border-border shadow-xl w-full max-w-md p-6 space-y-5">

                        <!-- Header -->
                        <div class="flex items-start gap-4">
                            <div class="size-10 rounded-xl bg-destructive/10 flex items-center justify-center shrink-0">
                                <i class="ki-filled ki-trash text-destructive text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-foreground">Supprimer le compte</h3>
                                <p class="text-sm text-muted-foreground mt-1">
                                    Confirmez votre mot de passe pour supprimer définitivement votre compte et toutes vos données.
                                </p>
                            </div>
                        </div>

                        <!-- Mot de passe -->
                        <div>
                            <label for="delete_password" class="block text-sm font-medium text-foreground mb-1.5">
                                Mot de passe
                            </label>
                            <div class="relative">
                                <input id="delete_password" ref="passwordInput" v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="Entrez votre mot de passe"
                                    autocomplete="current-password"
                                    @keyup.enter="deleteUser"
                                    class="w-full px-3.5 py-2.5 pr-10 rounded-lg border border-input bg-background text-foreground text-sm
                                           placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-destructive
                                           focus:border-destructive transition"/>
                                <button type="button" @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition">
                                    <i class="ki-filled text-base" :class="showPassword ? 'ki-eye-slash' : 'ki-eye'"></i>
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="mt-1.5 text-xs text-destructive">{{ form.errors.password }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-1">
                            <button type="button" @click="closeModal"
                                class="px-4 py-2.5 text-sm font-medium text-foreground bg-secondary rounded-lg
                                       hover:bg-secondary/80 transition">
                                Annuler
                            </button>
                            <button type="button" @click="deleteUser" :disabled="form.processing"
                                class="px-4 py-2.5 bg-destructive text-destructive-foreground text-sm font-medium rounded-lg
                                       hover:bg-destructive/90 transition disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2">
                                <span v-if="form.processing" class="flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    Suppression...
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <i class="ki-filled ki-trash text-base"></i>
                                    Confirmer la suppression
                                </span>
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
