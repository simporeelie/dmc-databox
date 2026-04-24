<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    documents: Object,
    entities: Array,
    filiales: Array,
    categories: Array,
    years: Array,
    filters: Object,
    counts: Object,
});

const page = usePage();
const user = page.props.auth.user;
const canUpload   = ['admin', 'dmc'].includes(user.role);
const canDelete   = ['admin', 'dmc', 'rmc'].includes(user.role);
const canDownload = ['admin', 'dmc', 'rmc'].includes(user.role);

// Filtres actifs — chaque clé est un tableau pour la multi-sélection
const toArr = (v) => v ? [].concat(v).map(String) : [];
const activeFilters = ref({
    entity_id:      toArr(props.filters.entity_id),
    filiale_id:     toArr(props.filters.filiale_id),
    category_id:    toArr(props.filters.category_id),
    subcategory_id: toArr(props.filters.subcategory_id),
    year:           toArr(props.filters.year),
});
const search = ref(props.filters.search || '');

// Vérifie si un item est sélectionné
const isActive = (key, value) => activeFilters.value[key].includes(String(value));

// Sous-catégories disponibles pour toutes les catégories sélectionnées
const availableSubcategories = computed(() => {
    if (!activeFilters.value.category_id.length) return [];
    return props.categories
        .filter(c => activeFilters.value.category_id.includes(String(c.id)))
        .flatMap(c => c.subcategories || []);
});

const applyFilters = () => {
    const params = { search: search.value || undefined };
    Object.entries(activeFilters.value).forEach(([k, v]) => {
        if (v.length) params[k] = v;
    });
    router.get(route('library.index'), params, { preserveState: true, replace: true });
};

// Toggle un item dans un filtre tableau
const setFilter = (key, value) => {
    const arr = activeFilters.value[key];
    const idx = arr.indexOf(String(value));
    if (idx === -1) {
        arr.push(String(value));
    } else {
        arr.splice(idx, 1);
        // Si on désélectionne une catégorie, retirer ses sous-catégories orphelines
        if (key === 'category_id') {
            const remaining = props.categories
                .filter(c => activeFilters.value.category_id.includes(String(c.id)))
                .flatMap(c => c.subcategories?.map(s => String(s.id)) || []);
            activeFilters.value.subcategory_id = activeFilters.value.subcategory_id.filter(id => remaining.includes(id));
        }
    }
    applyFilters();
};

const clearAll = () => {
    activeFilters.value = { entity_id: [], filiale_id: [], category_id: [], subcategory_id: [], year: [] };
    search.value = '';
    applyFilters();
};

// Sections collapsibles
const openSections = ref({ year: true, entity: true, filiale: true, category: true, subcategory: true });
const toggleSection = (key) => { openSections.value[key] = !openSections.value[key]; };
const getCount = (type, id) => props.counts?.[type]?.[id] ?? 0;

let searchTimer;
const onSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
};

const activeFilterCount = computed(() =>
    Object.values(activeFilters.value).reduce((sum, arr) => sum + arr.length, 0) + (search.value ? 1 : 0)
);

// Mode d'affichage
const viewMode = ref(localStorage.getItem('library_view') || 'grid');
const setViewMode = (mode) => {
    viewMode.value = mode;
    localStorage.setItem('library_view', mode);
};

// Sélection multiple pour téléchargement ZIP
const selectedIds = ref([]);
const isDownloadingZip = ref(false);

const toggleSelect = (id) => {
    const idx = selectedIds.value.indexOf(id);
    if (idx === -1) selectedIds.value.push(id);
    else selectedIds.value.splice(idx, 1);
};

const isSelected = (id) => selectedIds.value.includes(id);

const selectAll = () => {
    selectedIds.value = props.documents.data.map(d => d.id);
};

const clearSelection = () => {
    selectedIds.value = [];
};

const downloadZip = async () => {
    if (!selectedIds.value.length) return;
    isDownloadingZip.value = true;
    try {
        const csrfToken = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        const headers = { 'Content-Type': 'application/json' };
        if (csrfToken) headers['X-XSRF-TOKEN'] = decodeURIComponent(csrfToken[1]);

        const res = await fetch(route('documents.download-zip'), {
            method: 'POST',
            headers,
            body: JSON.stringify({ ids: selectedIds.value }),
        });

        if (!res.ok) { isDownloadingZip.value = false; return; }

        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'documents_' + new Date().toISOString().slice(0, 10) + '.zip';
        a.click();
        URL.revokeObjectURL(url);
        clearSelection();
    } catch (e) {
        console.error(e);
    } finally {
        isDownloadingZip.value = false;
    }
};

// Export CSV — envoie les tableaux correctement
const exportUrl = computed(() => {
    const params = new URLSearchParams();
    Object.entries(activeFilters.value).forEach(([k, v]) => {
        v.forEach(item => params.append(k + '[]', item));
    });
    if (search.value) params.append('search', search.value);
    const qs = params.toString();
    return route('documents.export') + (qs ? '?' + qs : '');
});

// Demande de document
const showRequest = ref(false);
const requestForm = useForm({
    title: '',
    description: '',
    category: '',
    period: '',
});
const submitRequest = () => {
    requestForm.post(route('document-requests.store'), {
        onSuccess: () => {
            showRequest.value = false;
            requestForm.reset();
        },
    });
};

// Upload
const showUpload = ref(false);
const isUploading = ref(false);
const uploadProgress = ref(0);
const uploadErrors = ref({});
const uploadTitle = ref('');
const uploadDescription = ref('');
const uploadEntityId = ref('');
const uploadFilialeId = ref('');
const uploadCategoryId = ref('');
const uploadSubcategoryId = ref('');
const uploadYear = ref(new Date().getFullYear());
const uploadFile = ref(null);
const selectedFileName = ref('');
const selectedFileSize = ref('');

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    uploadFile.value = file;
    selectedFileName.value = file.name;
    const mb = file.size / 1024 / 1024;
    selectedFileSize.value = mb >= 1 ? mb.toFixed(1) + ' MB' : (file.size / 1024).toFixed(0) + ' KB';
};

