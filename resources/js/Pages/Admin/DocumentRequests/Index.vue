<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    requests: Array,
    counts: Object,
});

const flash = computed(() => usePage().props.flash ?? {});

const activeFilter = ref('all');
const showModal = ref(false);
const selected = ref(null);

const form = useForm({
    status: '',
    response: '',
});

const openRequest = (req) => {
    selected.value = req;
    form.status   = req.status;
    form.response = req.response || '';
    showModal.value = true;
};

const submit = () => {
    form.put(route('admin.document-requests.update', selected.value.id), {
        onSuccess: () => { showModal.value = false; },
    });
};

const filteredRequests = computed(() => {
    if (activeFilter.value === 'all') return props.requests;
    return props.requests.filter(r => r.status === activeFilter.value);
});

const statusConfig = {
    pending:     { label: 'En attente',  class: 'bg-amber-500/15 text-amber-700 dark:text-amber-400', dot: 'bg-amber-400' },
    in_progress: { label: 'En cours',    class: 'bg-primary/10 text-primary',                         dot: 'bg-primary' },
    fulfilled:   { label: 'Traité',      class: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400', dot: 'bg-emerald-500' },
    closed:      { label: 'Fermé',       class: 'bg-accent/60 text-muted-foreground',                  dot: 'bg-border' },
};

const formatDate = (iso) => {
    if (!iso) return '—';
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    }).format(new Date(iso));
};
</script>

