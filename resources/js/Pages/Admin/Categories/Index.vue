<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ categories: Array });

const showCatModal = ref(false);
const showSubModal = ref(false);
const editingCat = ref(null);
const selectedCat = ref(null);

const catForm = useForm({ name: '', icon: '' });
const subForm = useForm({ name: '', category_id: '' });

const openCatCreate = () => { editingCat.value = null; catForm.reset(); showCatModal.value = true; };

const submitCat = () => {
    catForm.post(route('admin.categories.store'), { onSuccess: () => { showCatModal.value = false; catForm.reset(); } });
};

const deleteCat = (cat) => {
    if (!confirm(`Supprimer la catégorie "${cat.name}" ?`)) return;
    router.delete(route('admin.categories.destroy', cat.id));
};

const openSubCreate = (cat) => {
    selectedCat.value = cat;
    subForm.reset();
    subForm.category_id = cat.id;
    showSubModal.value = true;
};

const submitSub = () => {
    subForm.post(route('admin.subcategories.store'), { onSuccess: () => { showSubModal.value = false; subForm.reset(); } });
};

const deleteSub = (sub) => {
    if (!confirm(`Supprimer "${sub.name}" ?`)) return;
    router.delete(route('admin.subcategories.destroy', sub.id));
};
</script>

<template>
    <Head title="Catégories — Admin" />
    <AppLayout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-foreground">Catégories</h1>
            <button @click="openCatCreate"
                class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-900 transition text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouvelle catégorie
            </button>
        </div>

        <div class="space-y-4">
            <div v-for="cat in categories" :key="cat.id" class="bg-background rounded-xl border border-border p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-foreground text-lg">{{ cat.name }}</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">{{ cat.documents_count }} document{{ cat.documents_count > 1 ? 's' : '' }}</p>
                    </div>
                    <div class="flex gap-3 items-center">
                        <button @click="openSubCreate(cat)"
                            class="text-xs text-primary hover:text-primary font-medium border border-blue-200 rounded-lg px-3 py-1">
                            + Sous-catégorie
                        </button>
                        <button @click="deleteCat(cat)" class="text-xs text-destructive hover:text-red-700 font-medium">Supprimer</button>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <div v-for="sub in cat.subcategories" :key="sub.id"
                        class="flex items-center gap-2 bg-accent/50 rounded-lg px-3 py-1.5">
                        <span class="text-sm text-foreground">{{ sub.name }}</span>
                        <button @click="deleteSub(sub)" class="text-muted-foreground hover:text-destructive transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <span v-if="!cat.subcategories.length" class="text-xs text-muted-foreground italic">Aucune sous-catégorie</span>
                </div>
            </div>
        </div>

        <!-- Modal catégorie -->
        <div v-if="showCatModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60" @click.self="showCatModal = false">
            <div class="bg-background rounded-2xl shadow-2xl max-w-sm w-full p-6">
                <h3 class="font-bold text-foreground mb-4">Nouvelle catégorie</h3>
                <form @submit.prevent="submitCat" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Nom *</label>
                        <input v-model="catForm.name" type="text" required
                            class="w-full border border-input rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none" />
                        <p v-if="catForm.errors.name" class="text-destructive text-xs mt-1">{{ catForm.errors.name }}</p>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" :disabled="catForm.processing"
                            class="flex-1 py-2 bg-primary text-white rounded-lg hover:bg-blue-900 transition text-sm font-medium disabled:opacity-60">
                            {{ catForm.processing ? '...' : 'Créer' }}
                        </button>
                        <button type="button" @click="showCatModal = false"
                            class="px-4 py-2 border border-border text-foreground/80 rounded-lg text-sm">Annuler</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal sous-catégorie -->
        <div v-if="showSubModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60" @click.self="showSubModal = false">
            <div class="bg-background rounded-2xl shadow-2xl max-w-sm w-full p-6">
                <h3 class="font-bold text-foreground mb-1">Nouvelle sous-catégorie</h3>
                <p class="text-sm text-muted-foreground mb-4">dans <strong>{{ selectedCat?.name }}</strong></p>
                <form @submit.prevent="submitSub" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Nom *</label>
                        <input v-model="subForm.name" type="text" required
                            class="w-full border border-input rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none" />
                        <p v-if="subForm.errors.name" class="text-destructive text-xs mt-1">{{ subForm.errors.name }}</p>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" :disabled="subForm.processing"
                            class="flex-1 py-2 bg-primary text-white rounded-lg hover:bg-blue-900 transition text-sm font-medium disabled:opacity-60">
                            {{ subForm.processing ? '...' : 'Créer' }}
                        </button>
                        <button type="button" @click="showSubModal = false"
                            class="px-4 py-2 border border-border text-foreground/80 rounded-lg text-sm">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