const uploadSubcategories = computed(() => {
    if (!uploadCategoryId.value) return [];
    const cat = props.categories.find(c => c.id == uploadCategoryId.value);
    return cat ? cat.subcategories : [];
});

const CHUNK_SIZE = 5 * 1024 * 1024;

const submitUpload = async () => {
    uploadErrors.value = {};
    if (!uploadFile.value) return;
    isUploading.value = true;
    uploadProgress.value = 0;
    const file = uploadFile.value;
    const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
    const uploadId = crypto.randomUUID();
    try {
        for (let i = 0; i < totalChunks; i++) {
            const start = i * CHUNK_SIZE;
            const end = Math.min(start + CHUNK_SIZE, file.size);
            const fd = new FormData();
            fd.append('chunk', file.slice(start, end), file.name);
            fd.append('chunk_index', i);
            fd.append('total_chunks', totalChunks);
            fd.append('upload_id', uploadId);
            fd.append('filename', file.name);
            fd.append('title', uploadTitle.value);
            fd.append('description', uploadDescription.value);
            fd.append('entity_id', uploadEntityId.value);
            fd.append('filiale_id', uploadFilialeId.value);
            fd.append('category_id', uploadCategoryId.value);
            fd.append('subcategory_id', uploadSubcategoryId.value);
            fd.append('year', uploadYear.value);
            const csrfToken = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
            const headers = {};
            if (csrfToken) headers['X-XSRF-TOKEN'] = decodeURIComponent(csrfToken[1]);
            const res = await fetch(route('documents.chunk'), { method: 'POST', headers, body: fd });
            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                uploadErrors.value = data.errors || { file: 'Erreur lors de l\'envoi.' };
                isUploading.value = false;
                return;
            }
            uploadProgress.value = Math.round(((i + 1) / totalChunks) * 100);
        }
        showUpload.value = false;
        isUploading.value = false;
        uploadProgress.value = 0;
        uploadTitle.value = ''; uploadDescription.value = '';
        uploadEntityId.value = ''; uploadFilialeId.value = '';
        uploadCategoryId.value = ''; uploadSubcategoryId.value = '';
        uploadYear.value = new Date().getFullYear();
        uploadFile.value = null; selectedFileName.value = ''; selectedFileSize.value = '';
        router.reload({ only: ['documents'] });
    } catch (e) {
        uploadErrors.value = { file: 'Erreur réseau. Veuillez réessayer.' };
        isUploading.value = false;
    }
};

// Édition
const showEdit = ref(false);
const editDoc = ref(null);
const editErrors = ref({});
const isEditing = ref(false);
const editForm = ref({ title: '', description: '', entity_id: '', filiale_id: '', category_id: '', subcategory_id: '', year: new Date().getFullYear() });
const editSubcategories = computed(() => {
    if (!editForm.value.category_id) return [];
    const cat = props.categories.find(c => c.id == editForm.value.category_id);
    return cat ? cat.subcategories : [];
});
const openEdit = (doc) => {
    editDoc.value = doc;
    editForm.value = { title: doc.title, description: doc.description || '', entity_id: doc.entity_id, filiale_id: doc.filiale_id || '', category_id: doc.category_id, subcategory_id: doc.subcategory_id || '', year: doc.year };
    editErrors.value = {};
    showEdit.value = true;
    preview.value = null;
};
const submitEdit = async () => {
    editErrors.value = {};
    isEditing.value = true;
    const csrfToken = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    const headers = { 'Content-Type': 'application/json' };
    if (csrfToken) headers['X-XSRF-TOKEN'] = decodeURIComponent(csrfToken[1]);
    try {
        const res = await fetch(route('documents.update', editDoc.value.id), {
            method: 'POST', headers,
            body: JSON.stringify({ ...editForm.value, _method: 'PUT' }),
        });
        if (res.ok || res.redirected) {
            showEdit.value = false;
            router.reload({ only: ['documents'] });
        } else {
            const data = await res.json().catch(() => ({}));
            editErrors.value = data.errors || { title: 'Une erreur est survenue.' };
        }
    } catch (e) {
        editErrors.value = { title: 'Erreur réseau.' };
    } finally {
        isEditing.value = false;
    }
};

// Lazy load vidéo
const videoRefs = ref({});
let observer = null;
onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const video = entry.target;
                const src = video.getAttribute('data-src');
                if (src && !video.src) { video.src = src; video.load(); }
                observer.unobserve(video);
            }
        });
    }, { threshold: 0.1 });
});
watch(() => props.documents.data, () => {
    setTimeout(() => { Object.values(videoRefs.value).forEach(v => { if (v && observer) observer.observe(v); }); }, 100);
}, { immediate: true });
onUnmounted(() => { if (observer) observer.disconnect(); });

// Prévisualisation
const preview = ref(null);
const previewIndex = computed(() => {
    if (!preview.value) return -1;
    return props.documents.data.findIndex(d => d.id === preview.value.id);
});
const previewPrev = () => { if (previewIndex.value > 0) preview.value = props.documents.data[previewIndex.value - 1]; };
const previewNext = () => { if (previewIndex.value < props.documents.data.length - 1) preview.value = props.documents.data[previewIndex.value + 1]; };

const onKeydown = (e) => {
    if (e.key === 'Escape') { preview.value = null; showUpload.value = false; showEdit.value = false; }
    if (preview.value) {
        if (e.key === 'ArrowLeft') previewPrev();
        if (e.key === 'ArrowRight') previewNext();
    }
};
onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));

const deleteDocument = (doc) => {
    if (!confirm(`Supprimer "${doc.title}" ?`)) return;
    router.delete(route('documents.destroy', doc.id));
};

