<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ filiales: Array, entities: Array });
const showModal = ref(false);
const editing = ref(null);

const form = useForm({ name: '', entity_id: '', country: '', is_active: true });

const openCreate = () => { editing.value = null; form.reset(); form.is_active = true; showModal.value = true; };
const openEdit = (f) => {
    editing.value = f;
    form.name = f.name; form.entity_id = f.entity_id;
    form.country = f.country || ''; form.is_active = f.is_active;
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('admin.filiales.update', editing.value.id), { onSuccess: () => { showModal.value = false; } });
    } else {
        form.post(route('admin.filiales.store'), { onSuccess: () => { showModal.value = false; form.reset(); } });
    }
};

const deleteFiliale = (f) => {
    if (!confirm(`Supprimer "${f.name}" ?`)) return;
    router.delete(route('admin.filiales.destroy', f.id));
};
</script>

<template>
    <Head title="Filiales — Admin" />
    <AppLayout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Filiales</h1>
            <button @click="openCreate"
                class="flex items-center gap-2 px-4 py-2 bg-blue-800 text-white rounded-lg hover:bg-blue-900 transition text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouvelle filiale
            </button>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nom</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Entité</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pays</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Documents</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Statut</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="filiale in filiales" :key="filiale.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ filiale.name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ filiale.entity?.name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ filiale.country || '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ filiale.documents_count }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="filiale.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'">
                                {{ filiale.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 justify-end">
                                <button @click="openEdit(filiale)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Modifier</button>
                                <button @click="deleteFiliale(filiale)" class="text-red-500 hover:text-red-700 text-xs font-medium">Supprimer</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60" @click.self="showModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6">
                <h3 class="font-bold text-gray-800 mb-4">{{ editing ? 'Modifier la filiale' : 'Nouvelle filiale' }}</h3>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                        <input v-model="form.name" type="text" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Entité *</label>
                        <select v-model="form.entity_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Sélectionner</option>
                            <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                        <input v-model="form.country" type="text"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                    </div>
                    <label v-if="editing" class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.is_active" class="w-4 h-4 rounded text-blue-600" />
                        <span class="text-sm font-medium text-gray-700">Active</span>
                    </label>
                    <div class="flex gap-3">
                        <button type="submit" :disabled="form.processing"
                            class="flex-1 py-2 bg-blue-800 text-white rounded-lg hover:bg-blue-900 transition text-sm font-medium disabled:opacity-60">
                            {{ form.processing ? '...' : (editing ? 'Mettre à jour' : 'Créer') }}
                        </button>
                        <button type="button" @click="showModal = false"
                            class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
