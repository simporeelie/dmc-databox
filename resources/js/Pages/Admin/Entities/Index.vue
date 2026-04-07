<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ entities: Array });
const showModal = ref(false);
const editing = ref(null);

const form = useForm({ name: '', is_active: true });

const openCreate = () => { editing.value = null; form.reset(); form.is_active = true; showModal.value = true; };
const openEdit = (e) => { editing.value = e; form.name = e.name; form.is_active = e.is_active; showModal.value = true; };

const submit = () => {
    if (editing.value) {
        form.put(route('admin.entities.update', editing.value.id), { onSuccess: () => { showModal.value = false; } });
    } else {
        form.post(route('admin.entities.store'), { onSuccess: () => { showModal.value = false; form.reset(); } });
    }
};

const deleteEntity = (e) => {
    if (!confirm(`Supprimer "${e.name}" ? Tous les documents associés seront supprimés.`)) return;
    router.delete(route('admin.entities.destroy', e.id));
};
</script>

<template>
    <Head title="Entités — Admin" />
    <AppLayout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-foreground">Entités</h1>
            <button @click="openCreate"
                class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-900 transition text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouvelle entité
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="entity in entities" :key="entity.id"
                class="bg-background rounded-xl border border-border p-5 hover:shadow-md transition">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="font-semibold text-foreground">{{ entity.name }}</h3>
                        <p class="text-xs text-muted-foreground mt-1">{{ entity.filiales_count }} filiale{{ entity.filiales_count > 1 ? 's' : '' }} · {{ entity.documents_count }} document{{ entity.documents_count > 1 ? 's' : '' }}</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="entity.is_active ? 'bg-emerald-500/15 text-green-700' : 'bg-red-100 text-red-600'">
                        {{ entity.is_active ? 'Actif' : 'Inactif' }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <button @click="openEdit(entity)" class="text-xs text-primary hover:text-primary font-medium">Modifier</button>
                    <button @click="deleteEntity(entity)" class="text-xs text-destructive hover:text-red-700 font-medium">Supprimer</button>
                </div>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60" @click.self="showModal = false">
            <div class="bg-background rounded-2xl shadow-2xl max-w-sm w-full p-6">
                <h3 class="font-bold text-foreground mb-4">{{ editing ? 'Modifier l\'entité' : 'Nouvelle entité' }}</h3>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Nom *</label>
                        <input v-model="form.name" type="text" required
                            class="w-full border border-input rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none" />
                        <p v-if="form.errors.name" class="text-destructive text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <label v-if="editing" class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.is_active" class="w-4 h-4 rounded text-primary" />
                        <span class="text-sm font-medium text-foreground">Active</span>
                    </label>
                    <div class="flex gap-3">
                        <button type="submit" :disabled="form.processing"
                            class="flex-1 py-2 bg-primary text-white rounded-lg hover:bg-blue-900 transition text-sm font-medium disabled:opacity-60">
                            {{ form.processing ? '...' : (editing ? 'Mettre à jour' : 'Créer') }}
                        </button>
                        <button type="button" @click="showModal = false"
                            class="px-4 py-2 border border-border text-foreground/80 rounded-lg text-sm">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