// Tags colorés sur les cartes (entité + catégorie + filiale)
const tagColors = [
    'bg-red-500/15 text-destructive dark:text-red-400',
    'bg-primary/10 text-primary',
    'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
    'bg-purple-500/15 text-purple-700 dark:text-purple-400',
    'bg-orange-500/15 text-orange-700 dark:text-orange-400',
    'bg-pink-500/15 text-pink-700 dark:text-pink-400',
    'bg-teal-500/15 text-teal-700 dark:text-teal-400',
    'bg-amber-500/15 text-amber-700 dark:text-amber-400',
    'bg-indigo-500/15 text-indigo-700 dark:text-indigo-400',
];
const colorCache = {};
const getTagColor = (name) => {
    if (!colorCache[name]) {
        let hash = 0;
        for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
        colorCache[name] = tagColors[Math.abs(hash) % tagColors.length];
    }
    return colorCache[name];
};

const getDocTags = (doc) => {
    const tags = [];
    if (doc.entity?.name) tags.push(doc.entity.name);
    if (doc.filiale?.name) tags.push(doc.filiale.name);
    if (doc.category?.name) tags.push(doc.category.name);
    if (doc.subcategory?.name) tags.push(doc.subcategory.name);
    if (doc.year) tags.push(String(doc.year));
    return tags;
};
</script>

