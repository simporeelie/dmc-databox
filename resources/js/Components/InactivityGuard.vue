<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

// Déconnexion après 15 minutes d'inactivité
const TIMEOUT_MS      = 15 * 60 * 1000;
// Avertissement 2 minutes avant
const WARNING_BEFORE  = 2 * 60 * 1000;

const showWarning  = ref(false);
const secondsLeft  = ref(120);

let timeoutHandle  = null;
let warningHandle  = null;
let countdownHandle = null;

const resetTimer = () => {
    showWarning.value = false;
    clearTimeout(timeoutHandle);
    clearTimeout(warningHandle);
    clearInterval(countdownHandle);

    warningHandle = setTimeout(() => {
        showWarning.value = true;
        secondsLeft.value = Math.floor(WARNING_BEFORE / 1000);
        countdownHandle = setInterval(() => {
            secondsLeft.value--;
            if (secondsLeft.value <= 0) clearInterval(countdownHandle);
        }, 1000);
    }, TIMEOUT_MS - WARNING_BEFORE);

    timeoutHandle = setTimeout(() => {
        router.post(route('logout'));
    }, TIMEOUT_MS);
};

const stayConnected = () => resetTimer();

const EVENTS = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];

onMounted(() => {
    resetTimer();
    EVENTS.forEach(e => window.addEventListener(e, resetTimer, { passive: true }));
});

onUnmounted(() => {
    clearTimeout(timeoutHandle);
    clearTimeout(warningHandle);
    clearInterval(countdownHandle);
    EVENTS.forEach(e => window.removeEventListener(e, resetTimer));
});
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition duration-150" leave-to-class="opacity-0">
            <div v-if="showWarning"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm">
                <div class="bg-background rounded-2xl shadow-2xl border border-border p-8 max-w-sm w-full mx-4 text-center">
                    <div class="inline-flex items-center justify-center size-14 rounded-2xl bg-amber-500/10 mb-4">
                        <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-foreground mb-2">Session sur le point d'expirer</h2>
                    <p class="text-sm text-muted-foreground mb-1">
                        Vous allez être déconnecté dans
                    </p>
                    <p class="text-4xl font-bold text-amber-500 mb-4">{{ secondsLeft }}s</p>
                    <p class="text-xs text-muted-foreground mb-6">
                        Aucune activité détectée depuis 13 minutes.
                    </p>
                    <button @click="stayConnected"
                        class="w-full py-2.5 bg-primary text-white font-medium rounded-lg hover:bg-primary/90 transition text-sm">
                        Rester connecté
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
