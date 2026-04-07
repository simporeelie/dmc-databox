<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    users: Array,
    entities: Array,
    filiales: Array,
});

const flash = computed(() => usePage().props.flash ?? {});

const showModal = ref(false);
const editing = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'viewer',
    entity_id: '',
    filiale_id: '',
    is_active: true,
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.role = 'viewer';
    form.is_active = true;
    showModal.value = true;
};

const openEdit = (user) => {
    editing.value = user;
    form.name       = user.name;
    form.email      = user.email;
    form.password   = '';
    form.role       = user.role;
    form.entity_id  = user.entity_id || '';
    form.filiale_id = user.filiale_id || '';
    form.is_active  = user.is_active;
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

const resendInvitation = (user) => {
    if (!confirm(`Renvoyer l'invitation à "${user.email}" ?`)) return;
    router.post(route('admin.users.resend-invitation', user.id));
};

const roleBadge = (role) => {
    const styles = {
        admin:   'bg-red-500/15 text-red-700 dark:text-red-400',
        dmc:     'bg-primary/10 text-primary',
        rmc:     'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
        viewer:  'bg-accent/60 text-muted-foreground',
    };
    return styles[role] || 'bg-accent/60 text-muted-foreground';
};

const roleLabel = { admin: 'Admin', dmc: 'DMC', rmc: 'RMC', viewer: 'Visiteur' };

const formatDate = (iso) => {
    if (!iso) return null;
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    }).format(new Date(iso));
};

const formatDateShort = (iso) => {
    if (!iso) return null;
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric',
    }).format(new Date(iso));
};
</script>