<template>
    <Head title="Bibliothèque — DMC DataBox" />
    <AppLayout :noPadding="true">

        <!-- Layout principal style Odoo : sidebar gauche + contenu -->
        <div class="flex flex-1 overflow-hidden">

            <!-- ===== SIDEBAR FILTRES GAUCHE ===== -->
            <aside class="w-56 flex-shrink-0 bg-background border-r border-border flex flex-col overflow-hidden">
                <div class="p-4 flex-shrink-0 border-b border-border">
                    <!-- Bouton Upload -->
                    <button v-if="canUpload" @click="showUpload = true"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2.5 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Charger
                    </button>
                    <!-- Bouton Demander un document -->
                    <button @click="showRequest = true"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 border border-border rounded-lg hover:bg-accent/60 transition text-sm text-foreground mt-2">
                        <i class="ki-filled ki-file-added text-base text-muted-foreground"></i>
                        Demander un document
                    </button>
                    <!-- Effacer filtres -->
                    <button v-if="activeFilterCount" @click="clearAll"
                        class="w-full text-xs text-center text-destructive hover:text-destructive py-1 mt-2">
                        ✕ Effacer les filtres ({{ activeFilterCount }})
                    </button>
                </div>

                <!-- Sections collapsibles -->
                <div class="flex-1 overflow-y-auto text-sm">

                    <!-- Section Années -->
                    <div class="border-b border-border">
                        <button @click="toggleSection('year')"
                            class="w-full flex items-center justify-between px-4 py-2.5 text-xs font-bold text-muted-foreground uppercase tracking-wider hover:bg-accent/30 transition">
                            <span>Années</span>
                            <svg class="w-3.5 h-3.5 text-muted-foreground transition-transform duration-200" :class="openSections.year ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <ul v-show="openSections.year" class="px-2 pb-2 space-y-0.5">
                            <li v-for="y in years" :key="y">
                                <button @click="setFilter('year', y)"
                                    class="w-full text-left px-2 py-1 rounded-md transition flex items-center gap-2 group"
                                    :class="isActive('year', y) ? 'bg-primary/5 text-primary font-semibold' : 'text-foreground/80 hover:bg-accent/60'">
                                    <span class="w-3.5 h-3.5 rounded border flex-shrink-0 flex items-center justify-center transition"
                                        :class="isActive('year', y) ? 'bg-primary border-primary' : 'border-input group-hover:border-muted-foreground'">
                                        <svg v-if="isActive('year', y)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                    <span class="flex-1 truncate">{{ y }}</span>
                                    <span v-if="getCount('year', y)" class="text-xs text-muted-foreground font-normal tabular-nums">{{ getCount('year', y) }}</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Section Entités -->
                    <div class="border-b border-border">
                        <button @click="toggleSection('entity')"
                            class="w-full flex items-center justify-between px-4 py-2.5 text-xs font-bold text-muted-foreground uppercase tracking-wider hover:bg-accent/30 transition">
                            <span>Entités</span>
                            <svg class="w-3.5 h-3.5 text-muted-foreground transition-transform duration-200" :class="openSections.entity ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <ul v-show="openSections.entity" class="px-2 pb-2 space-y-0.5">
                            <li v-for="e in entities" :key="e.id">
                                <button @click="setFilter('entity_id', e.id)"
                                    class="w-full text-left px-2 py-1 rounded-md transition flex items-center gap-2 group"
                                    :class="isActive('entity_id', e.id) ? 'bg-primary/5 text-primary font-semibold' : 'text-foreground/80 hover:bg-accent/60'">
                                    <span class="w-3.5 h-3.5 rounded border flex-shrink-0 flex items-center justify-center transition"
                                        :class="isActive('entity_id', e.id) ? 'bg-primary border-primary' : 'border-input group-hover:border-muted-foreground'">
                                        <svg v-if="isActive('entity_id', e.id)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                    <span class="flex-1 truncate">{{ e.name }}</span>
                                    <span v-if="getCount('entity', e.id)" class="text-xs text-muted-foreground font-normal tabular-nums">{{ getCount('entity', e.id) }}</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Section Filiales -->
                    <div v-if="filiales.length" class="border-b border-border">
                        <button @click="toggleSection('filiale')"
                            class="w-full flex items-center justify-between px-4 py-2.5 text-xs font-bold text-muted-foreground uppercase tracking-wider hover:bg-accent/30 transition">
                            <span>Filiales</span>
                            <svg class="w-3.5 h-3.5 text-muted-foreground transition-transform duration-200" :class="openSections.filiale ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <ul v-show="openSections.filiale" class="px-2 pb-2 space-y-0.5">
                            <li v-for="f in filiales" :key="f.id">
                                <button @click="setFilter('filiale_id', f.id)"
                                    class="w-full text-left px-2 py-1 rounded-md transition flex items-center gap-2 group"
                                    :class="isActive('filiale_id', f.id) ? 'bg-primary/5 text-primary font-semibold' : 'text-foreground/80 hover:bg-accent/60'">
                                    <span class="w-3.5 h-3.5 rounded border flex-shrink-0 flex items-center justify-center transition"
                                        :class="isActive('filiale_id', f.id) ? 'bg-primary border-primary' : 'border-input group-hover:border-muted-foreground'">
                                        <svg v-if="isActive('filiale_id', f.id)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                    <span class="flex-1 truncate">{{ f.name }}</span>
                                    <span v-if="getCount('filiale', f.id)" class="text-xs text-muted-foreground font-normal tabular-nums">{{ getCount('filiale', f.id) }}</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Section Catégories -->
                    <div class="border-b border-border">
                        <button @click="toggleSection('category')"
                            class="w-full flex items-center justify-between px-4 py-2.5 text-xs font-bold text-muted-foreground uppercase tracking-wider hover:bg-accent/30 transition">
                            <span>Catégories</span>
                            <svg class="w-3.5 h-3.5 text-muted-foreground transition-transform duration-200" :class="openSections.category ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <ul v-show="openSections.category" class="px-2 pb-2 space-y-0.5">
                            <li v-for="c in categories" :key="c.id">
                                <button @click="setFilter('category_id', c.id)"
                                    class="w-full text-left px-2 py-1 rounded-md transition flex items-center gap-2 group"
                                    :class="isActive('category_id', c.id) ? 'bg-primary/5 text-primary font-semibold' : 'text-foreground/80 hover:bg-accent/60'">
                                    <span class="w-3.5 h-3.5 rounded border flex-shrink-0 flex items-center justify-center transition"
                                        :class="isActive('category_id', c.id) ? 'bg-primary border-primary' : 'border-input group-hover:border-muted-foreground'">
                                        <svg v-if="isActive('category_id', c.id)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                    <span class="flex-1 truncate">{{ c.name }}</span>
                                    <span v-if="getCount('category', c.id)" class="text-xs text-muted-foreground font-normal tabular-nums">{{ getCount('category', c.id) }}</span>
                                </button>

                                <!-- Sous-catégories de cette catégorie si sélectionnée -->
                                <ul v-if="isActive('category_id', c.id) && c.subcategories?.length" class="ml-4 mt-0.5 space-y-0.5">
                                    <li v-for="s in c.subcategories" :key="s.id">
                                        <button @click.stop="setFilter('subcategory_id', s.id)"
                                            class="w-full text-left px-2 py-0.5 rounded-md transition flex items-center gap-2 group"
                                            :class="isActive('subcategory_id', s.id) ? 'text-primary font-semibold' : 'text-muted-foreground hover:bg-accent/60'">
                                            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                                :class="isActive('subcategory_id', s.id) ? 'bg-primary' : 'bg-muted group-hover:bg-muted-foreground'"></span>
                                            <span class="flex-1 truncate text-xs">{{ s.name }}</span>
                                        </button>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>

                </div>
            </aside>

            <!-- ===== ZONE PRINCIPALE ===== -->
            <div class="flex-1 flex flex-col min-w-0 bg-accent/30 overflow-y-auto">

                <!-- Barre du haut style Odoo -->
                <div class="bg-background border-b border-border px-5 py-3 flex items-center gap-3">
                    <!-- Recherche -->
                    <div class="relative flex-1 max-w-md">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input v-model="search" @input="onSearch" type="text" placeholder="Rechercher..."
                            class="w-full pl-9 pr-4 py-2 border border-input rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none bg-background text-foreground placeholder:text-muted-foreground"/>
                    </div>

                    <div class="flex items-center gap-2 ml-auto">
                        <!-- Compteur -->
                        <span class="text-sm text-muted-foreground">
                            {{ documents.from }}-{{ documents.to }} / {{ documents.total }}
                        </span>

                        <!-- Export CSV -->
                        <a v-if="canDownload" :href="exportUrl"
                            class="flex items-center gap-1.5 px-3 py-2 border border-border rounded-lg text-sm text-foreground/80 hover:bg-accent/30 transition"
                            title="Exporter CSV">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                            </svg>
                            CSV
                        </a>

                        <!-- Toggle grille/liste -->
                        <div class="flex items-center border border-border rounded-lg overflow-hidden bg-background">
                            <button @click="setViewMode('grid')"
                                :class="viewMode === 'grid' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent'"
                                class="px-2.5 py-2 transition" title="Grille">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                            </button>
                            <button @click="setViewMode('list')"
                                :class="viewMode === 'list' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent'"
                                class="px-2.5 py-2 transition" title="Liste">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Contenu scrollable -->
                <div class="flex-1 overflow-y-auto p-5">

                    <!-- ===== VUE GRILLE style Odoo ===== -->
                    <div v-if="viewMode === 'grid'">
                        <div v-if="documents.data.length"
                            class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                            <div
                                v-for="doc in documents.data"
                                :key="doc.id"
                                class="bg-background rounded-lg border overflow-hidden hover:shadow-md transition-all duration-200 group cursor-pointer flex flex-col"
                                :class="isSelected(doc.id) ? 'border-primary ring-2 ring-primary/50' : 'border-border'"
                                @click="preview = doc"
                            >
                                <!-- Thumbnail -->
                                <div class="relative overflow-hidden bg-accent/60 flex-shrink-0" style="height: 150px;">
                                    <!-- Checkbox de sélection -->
                                    <div class="absolute top-2 left-2 z-10" @click.stop="toggleSelect(doc.id)">
                                        <span class="w-5 h-5 rounded border-2 flex items-center justify-center transition shadow-sm cursor-pointer"
                                            :class="isSelected(doc.id)
                                                ? 'bg-primary border-primary'
                                                : 'bg-background bg-opacity-80 border-input opacity-0 group-hover:opacity-100'">
                                            <svg v-if="isSelected(doc.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </span>
                                    </div>
                                    <img v-if="doc.file_type === 'image' && doc.thumbnail_path"
                                        :src="`/storage/${doc.thumbnail_path}`" :alt="doc.title"
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300"/>
                                    <div v-else-if="doc.file_type === 'video'" class="w-full h-full relative bg-foreground">
                                        <video class="w-full h-full object-cover" :data-src="route('documents.stream', doc.id)"
                                            muted preload="none" :ref="el => { if (el) videoRefs[doc.id] = el }"
                                            @loadedmetadata="e => e.target.currentTime = 1"/>
                                        <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30">
                                            <div class="w-10 h-10 rounded-full bg-background bg-opacity-80 flex items-center justify-center shadow">
                                                <svg class="w-5 h-5 text-foreground ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else-if="doc.file_type === 'audio'"
                                        class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-purple-600 to-purple-900">
                                        <svg class="w-10 h-10 text-white opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                        </svg>
                                        <span class="text-white text-xs mt-1 opacity-70 uppercase">{{ doc.file_extension }}</span>
                                    </div>
                                    <div v-else-if="doc.file_extension === 'pdf'"
                                        class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-red-500 to-red-700">
                                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="text-white text-xs mt-1 font-bold">PDF</span>
                                    </div>
                                    <div v-else class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-blue-500 to-blue-700">
                                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <span class="text-white text-xs mt-1 uppercase">{{ doc.file_extension }}</span>
                                    </div>
                                </div>

                                <!-- Corps de la carte -->
                                <div class="p-2.5 flex flex-col flex-1">
                                    <!-- Titre -->
                                    <p class="text-xs font-semibold text-foreground leading-snug mb-1.5 line-clamp-2">{{ doc.title }}</p>

                                    <!-- Tags colorés style Odoo -->
                                    <div class="flex flex-wrap gap-1 mb-2 flex-1">
                                        <span v-for="tag in getDocTags(doc)" :key="tag"
                                            class="text-xs px-1.5 py-0.5 rounded font-medium"
                                            :class="getTagColor(tag)">
                                            {{ tag }}
                                        </span>
                                    </div>

                                    <!-- Date + actions -->
                                    <div class="flex items-center justify-between mt-auto pt-1.5 border-t border-border">
                                        <span class="text-xs text-muted-foreground">
                                            {{ new Date(doc.created_at).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) }}
                                        </span>
                                        <!-- Actions rapides -->
                                        <div class="flex items-center gap-0.5" @click.stop>
                                            <button v-if="canUpload" @click="openEdit(doc)"
                                                class="p-1 text-muted-foreground hover:text-primary rounded transition" title="Modifier">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            <a v-if="canDownload" :href="route('documents.download', doc.id)" @click.stop
                                                class="p-1 text-muted-foreground hover:text-emerald-600 rounded transition" title="Télécharger">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                            </a>
                                            <button v-if="canDelete" @click="deleteDocument(doc)"
                                                class="p-1 text-muted-foreground hover:text-destructive rounded transition" title="Supprimer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                            <!-- Compteur DL -->
                                            <span class="flex items-center gap-0.5 text-xs text-muted-foreground ml-0.5">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                                {{ doc.download_count ?? 0 }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- État vide -->
                        <div v-else class="flex flex-col items-center justify-center py-32 text-muted-foreground">
                            <svg class="w-20 h-20 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                            <p class="font-medium text-base">Aucun document</p>
                            <p class="text-sm mt-1">Modifiez vos filtres ou ajoutez un document</p>
                            <button v-if="canUpload" @click="showUpload = true"
                                class="mt-4 flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg text-sm hover:bg-primary/90 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                Charger un document
                            </button>
                        </div>
                    </div>

                    <!-- ===== VUE LISTE ===== -->
                    <div v-else>
                        <div v-if="documents.data.length" class="bg-background rounded-lg border border-border overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-accent/30 border-b border-border">
                                    <tr>
                                        <th class="pl-3 pr-1 py-2.5 w-8">
                                            <span class="w-4 h-4 rounded border-2 flex items-center justify-center cursor-pointer transition"
                                                :class="selectedIds.length === documents.data.length && documents.data.length > 0 ? 'bg-primary border-primary' : 'border-input hover:border-primary/60'"
                                                @click="selectedIds.length === documents.data.length ? clearSelection() : selectAll()">
                                                <svg v-if="selectedIds.length === documents.data.length && documents.data.length > 0" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </span>
                                        </th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase">Document</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase hidden md:table-cell">Tags</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase hidden sm:table-cell">Année</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase hidden xl:table-cell">Taille</th>
                                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-muted-foreground uppercase">DL</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase hidden lg:table-cell">Date</th>
                                        <th class="px-4 py-2.5"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="doc in documents.data" :key="doc.id"
                                        class="hover:bg-accent/30 transition cursor-pointer"
                                        :class="isSelected(doc.id) ? 'bg-primary/5' : ''"
                                        @click="preview = doc">
                                        <!-- Checkbox liste -->
                                        <td class="pl-3 pr-1 py-2.5 w-8" @click.stop="toggleSelect(doc.id)">
                                            <span class="w-4 h-4 rounded border-2 flex items-center justify-center transition cursor-pointer"
                                                :class="isSelected(doc.id) ? 'bg-primary border-primary' : 'border-input hover:border-primary/60'">
                                                <svg v-if="isSelected(doc.id)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-8 h-8 rounded flex items-center justify-center flex-shrink-0"
                                                    :class="{
                                                        'bg-red-500/15': doc.file_extension === 'pdf',
                                                        'bg-emerald-500/15': doc.file_type === 'image',
                                                        'bg-purple-500/15': doc.file_type === 'video',
                                                        'bg-amber-500/15': doc.file_type === 'audio',
                                                        'bg-primary/10': doc.file_type === 'document' && doc.file_extension !== 'pdf',
                                                        'bg-accent/60': doc.file_type === 'other',
                                                    }">
                                                    <svg class="w-4 h-4"
                                                        :class="{
                                                            'text-destructive': doc.file_extension === 'pdf',
                                                            'text-emerald-600 dark:text-emerald-400': doc.file_type === 'image',
                                                            'text-purple-600 dark:text-purple-400': doc.file_type === 'video',
                                                            'text-amber-600 dark:text-amber-400': doc.file_type === 'audio',
                                                            'text-primary': doc.file_type === 'document' && doc.file_extension !== 'pdf',
                                                            'text-muted-foreground': doc.file_type === 'other',
                                                        }"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="font-medium text-foreground truncate max-w-xs text-sm">{{ doc.title }}</p>
                                                    <p class="text-xs text-muted-foreground uppercase">{{ doc.file_extension }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2.5 hidden md:table-cell">
                                            <div class="flex flex-wrap gap-1">
                                                <span v-for="tag in getDocTags(doc).slice(0, 3)" :key="tag"
                                                    class="text-xs px-1.5 py-0.5 rounded font-medium"
                                                    :class="getTagColor(tag)">
                                                    {{ tag }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2.5 hidden sm:table-cell text-foreground/80 text-sm">{{ doc.year }}</td>
                                        <td class="px-4 py-2.5 hidden xl:table-cell text-muted-foreground text-xs">{{ doc.file_size_formatted }}</td>
                                        <td class="px-4 py-2.5 text-center text-muted-foreground text-xs">{{ doc.download_count ?? 0 }}</td>
                                        <td class="px-4 py-2.5 hidden lg:table-cell text-muted-foreground text-xs">
                                            {{ new Date(doc.created_at).toLocaleDateString('fr-FR') }}
                                        </td>
                                        <td class="px-4 py-2.5" @click.stop>
                                            <div class="flex items-center gap-1 justify-end">
                                                <button v-if="canUpload" @click="openEdit(doc)"
                                                    class="p-1.5 text-muted-foreground hover:text-primary hover:bg-primary/5 rounded transition" title="Modifier">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </button>
                                                <a v-if="canDownload" :href="route('documents.download', doc.id)" @click.stop
                                                    class="p-1.5 text-muted-foreground hover:text-emerald-600 hover:bg-emerald-500/10 rounded transition" title="Télécharger">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                    </svg>
                                                </a>
                                                <button v-if="canDelete" @click="deleteDocument(doc)"
                                                    class="p-1.5 text-muted-foreground hover:text-destructive hover:bg-red-500/10 rounded transition" title="Supprimer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="flex flex-col items-center justify-center py-32 text-muted-foreground">
                            <svg class="w-16 h-16 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                            <p class="font-medium">Aucun document trouvé</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="documents.last_page > 1" class="mt-6 flex justify-center gap-1.5">
                        <a v-for="link in documents.links" :key="link.label"
                            :href="link.url" v-html="link.label"
                            class="px-3 py-1.5 rounded-lg text-sm border transition"
                            :class="link.active
                                ? 'bg-primary text-white border-primary'
                                : link.url
                                    ? 'bg-background text-foreground/80 border-border hover:border-primary/50'
                                    : 'bg-background text-muted-foreground border-border cursor-default'"
                            @click.prevent="link.url && router.get(link.url)"/>
                    </div>

                </div>
            </div>
        </div>

        <!-- ===== MODAL PRÉVISUALISATION ===== -->
        <div v-if="preview" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-70" @click.self="preview = null">
            <button v-if="previewIndex > 0" @click="previewPrev"
                class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-background bg-opacity-90 rounded-full flex items-center justify-center shadow hover:bg-opacity-100 transition z-10">
                <svg class="w-5 h-5 text-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button v-if="previewIndex < documents.data.length - 1" @click="previewNext"
                class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-background bg-opacity-90 rounded-full flex items-center justify-center shadow hover:bg-opacity-100 transition z-10">
                <svg class="w-5 h-5 text-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <div class="bg-background rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-5 border-b">
                    <div class="flex-1 min-w-0 mr-4">
                        <h3 class="font-bold text-foreground text-lg truncate">{{ preview.title }}</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">{{ previewIndex + 1 }} / {{ documents.data.length }}</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button v-if="canUpload" @click="openEdit(preview)"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-sm text-primary bg-primary/5 rounded-lg hover:bg-primary/10 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Modifier
                        </button>
                        <button @click="preview = null" class="text-muted-foreground hover:text-foreground/80">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-5">
                    <div v-if="preview.file_type === 'image'" class="mb-4 rounded-xl overflow-hidden bg-accent/60">
                        <img :src="`/storage/${preview.file_path}`" :alt="preview.title" class="w-full max-h-96 object-contain"/>
                    </div>
                    <div v-else-if="preview.file_type === 'video'" class="mb-4 rounded-xl overflow-hidden bg-black">
                        <video controls preload="metadata" class="w-full max-h-96" :src="route('documents.stream', preview.id)">
                            Votre navigateur ne supporte pas la lecture vidéo.
                        </video>
                    </div>
                    <div v-else-if="preview.file_type === 'audio'" class="mb-4 rounded-xl bg-accent/30 p-6 flex flex-col items-center">
                        <span class="text-6xl mb-4">🎵</span>
                        <audio controls preload="metadata" class="w-full" :src="route('documents.stream', preview.id)"/>
                    </div>
                    <div v-else-if="preview.file_extension === 'pdf'" class="mb-4 rounded-xl overflow-hidden bg-accent/60">
                        <iframe :src="`${route('documents.stream', preview.id)}#toolbar=0&navpanes=0&scrollbar=0`" class="w-full h-96 border-0"></iframe>
                    </div>
                    <div v-else class="mb-4 flex items-center justify-center bg-accent/30 rounded-xl h-40">
                        <div class="text-center">
                            <p class="text-muted-foreground text-sm uppercase mt-2">{{ preview.file_extension }}</p>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div class="flex flex-wrap gap-1.5 mb-4">
                        <span v-for="tag in getDocTags(preview)" :key="tag"
                            class="text-xs px-2 py-0.5 rounded-full font-medium"
                            :class="getTagColor(tag)">
                            {{ tag }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-sm mb-4">
                        <div><span class="text-muted-foreground">Entité :</span> <span class="font-medium">{{ preview.entity?.name }}</span></div>
                        <div><span class="text-muted-foreground">Filiale :</span> <span class="font-medium">{{ preview.filiale?.name || '—' }}</span></div>
                        <div><span class="text-muted-foreground">Catégorie :</span> <span class="font-medium">{{ preview.category?.name }}</span></div>
                        <div><span class="text-muted-foreground">Type :</span> <span class="font-medium">{{ preview.subcategory?.name || '—' }}</span></div>
                        <div><span class="text-muted-foreground">Année :</span> <span class="font-medium">{{ preview.year }}</span></div>
                        <div><span class="text-muted-foreground">Téléchargements :</span> <span class="font-medium">{{ preview.download_count ?? 0 }}</span></div>
                    </div>

                    <p v-if="preview.description" class="text-foreground/80 text-sm mb-4">{{ preview.description }}</p>

                    <div class="flex gap-3">
                        <a v-if="canDownload" :href="route('documents.download', preview.id)"
                            class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Télécharger
                        </a>
                        <button v-if="canDelete" @click="deleteDocument(preview); preview = null"
                            class="flex items-center gap-2 px-4 py-2 bg-red-500/10 text-destructive rounded-lg hover:bg-red-500/15 transition text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== MODAL ÉDITION ===== -->
        <div v-if="showEdit" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60" @click.self="showEdit = false">
            <div class="bg-background rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-5 border-b">
                    <h3 class="font-bold text-foreground">Modifier le document</h3>
                    <button @click="showEdit = false" class="text-muted-foreground hover:text-foreground/80">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitEdit" class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Titre *</label>
                        <input v-model="editForm.title" type="text" required
                            class="w-full border border-input rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none"/>
                        <p v-if="editErrors.title" class="text-destructive text-xs mt-1">{{ editErrors.title }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Description</label>
                        <textarea v-model="editForm.description" rows="2"
                            class="w-full border border-input rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none resize-none"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Entité *</label>
                            <select v-model="editForm.entity_id" required class="w-full border border-input rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none">
                                <option value="">Sélectionner</option>
                                <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Filiale</label>
                            <select v-model="editForm.filiale_id" class="w-full border border-input rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none">
                                <option value="">Aucune</option>
                                <option v-for="f in filiales" :key="f.id" :value="f.id">{{ f.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Catégorie *</label>
                            <select v-model="editForm.category_id" required @change="editForm.subcategory_id = ''"
                                class="w-full border border-input rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none">
                                <option value="">Sélectionner</option>
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Type</label>
                            <select v-model="editForm.subcategory_id" :disabled="!editSubcategories.length"
                                class="w-full border border-input rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none disabled:opacity-50">
                                <option value="">Aucun</option>
                                <option v-for="s in editSubcategories" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Année *</label>
                            <input v-model="editForm.year" type="number" min="2000" max="2099" required
                                class="w-full border border-input rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none"/>
                        </div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="isEditing"
                            class="flex-1 py-2.5 bg-primary text-white rounded-lg hover:bg-primary/90 transition text-sm font-medium disabled:opacity-50 flex items-center justify-center gap-2">
                            <svg v-if="isEditing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ isEditing ? 'Enregistrement...' : 'Enregistrer' }}
                        </button>
                        <button type="button" @click="showEdit = false" :disabled="isEditing"
                            class="px-4 py-2 border border-border text-foreground/80 rounded-lg hover:bg-accent/30 transition text-sm disabled:opacity-50">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===== BARRE D'ACTION SÉLECTION MULTIPLE ===== -->
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="translate-y-4 opacity-0" enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in" leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y-4 opacity-0">
            <div v-if="selectedIds.length > 0 && canDownload"
                class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-foreground text-white rounded-2xl shadow-2xl px-5 py-3 flex items-center gap-4">
                <span class="text-sm font-medium">
                    {{ selectedIds.length }} document{{ selectedIds.length > 1 ? 's' : '' }} sélectionné{{ selectedIds.length > 1 ? 's' : '' }}
                </span>
                <div class="w-px h-5 bg-muted-foreground"/>
                <button @click="selectAll" class="text-sm text-muted-foreground hover:text-white transition">
                    Tout sélectionner ({{ documents.data.length }})
                </button>
                <div class="w-px h-5 bg-muted-foreground"/>
                <button @click="downloadZip" :disabled="isDownloadingZip"
                    class="flex items-center gap-2 px-4 py-1.5 bg-primary hover:bg-blue-500 text-white rounded-lg text-sm font-medium transition disabled:opacity-60">
                    <svg v-if="isDownloadingZip" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    {{ isDownloadingZip ? 'Préparation...' : 'Télécharger ZIP' }}
                </button>
                <button @click="clearSelection" class="text-muted-foreground hover:text-white transition ml-1" title="Annuler la sélection">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </Transition>

        <!-- ===== MODAL UPLOAD ===== -->
        <div v-if="showUpload" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60" @click.self="showUpload = false">
            <div class="bg-background rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-5 border-b">
                    <h3 class="font-bold text-foreground">Charger un document</h3>
                    <button @click="showUpload = false" class="text-muted-foreground hover:text-foreground/80">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitUpload" class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Titre *</label>
                        <input v-model="uploadTitle" type="text" required
                            class="w-full border border-input rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none"/>
                        <p v-if="uploadErrors.title" class="text-destructive text-xs mt-1">{{ uploadErrors.title }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Description</label>
                        <textarea v-model="uploadDescription" rows="2"
                            class="w-full border border-input rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none resize-none"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Entité *</label>
                            <select v-model="uploadEntityId" required class="w-full border border-input rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none">
                                <option value="">Sélectionner</option>
                                <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.name }}</option>
                            </select>
                            <p v-if="uploadErrors.entity_id" class="text-destructive text-xs mt-1">{{ uploadErrors.entity_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Filiale</label>
                            <select v-model="uploadFilialeId" class="w-full border border-input rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none">
                                <option value="">Aucune</option>
                                <option v-for="f in filiales" :key="f.id" :value="f.id">{{ f.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Catégorie *</label>
                            <select v-model="uploadCategoryId" required @change="uploadSubcategoryId = ''"
                                class="w-full border border-input rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none">
                                <option value="">Sélectionner</option>
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                            <p v-if="uploadErrors.category_id" class="text-destructive text-xs mt-1">{{ uploadErrors.category_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Type</label>
                            <select v-model="uploadSubcategoryId" :disabled="!uploadSubcategories.length"
                                class="w-full border border-input rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none disabled:opacity-50">
                                <option value="">Aucun</option>
                                <option v-for="s in uploadSubcategories" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Année *</label>
                            <input v-model="uploadYear" type="number" min="2000" max="2099" required
                                class="w-full border border-input rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none"/>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Fichier *</label>
                        <label class="flex flex-col items-center justify-center w-full border-2 border-dashed border-input rounded-xl cursor-pointer hover:border-primary/60 hover:bg-primary/5 transition p-4"
                            :class="selectedFileName ? 'border-primary/60 bg-primary/5' : ''">
                            <input type="file" required class="hidden" @change="onFileChange"/>
                            <svg v-if="!selectedFileName" class="w-8 h-8 text-muted-foreground mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            <svg v-else class="w-8 h-8 text-primary mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span v-if="!selectedFileName" class="text-sm text-muted-foreground">Cliquer pour choisir un fichier</span>
                            <span v-else class="text-sm font-medium text-primary text-center truncate max-w-full">{{ selectedFileName }}</span>
                            <span v-if="selectedFileSize" class="text-xs text-muted-foreground mt-1">{{ selectedFileSize }}</span>
                        </label>
                        <p v-if="uploadErrors.file" class="text-destructive text-xs mt-1">{{ uploadErrors.file }}</p>
                    </div>
                    <div v-if="isUploading" class="space-y-1">
                        <div class="flex justify-between text-xs text-muted-foreground">
                            <span>Envoi en cours...</span>
                            <span>{{ uploadProgress }}%</span>
                        </div>
                        <div class="w-full bg-accent rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full transition-all" :style="{ width: uploadProgress + '%' }"></div>
                        </div>
                        <p class="text-xs text-muted-foreground text-center">Ne fermez pas cette fenêtre</p>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="isUploading || !selectedFileName"
                            class="flex-1 py-2.5 bg-primary text-white rounded-lg hover:bg-primary/90 transition text-sm font-medium disabled:opacity-50 flex items-center justify-center gap-2">
                            <svg v-if="isUploading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ isUploading ? `Envoi ${uploadProgress}%` : 'Charger' }}
                        </button>
                        <button type="button" @click="showUpload = false" :disabled="isUploading"
                            class="px-4 py-2 border border-border text-foreground/80 rounded-lg hover:bg-accent/30 transition text-sm disabled:opacity-50">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===== MODAL DEMANDE DE DOCUMENT ===== -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition duration-150"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showRequest" class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    @click.self="showRequest = false">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

                    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150"
                        leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                        <div v-if="showRequest"
                            class="relative bg-background rounded-2xl shadow-2xl w-full max-w-md border border-border">

                            <!-- Header -->
                            <div class="flex items-center justify-between px-6 py-4 border-b border-border">
                                <div class="flex items-center gap-3">
                                    <div class="size-9 rounded-xl bg-primary/10 flex items-center justify-center">
                                        <i class="ki-filled ki-file-added text-primary text-base"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-foreground text-sm">Demander un document</h3>
                                        <p class="text-xs text-muted-foreground">L'équipe DMC recevra votre demande par email</p>
                                    </div>
                                </div>
                                <button @click="showRequest = false"
                                    class="size-8 rounded-lg flex items-center justify-center text-muted-foreground hover:bg-accent/60 transition">
                                    <i class="ki-filled ki-cross text-base"></i>
                                </button>
                            </div>

                            <form @submit.prevent="submitRequest" class="p-6 space-y-4">

                                <!-- Titre -->
                                <div>
                                    <label class="block text-sm font-medium text-foreground mb-1.5">Titre du document *</label>
                                    <input v-model="requestForm.title" type="text" required
                                        placeholder="Ex: Rapport d'activité 2024 Burkina Faso"
                                        class="w-full border border-input rounded-lg px-3.5 py-2.5 text-sm bg-background text-foreground
                                               placeholder:text-muted-foreground focus:ring-2 focus:ring-primary focus:border-primary outline-none transition"/>
                                    <p v-if="requestForm.errors.title" class="text-destructive text-xs mt-1">{{ requestForm.errors.title }}</p>
                                </div>

                                <!-- Description -->
                                <div>
                                    <label class="block text-sm font-medium text-foreground mb-1.5">Description <span class="text-muted-foreground font-normal">(optionnel)</span></label>
                                    <textarea v-model="requestForm.description" rows="2"
                                        placeholder="Précisez votre besoin..."
                                        class="w-full border border-input rounded-lg px-3.5 py-2.5 text-sm bg-background text-foreground
                                               placeholder:text-muted-foreground focus:ring-2 focus:ring-primary focus:border-primary outline-none transition resize-none"></textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <!-- Catégorie -->
                                    <div>
                                        <label class="block text-sm font-medium text-foreground mb-1.5">Catégorie</label>
                                        <input v-model="requestForm.category" type="text" placeholder="Ex: Graphisme"
                                            class="w-full border border-input rounded-lg px-3.5 py-2.5 text-sm bg-background text-foreground
                                                   placeholder:text-muted-foreground focus:ring-2 focus:ring-primary outline-none transition"/>
                                    </div>
                                    <!-- Période -->
                                    <div>
                                        <label class="block text-sm font-medium text-foreground mb-1.5">Période</label>
                                        <input v-model="requestForm.period" type="text" placeholder="Ex: 2024, T1 2025"
                                            class="w-full border border-input rounded-lg px-3.5 py-2.5 text-sm bg-background text-foreground
                                                   placeholder:text-muted-foreground focus:ring-2 focus:ring-primary outline-none transition"/>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex gap-3 pt-1">
                                    <button type="submit" :disabled="requestForm.processing"
                                        class="flex-1 py-2.5 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90
                                               transition text-sm font-medium disabled:opacity-60 flex items-center justify-center gap-2">
                                        <svg v-if="requestForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                        </svg>
                                        {{ requestForm.processing ? 'Envoi...' : 'Envoyer la demande' }}
                                    </button>
                                    <button type="button" @click="showRequest = false"
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
