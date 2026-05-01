<script setup>
import { computed } from 'vue';

const props = defineProps({
    password: { type: String, default: '' },
});

const rules = computed(() => [
    { label: '12 caractères minimum',          ok: props.password.length >= 12 },
    { label: 'Une lettre majuscule (A-Z)',      ok: /[A-Z]/.test(props.password) },
    { label: 'Une lettre minuscule (a-z)',      ok: /[a-z]/.test(props.password) },
    { label: 'Un chiffre (0-9)',                ok: /[0-9]/.test(props.password) },
    { label: 'Un symbole (!@#$%^&*...)',        ok: /[^A-Za-z0-9]/.test(props.password) },
]);

const score = computed(() => rules.value.filter(r => r.ok).length);

const strength = computed(() => {
    if (props.password.length === 0) return null;
    if (score.value <= 2) return { label: 'Faible',  color: 'bg-red-500',    text: 'text-red-500' };
    if (score.value === 3) return { label: 'Moyen',  color: 'bg-orange-400', text: 'text-orange-400' };
    if (score.value === 4) return { label: 'Fort',   color: 'bg-yellow-400', text: 'text-yellow-500' };
    return                        { label: 'Excellent', color: 'bg-emerald-500', text: 'text-emerald-600' };
});
</script>

<template>
    <div v-if="password.length > 0" class="mt-2 space-y-2">
        <!-- Barre de progression -->
        <div class="flex gap-1">
            <div v-for="i in 5" :key="i"
                class="h-1 flex-1 rounded-full transition-all duration-300"
                :class="i <= score ? strength.color : 'bg-muted'">
            </div>
        </div>

        <!-- Label force -->
        <p class="text-xs font-medium" :class="strength.text">
            Force : {{ strength.label }}
        </p>

        <!-- Critères -->
        <ul class="space-y-0.5">
            <li v-for="rule in rules" :key="rule.label"
                class="flex items-center gap-1.5 text-xs transition-colors"
                :class="rule.ok ? 'text-emerald-600' : 'text-muted-foreground'">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path v-if="rule.ok" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                {{ rule.label }}
            </li>
        </ul>
    </div>
</template>