<template>
    <Head title="Demandes de documents — Admin" />
    <AppLayout>

        <!-- Flash -->
        <div v-if="flash.success"
            class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-sm">
            <i class="ki-filled ki-check-circle text-base flex-shrink-0"></i>
            {{ flash.success }}
        </div>

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Demandes de documents</h1>
                <p class="text-muted-foreground text-sm mt-1">Demandes soumises par les utilisateurs</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div v-for="(cfg, key) in statusConfig" :key="key"
                @click="activeFilter = activeFilter === key ? 'all' : key"
                class="bg-background rounded-xl border p-4 cursor-pointer transition-all"
                :class="activeFilter === key ? 'border-primary shadow-sm' : 'border-border hover:border-primary/30'">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-muted-foreground uppercase font-semibold tracking-wide">{{ cfg.label }}</p>
                    <span class="size-2 rounded-full" :class="cfg.dot"></span>
                </div>
                <p class="text-3xl font-bold text-foreground mt-2 tabular-nums">{{ counts[key] }}</p>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-background rounded-2xl border border-border overflow-hidden shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-accent/30 border-b border-border">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Demande</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden md:table-cell">Demandeur</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden lg:table-cell">Catégorie / Période</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Statut</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden lg:table-cell">Date</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="req in filteredRequests" :key="req.id" class="hover:bg-accent/20 transition-colors">

                        <!-- Titre + description -->
                        <td class="px-4 py-3 max-w-xs">
                            <p class="font-medium text-foreground truncate">{{ req.title }}</p>
                            <p v-if="req.description" class="text-xs text-muted-foreground truncate mt-0.5">{{ req.description }}</p>
                        </td>

                        <!-- Demandeur -->
                        <td class="px-4 py-3 hidden md:table-cell">
                            <div v-if="req.user">
                                <p class="font-medium text-foreground text-xs">{{ req.user.name }}</p>
                                <p class="text-muted-foreground text-xs">{{ req.user.email }}</p>
                            </div>
                        </td>

                        <!-- Catégorie / période -->
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <div class="flex flex-col gap-0.5">
                                <span v-if="req.category" class="text-xs text-foreground">{{ req.category }}</span>
                                <span v-if="req.period" class="text-xs text-muted-foreground">{{ req.period }}</span>
                                <span v-if="!req.category && !req.period" class="text-muted-foreground text-xs">—</span>
                            </div>
                        </td>

                        <!-- Statut -->
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold"
                                :class="statusConfig[req.status]?.class">
                                <span class="size-1.5 rounded-full" :class="statusConfig[req.status]?.dot"></span>
                                {{ statusConfig[req.status]?.label }}
                            </span>
                        </td>

                        <!-- Date -->
                        <td class="px-4 py-3 hidden lg:table-cell text-xs text-muted-foreground">
                            {{ formatDate(req.created_at) }}
                        </td>

                        <!-- Action -->
                        <td class="px-4 py-3 text-right">
                            <button @click="openRequest(req)"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium border border-border text-foreground hover:bg-accent/60 transition">
                                Traiter
                            </button>
                        </td>
                    </tr>

                    <tr v-if="filteredRequests.length === 0">
                        <td colspan="6" class="px-4 py-16 text-center">
                            <i class="ki-filled ki-document text-4xl text-muted-foreground/30 block mb-3"></i>
                            <p class="text-muted-foreground text-sm">Aucune demande pour le moment</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal traiter -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition duration-150"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    @click.self="showModal = false">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

                    <div v-if="selected"
                        class="relative bg-background rounded-2xl shadow-2xl w-full max-w-lg border border-border">

                        <!-- Header -->
                        <div class="flex items-start justify-between px-6 py-4 border-b border-border gap-4">
                            <div>
                                <h3 class="font-semibold text-foreground">{{ selected.title }}</h3>
                                <p v-if="selected.user" class="text-xs text-muted-foreground mt-0.5">
                                    Demandé par <strong>{{ selected.user.name }}</strong> le {{ formatDate(selected.created_at) }}
                                </p>
                            </div>
                            <button @click="showModal = false"
                                class="size-8 rounded-lg flex items-center justify-center text-muted-foreground hover:bg-accent/60 transition shrink-0">
                                <i class="ki-filled ki-cross text-base"></i>
                            </button>
                        </div>

                        <div class="p-6 space-y-4">
                            <!-- Détails demande -->
                            <div v-if="selected.description || selected.category || selected.period"
                                class="p-3 rounded-lg bg-accent/40 space-y-1.5 text-sm">
                                <p v-if="selected.description" class="text-foreground">{{ selected.description }}</p>
                                <div class="flex gap-4 text-xs text-muted-foreground">
                                    <span v-if="selected.category"><i class="ki-filled ki-category mr-1"></i>{{ selected.category }}</span>
                                    <span v-if="selected.period"><i class="ki-filled ki-calendar mr-1"></i>{{ selected.period }}</span>
                                </div>
                            </div>

                            <!-- Statut -->
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1.5">Statut</label>
                                <select v-model="form.status"
                                    class="w-full border border-input rounded-lg px-3.5 py-2.5 text-sm bg-background text-foreground
                                           focus:ring-2 focus:ring-primary focus:border-primary outline-none transition">
                                    <option value="pending">En attente</option>
                                    <option value="in_progress">En cours de traitement</option>
                                    <option value="fulfilled">Traité — Document disponible</option>
                                    <option value="closed">Fermé</option>
                                </select>
                            </div>

                            <!-- Réponse -->
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1.5">
                                    Réponse / commentaire <span class="text-muted-foreground font-normal">(optionnel)</span>
                                </label>
                                <textarea v-model="form.response" rows="3" placeholder="Ex: Le document a été ajouté dans la catégorie Graphisme..."
                                    class="w-full border border-input rounded-lg px-3.5 py-2.5 text-sm bg-background text-foreground
                                           focus:ring-2 focus:ring-primary focus:border-primary outline-none transition resize-none
                                           placeholder:text-muted-foreground"></textarea>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-3 pt-1">
                                <button @click="submit" :disabled="form.processing"
                                    class="flex-1 py-2.5 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90
                                           transition text-sm font-medium disabled:opacity-60">
                                    {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                                </button>
                                <button type="button" @click="showModal = false"
                                    class="px-4 py-2.5 border border-border text-foreground rounded-lg hover:bg-accent/40 transition text-sm">
                                    Annuler
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AppLayout>
</template>