<template>
    <Head title="Utilisateurs — Admin" />
    <AppLayout>

        <!-- Flash message -->
        <div v-if="flash.success"
            class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-sm">
            <i class="ki-filled ki-check-circle text-base flex-shrink-0"></i>
            {{ flash.success }}
        </div>

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Utilisateurs</h1>
                <p class="text-muted-foreground text-sm mt-1">{{ users.length }} utilisateur{{ users.length > 1 ? 's' : '' }}</p>
            </div>
            <button @click="openCreate"
                class="flex items-center gap-2 px-4 py-2.5 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition text-sm font-medium">
                <i class="ki-filled ki-user-tick text-base"></i>
                Inviter un utilisateur
            </button>
        </div>

        <!-- Table -->
        <div class="bg-background rounded-2xl border border-border overflow-hidden shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-accent/30 border-b border-border">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Utilisateur</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Rôle</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden md:table-cell">Entité / Filiale</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden lg:table-cell">Dernière connexion</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden lg:table-cell">Connexions</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Statut</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="user in users" :key="user.id" class="hover:bg-accent/20 transition-colors">

                        <!-- Nom + email -->
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="size-9 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                                    <span class="text-sm font-bold text-primary uppercase">{{ user.name.charAt(0) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-foreground leading-tight">{{ user.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                                </div>
                            </div>
                        </td>

                        <!-- Rôle -->
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold uppercase" :class="roleBadge(user.role)">
                                {{ roleLabel[user.role] || user.role }}
                            </span>
                        </td>

                        <!-- Entité / Filiale -->
                        <td class="px-4 py-3 hidden md:table-cell">
                            <div v-if="user.entity || user.filiale" class="text-xs">
                                <p v-if="user.entity" class="text-foreground font-medium">{{ user.entity.name }}</p>
                                <p v-if="user.filiale" class="text-muted-foreground">{{ user.filiale.name }}</p>
                            </div>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>

                        <!-- Dernière connexion -->
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <div v-if="user.last_login_at">
                                <p class="text-foreground text-xs">{{ formatDate(user.last_login_at) }}</p>
                            </div>
                            <div v-else class="flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
                                <span class="text-xs text-amber-600 dark:text-amber-400">Jamais connecté</span>
                            </div>
                        </td>

                        <!-- Nb connexions -->
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <div class="flex items-center gap-1.5">
                                <i class="ki-filled ki-entrance-right text-muted-foreground text-xs"></i>
                                <span class="text-xs text-foreground tabular-nums">{{ user.login_count }}</span>
                            </div>
                        </td>

                        <!-- Statut actif -->
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="user.is_active
                                    ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400'
                                    : 'bg-red-500/15 text-red-600 dark:text-red-400'">
                                {{ user.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1 justify-end">
                                <!-- Renvoyer invitation si jamais connecté -->
                                <button v-if="!user.last_login_at" @click="resendInvitation(user)"
                                    title="Renvoyer l'invitation"
                                    class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-500/10 transition">
                                    <i class="ki-filled ki-send text-sm"></i>
                                </button>
                                <button @click="openEdit(user)"
                                    title="Modifier"
                                    class="p-1.5 rounded-lg text-primary hover:bg-primary/10 transition">
                                    <i class="ki-filled ki-pencil text-sm"></i>
                                </button>
                                <button @click="deleteUser(user)"
                                    title="Supprimer"
                                    class="p-1.5 rounded-lg text-destructive hover:bg-destructive/10 transition">
                                    <i class="ki-filled ki-trash text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="users.length === 0">
                        <td colspan="7" class="px-4 py-12 text-center text-muted-foreground text-sm">
                            Aucun utilisateur trouvé.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal créer / modifier -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition duration-150"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    @click.self="showModal = false">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

                    <Transition enter-active-class="transition duration-200"
                        enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition duration-150"
                        leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                        <div v-if="showModal"
                            class="relative bg-background rounded-2xl shadow-2xl w-full max-w-lg border border-border">

                            <!-- Header -->
                            <div class="flex items-center justify-between px-6 py-4 border-b border-border">
                                <div class="flex items-center gap-3">
                                    <div class="size-9 rounded-xl bg-primary/10 flex items-center justify-center">
                                        <i class="ki-filled ki-user-square text-primary text-base"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-foreground text-sm">
                                            {{ editing ? 'Modifier l\'utilisateur' : 'Inviter un utilisateur' }}
                                        </h3>
                                        <p v-if="!editing" class="text-xs text-muted-foreground">
                                            Un email d'invitation sera envoyé automatiquement
                                        </p>
                                    </div>
                                </div>
                                <button @click="showModal = false"
                                    class="size-8 rounded-lg flex items-center justify-center text-muted-foreground hover:bg-accent/60 transition">
                                    <i class="ki-filled ki-cross text-base"></i>
                                </button>
                            </div>

                            <!-- Form -->
                            <form @submit.prevent="submit" class="p-6 space-y-4">

                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Nom -->
                                    <div class="col-span-2">
                                        <label class="block text-sm font-medium text-foreground mb-1.5">Nom complet *</label>
                                        <input v-model="form.name" type="text" required placeholder="Prénom Nom"
                                            class="w-full border border-input rounded-lg px-3.5 py-2.5 text-sm bg-background text-foreground
                                                   focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" />
                                        <p v-if="form.errors.name" class="text-destructive text-xs mt-1">{{ form.errors.name }}</p>
                                    </div>

                                    <!-- Email -->
                                    <div class="col-span-2">
                                        <label class="block text-sm font-medium text-foreground mb-1.5">Adresse email *</label>
                                        <input v-model="form.email" type="email" required placeholder="email@coris.com"
                                            class="w-full border border-input rounded-lg px-3.5 py-2.5 text-sm bg-background text-foreground
                                                   focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" />
                                        <p v-if="form.errors.email" class="text-destructive text-xs mt-1">{{ form.errors.email }}</p>
                                    </div>

                                    <!-- Mot de passe (modification uniquement) -->
                                    <div v-if="editing" class="col-span-2">
                                        <label class="block text-sm font-medium text-foreground mb-1.5">
                                            Nouveau mot de passe <span class="text-muted-foreground font-normal">(laisser vide = inchangé)</span>
                                        </label>
                                        <input v-model="form.password" type="password"
                                            class="w-full border border-input rounded-lg px-3.5 py-2.5 text-sm bg-background text-foreground
                                                   focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" />
                                        <p v-if="form.errors.password" class="text-destructive text-xs mt-1">{{ form.errors.password }}</p>
                                    </div>

                                    <!-- Rôle -->
                                    <div>
                                        <label class="block text-sm font-medium text-foreground mb-1.5">Rôle *</label>
                                        <select v-model="form.role" required
                                            class="w-full border border-input rounded-lg px-3.5 py-2.5 text-sm bg-background text-foreground
                                                   focus:ring-2 focus:ring-primary focus:border-primary outline-none transition">
                                            <option value="admin">Admin</option>
                                            <option value="dmc">DMC</option>
                                            <option value="rmc">RMC</option>
                                            <option value="viewer">Visiteur</option>
                                        </select>
                                    </div>

                                    <!-- Entité -->
                                    <div>
                                        <label class="block text-sm font-medium text-foreground mb-1.5">Entité</label>
                                        <select v-model="form.entity_id"
                                            class="w-full border border-input rounded-lg px-3.5 py-2.5 text-sm bg-background text-foreground
                                                   focus:ring-2 focus:ring-primary focus:border-primary outline-none transition">
                                            <option value="">Aucune</option>
                                            <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.name }}</option>
                                        </select>
                                    </div>

                                    <!-- Filiale -->
                                    <div>
                                        <label class="block text-sm font-medium text-foreground mb-1.5">Filiale</label>
                                        <select v-model="form.filiale_id"
                                            class="w-full border border-input rounded-lg px-3.5 py-2.5 text-sm bg-background text-foreground
                                                   focus:ring-2 focus:ring-primary focus:border-primary outline-none transition">
                                            <option value="">Aucune</option>
                                            <option v-for="f in filiales" :key="f.id" :value="f.id">{{ f.name }}</option>
                                        </select>
                                    </div>

                                    <!-- Statut actif (modification uniquement) -->
                                    <div v-if="editing" class="flex items-end pb-1">
                                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                                            <button type="button" @click="form.is_active = !form.is_active"
                                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200"
                                                :class="form.is_active ? 'bg-primary' : 'bg-border'">
                                                <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform duration-200"
                                                    :class="form.is_active ? 'translate-x-4' : 'translate-x-1'"></span>
                                            </button>
                                            <span class="text-sm text-foreground">Compte actif</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Info invitation (création) -->
                                <div v-if="!editing"
                                    class="flex items-start gap-2.5 p-3 rounded-lg bg-primary/5 border border-primary/15 text-sm">
                                    <i class="ki-filled ki-information-2 text-primary flex-shrink-0 mt-0.5"></i>
                                    <p class="text-foreground/80">
                                        L'utilisateur recevra un email avec un lien pour définir son mot de passe.
                                        Le lien est valable <strong>60 minutes</strong>.
                                    </p>
                                </div>

                                <!-- Actions -->
                                <div class="flex gap-3 pt-1">
                                    <button type="submit" :disabled="form.processing"
                                        class="flex-1 py-2.5 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90
                                               transition text-sm font-medium disabled:opacity-60 flex items-center justify-center gap-2">
                                        <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                        </svg>
                                        <span v-if="form.processing">{{ editing ? 'Enregistrement...' : 'Envoi de l\'invitation...' }}</span>
                                        <span v-else>{{ editing ? 'Mettre à jour' : 'Envoyer l\'invitation' }}</span>
                                    </button>
                                    <button type="button" @click="showModal = false"
                                        class="px-4 py-2.5 border border-border text-foreground rounded-lg hover:bg-accent/40 transition text-sm">
                                        Annuler
                                    </button>
                                </div>
                            </form>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

    </AppLayout>
</template>
