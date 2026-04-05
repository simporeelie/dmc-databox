<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    users: Array,
    entities: Array,
    filiales: Array,
});

const showModal = ref(false);
const editing = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'visiteur',
    entity_id: '',
    filiale_id: '',
    is_active: true,
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    showModal.value = true;
};

const openEdit = (user) => {
    editing.value = user;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.role = user.role;
    form.entity_id = user.entity_id || '';
    form.filiale_id = user.filiale_id || '';
    form.is_active = user.is_active;
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('admin.users.update', editing.value.id), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    } else {
        form.post(route('admin.users.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
};

const deleteUser = (user) => {
    if (!confirm(`Supprimer l'utilisateur "${user.name}" ?`)) return;
    router.delete(route('admin.users.destroy', user.id));
};

const roleBadge = (role) => {
    const styles = {
        admin: 'bg-red-100 text-red-700',
        dmc: 'bg-blue-100 text-blue-700',
        rmc: 'bg-green-100 text-green-700',
        visiteur: 'bg-gray-100 text-gray-600',
    };
    return styles[role] || 'bg-gray-100 text-gray-600';
};
</script>

<template>
    <Head title="Utilisateurs — Admin" />
    <AppLayout>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Utilisateurs</h1>
                <p class="text-gray-500 text-sm mt-1">{{ users.length }} utilisateur{{ users.length > 1 ? 's' : '' }}</p>
            </div>
            <button @click="openCreate"
                class="flex items-center gap-2 px-4 py-2 bg-blue-800 text-white rounded-lg hover:bg-blue-900 transition text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouvel utilisateur
            </button>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nom</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Rôle</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Entité / Filiale</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Statut</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-blue-800 text-white flex items-center justify-center text-xs font-bold">
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </div>
                                <span class="font-medium text-gray-800">{{ user.name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ user.email }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium uppercase" :class="roleBadge(user.role)">
                                {{ user.role }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            <span v-if="user.entity">{{ user.entity.name }}</span>
                            <span v-if="user.filiale" class="text-gray-400"> · {{ user.filiale.name }}</span>
                            <span v-if="!user.entity && !user.filiale">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="user.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'">
                                {{ user.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 justify-end">
                                <button @click="openEdit(user)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Modifier</button>
                                <button @click="deleteUser(user)" class="text-red-500 hover:text-red-700 text-xs font-medium">Supprimer</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60"
            @click.self="showModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
                <div class="flex items-center justify-between p-5 border-b">
                    <h3 class="font-bold text-gray-800">{{ editing ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' }}</h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submit" class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                        <input v-model="form.name" type="text" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                        <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input v-model="form.email" type="email" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                        <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Mot de passe {{ editing ? '(laisser vide = inchangé)' : '*' }}
                        </label>
                        <input v-model="form.password" type="password" :required="!editing"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                        <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rôle *</label>
                            <select v-model="form.role" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="admin">Admin</option>
                                <option value="dmc">DMC</option>
                                <option value="rmc">RMC</option>
                                <option value="visiteur">Visiteur</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Entité</label>
                            <select v-model="form.entity_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">Aucune</option>
                                <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filiale</label>
                            <select v-model="form.filiale_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">Aucune</option>
                                <option v-for="f in filiales" :key="f.id" :value="f.id">{{ f.name }}</option>
                            </select>
                        </div>
                        <div v-if="editing" class="flex items-end">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="form.is_active"
                                    class="w-4 h-4 rounded border-gray-300 text-blue-600" />
                                <span class="text-sm font-medium text-gray-700">Actif</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="form.processing"
                            class="flex-1 py-2 bg-blue-800 text-white rounded-lg hover:bg-blue-900 transition text-sm font-medium disabled:opacity-60">
                            {{ form.processing ? 'Enregistrement...' : (editing ? 'Mettre à jour' : 'Créer') }}
                        </button>
                        <button type="button" @click="showModal = false"
                            class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition text-sm">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
