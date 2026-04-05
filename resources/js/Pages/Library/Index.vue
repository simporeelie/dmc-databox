<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    documents: Object,
    entities: Array,
    filiales: Array,
    categories: Array,
    years: Array,
    filters: Object,
});

const page = usePage();
const user = page.props.auth.user;
const canUpload = ['admin', 'dmc'].includes(user.role);
const canDelete = ['admin', 'dmc', 'rmc'].includes(user.role);

// Filtres actifs
const activeFilters = ref({ ...props.filters });
const search = ref(props.filters.search || '');

// Sous-catégories disponibles selon la catégorie sélectionnée
const availableSubcategories = computed(() => {
    if (!activeFilters.value.category_id) return [];
    const cat = props.categories.find(c => c.id == activeFilters.value.category_id);
    return cat ? cat.subcategories : [];
});

const applyFilters = () => {
    router.get(route('library.index'), {
        ...activeFilters.value,
        search: search.value || undefined,
    }, { preserveState: true, replace: true });
};

const clearFilter = (key) => {
    activeFilters.value[key] = null;
    if (key === 'category_id') activeFilters.value.subcategory_id = null;
    applyFilters();
};

const clearAll = () => {
    activeFilters.value = {};
    search.value = '';
    applyFilters();
};

watch(activeFilters, applyFilters, { deep: true });

let searchTimer;
const onSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
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

const CHUNK_SIZE = 5 * 1024 * 1024; // 5 MB

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
            const chunk = file.slice(start, end);

            const fd = new FormData();
            fd.append('chunk', chunk, file.name);
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

            const res = await fetch(route('documents.chunk'), {
                method: 'POST',
                headers,
                body: fd,
            });

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                uploadErrors.value = data.errors || { file: 'Erreur lors de l\'envoi.' };
                isUploading.value = false;
                return;
            }

            uploadProgress.value = Math.round(((i + 1) / totalChunks) * 100);
        }

        // Succès — recharger les documents
        showUpload.value = false;
        isUploading.value = false;
        uploadProgress.value = 0;
        uploadTitle.value = '';
        uploadDescription.value = '';
        uploadEntityId.value = '';
        uploadFilialeId.value = '';
        uploadCategoryId.value = '';
        uploadSubcategoryId.value = '';
        uploadYear.value = new Date().getFullYear();
        uploadFile.value = null;
        selectedFileName.value = '';
        selectedFileSize.value = '';
        router.reload({ only: ['documents'] });

    } catch (e) {
        uploadErrors.value = { file: 'Erreur réseau. Veuillez réessayer.' };
        isUploading.value = false;
    }
};

// Lazy load thumbnails vidéo
const videoRefs = ref({});
let observer = null;

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const video = entry.target;
                const src = video.getAttribute('data-src');
                if (src && !video.src) {
                    video.src = src;
                    video.load();
                }
                observer.unobserve(video);
            }
        });
    }, { threshold: 0.1 });
});

watch(() => props.documents.data, () => {
    setTimeout(() => {
        Object.values(videoRefs.value).forEach(video => {
            if (video && observer) observer.observe(video);
        });
    }, 100);
}, { immediate: true });

onUnmounted(() => { if (observer) observer.disconnect(); });

// Prévisualisation
const preview = ref(null);

const deleteDocument = (doc) => {
    if (!confirm(`Supprimer "${doc.title}" ?`)) return;
    router.delete(route('documents.destroy', doc.id));
};

const getFileIcon = (type) => {
    const icons = {
        image: '🖼️',
        video: '🎬',
        audio: '🎵',
        document: '📄',
        other: '📁',
    };
    return icons[type] || '📁';
};

const activeFilterCount = computed(() => {
    return Object.values(activeFilters.value).filter(v => v).length + (search.value ? 1 : 0);
});
</script>

<template>
    <Head title="Bibliothèque — DMC DataBox" />
    <AppLayout>

        <!-- En-tête -->
        <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Bibliothèque</h1>
                <p class="text-gray-500 text-sm mt-1">
                    {{ documents.total }} document{{ documents.total > 1 ? 's' : '' }}
                    <span v-if="activeFilterCount"> · {{ activeFilterCount }} filtre{{ activeFilterCount > 1 ? 's' : '' }} actif{{ activeFilterCount > 1 ? 's' : '' }}</span>
                </p>
            </div>
            <button
                v-if="canUpload"
                @click="showUpload = true"
                class="flex items-center gap-2 px-4 py-2 bg-blue-800 text-white rounded-lg hover:bg-blue-900 transition text-sm font-medium"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Ajouter un document
            </button>
        </div>

        <!-- Barre de recherche -->
        <div class="mb-4">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    v-model="search"
                    @input="onSearch"
                    type="text"
                    placeholder="Rechercher un document..."
                    class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                />
            </div>
        </div>

        <!-- Filtres -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-semibold text-gray-700">Filtres</span>
                <button v-if="activeFilterCount" @click="clearAll" class="text-xs text-red-500 hover:text-red-700">
                    Tout effacer
                </button>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                <!-- Entité -->
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Entité</label>
                    <select v-model="activeFilters.entity_id"
                        class="w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-500 outline-none">
                        <option :value="null">Toutes</option>
                        <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.name }}</option>
                    </select>
                </div>
                <!-- Filiale -->
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Filiale</label>
                    <select v-model="activeFilters.filiale_id"
                        class="w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-500 outline-none">
                        <option :value="null">Toutes</option>
                        <option v-for="f in filiales" :key="f.id" :value="f.id">{{ f.name }}</option>
                    </select>
                </div>
                <!-- Catégorie -->
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Catégorie</label>
                    <select v-model="activeFilters.category_id"
                        @change="activeFilters.subcategory_id = null"
                        class="w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-500 outline-none">
                        <option :value="null">Toutes</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <!-- Sous-catégorie -->
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Type</label>
                    <select v-model="activeFilters.subcategory_id"
                        :disabled="!availableSubcategories.length"
                        class="w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-500 outline-none disabled:opacity-50">
                        <option :value="null">Tous</option>
                        <option v-for="s in availableSubcategories" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <!-- Année -->
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Année</label>
                    <select v-model="activeFilters.year"
                        class="w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-500 outline-none">
                        <option :value="null">Toutes</option>
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Grille de documents -->
        <div v-if="documents.data.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            <div
                v-for="doc in documents.data"
                :key="doc.id"
                class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 group cursor-pointer"
                @click="preview = doc"
            >
                <!-- Thumbnail -->
                <div class="relative bg-gray-100 overflow-hidden" style="height: 160px;">
                    <!-- Image -->
                    <img
                        v-if="doc.file_type === 'image' && doc.thumbnail_path"
                        :src="`/storage/${doc.thumbnail_path}`"
                        :alt="doc.title"
                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                    />
                    <!-- Vidéo — aperçu première frame -->
                    <div v-else-if="doc.file_type === 'video'" class="w-full h-full relative bg-gray-900">
                        <video
                            class="w-full h-full object-cover"
                            :data-src="route('documents.stream', doc.id)"
                            muted
                            preload="none"
                            :ref="el => { if (el) videoRefs[doc.id] = el }"
                            @loadedmetadata="e => e.target.currentTime = 1"
                        />
                        <!-- Overlay play -->
                        <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30 group-hover:bg-opacity-10 transition">
                            <div class="w-10 h-10 rounded-full bg-white bg-opacity-80 flex items-center justify-center shadow">
                                <svg class="w-5 h-5 text-gray-800 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <!-- Audio -->
                    <div v-else-if="doc.file_type === 'audio'"
                        class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-purple-700 to-purple-900">
                        <svg class="w-12 h-12 text-white opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                        </svg>
                        <span class="text-white text-xs mt-2 opacity-60 uppercase tracking-wider">{{ doc.file_extension }}</span>
                    </div>
                    <!-- PDF -->
                    <div v-else-if="doc.file_extension === 'pdf'"
                        class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-red-500 to-red-700">
                        <svg class="w-12 h-12 text-white opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-white text-xs mt-2 opacity-80 font-bold uppercase">PDF</span>
                    </div>
                    <!-- Autres documents -->
                    <div v-else
                        class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-blue-500 to-blue-700">
                        <svg class="w-12 h-12 text-white opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="text-white text-xs mt-2 opacity-80 uppercase">{{ doc.file_extension }}</span>
                    </div>

                    <!-- Badge type en haut à droite -->
                    <span class="absolute top-2 right-2 text-xs font-medium px-2 py-0.5 rounded-full bg-black bg-opacity-40 text-white uppercase tracking-wide">
                        {{ doc.file_extension }}
                    </span>
                </div>

                <!-- Info -->
                <div class="p-3">
                    <p class="text-sm font-semibold text-gray-800 truncate leading-tight">{{ doc.title }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ doc.year }}</p>
                    <div class="flex flex-wrap gap-1 mt-2">
                        <span class="text-xs bg-blue-50 text-blue-700 rounded-full px-2 py-0.5 font-medium truncate max-w-full">
                            {{ doc.entity?.name }}
                        </span>
                    </div>
                    <div v-if="doc.filiale" class="mt-1">
                        <span class="text-xs text-gray-400 truncate block">{{ doc.filiale?.name }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- État vide -->
        <div v-else class="text-center py-24 text-gray-400">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="font-medium">Aucun document trouvé</p>
            <p class="text-sm mt-1">Essayez de modifier vos filtres</p>
        </div>

        <!-- Pagination -->
        <div v-if="documents.last_page > 1" class="mt-8 flex justify-center gap-2">
            <a
                v-for="link in documents.links"
                :key="link.label"
                :href="link.url"
                v-html="link.label"
                class="px-3 py-1 rounded-lg text-sm border transition"
                :class="link.active
                    ? 'bg-blue-800 text-white border-blue-800'
                    : link.url
                        ? 'bg-white text-gray-600 border-gray-200 hover:border-blue-300'
                        : 'bg-white text-gray-300 border-gray-100 cursor-default'"
                @click.prevent="link.url && router.get(link.url)"
            />
        </div>

        <!-- Modal Prévisualisation -->
        <div v-if="preview" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-70"
            @click.self="preview = null">
            <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-5 border-b">
                    <h3 class="font-bold text-gray-800 text-lg">{{ preview.title }}</h3>
                    <button @click="preview = null" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-5">
                    <!-- Image -->
                    <div v-if="preview.file_type === 'image'" class="mb-4 rounded-xl overflow-hidden bg-gray-100">
                        <img :src="`/storage/${preview.file_path}`" :alt="preview.title" class="w-full max-h-96 object-contain" />
                    </div>
                    <!-- Vidéo -->
                    <div v-else-if="preview.file_type === 'video'" class="mb-4 rounded-xl overflow-hidden bg-black">
                        <video
                            controls
                            preload="metadata"
                            class="w-full max-h-96"
                            :src="route('documents.stream', preview.id)"
                        >
                            Votre navigateur ne supporte pas la lecture vidéo.
                        </video>
                    </div>
                    <!-- Audio -->
                    <div v-else-if="preview.file_type === 'audio'" class="mb-4 rounded-xl bg-gray-50 p-6 flex flex-col items-center">
                        <span class="text-6xl mb-4">🎵</span>
                        <audio
                            controls
                            preload="metadata"
                            class="w-full"
                            :src="route('documents.stream', preview.id)"
                        >
                            Votre navigateur ne supporte pas la lecture audio.
                        </audio>
                    </div>
                    <!-- PDF -->
                    <div v-else-if="preview.file_extension === 'pdf'" class="mb-4 rounded-xl overflow-hidden bg-gray-100">
                        <iframe :src="`/storage/${preview.file_path}`" class="w-full h-96 border-0"></iframe>
                    </div>
                    <!-- Autres types -->
                    <div v-else class="mb-4 flex items-center justify-center bg-gray-50 rounded-xl h-40">
                        <div class="text-center">
                            <span class="text-6xl">{{ getFileIcon(preview.file_type) }}</span>
                            <p class="text-gray-500 text-sm mt-2 uppercase">{{ preview.file_extension }}</p>
                        </div>
                    </div>

                    <!-- Métadonnées -->
                    <div class="grid grid-cols-2 gap-3 text-sm mb-5">
                        <div><span class="text-gray-400">Entité :</span> <span class="font-medium">{{ preview.entity?.name }}</span></div>
                        <div><span class="text-gray-400">Filiale :</span> <span class="font-medium">{{ preview.filiale?.name || '—' }}</span></div>
                        <div><span class="text-gray-400">Catégorie :</span> <span class="font-medium">{{ preview.category?.name }}</span></div>
                        <div><span class="text-gray-400">Type :</span> <span class="font-medium">{{ preview.subcategory?.name || '—' }}</span></div>
                        <div><span class="text-gray-400">Année :</span> <span class="font-medium">{{ preview.year }}</span></div>
                    </div>

                    <p v-if="preview.description" class="text-gray-600 text-sm mb-5">{{ preview.description }}</p>

                    <div class="flex gap-3">
                        <a
                            :href="route('documents.download', preview.id)"
                            class="flex items-center gap-2 px-4 py-2 bg-blue-800 text-white rounded-lg hover:bg-blue-900 transition text-sm font-medium"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Télécharger
                        </a>
                        <button
                            v-if="canDelete"
                            @click="deleteDocument(preview); preview = null"
                            class="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition text-sm font-medium"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Upload -->
        <div v-if="showUpload" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60"
            @click.self="showUpload = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-5 border-b">
                    <h3 class="font-bold text-gray-800">Ajouter un document</h3>
                    <button @click="showUpload = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitUpload" class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Titre *</label>
                        <input v-model="uploadTitle" type="text" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                        <p v-if="uploadErrors.title" class="text-red-500 text-xs mt-1">{{ uploadErrors.title }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea v-model="uploadDescription" rows="2"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Entité *</label>
                            <select v-model="uploadEntityId" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">Sélectionner</option>
                                <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.name }}</option>
                            </select>
                            <p v-if="uploadErrors.entity_id" class="text-red-500 text-xs mt-1">{{ uploadErrors.entity_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filiale</label>
                            <select v-model="uploadFilialeId"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">Aucune</option>
                                <option v-for="f in filiales" :key="f.id" :value="f.id">{{ f.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie *</label>
                            <select v-model="uploadCategoryId" required
                                @change="uploadSubcategoryId = ''"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">Sélectionner</option>
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                            <p v-if="uploadErrors.category_id" class="text-red-500 text-xs mt-1">{{ uploadErrors.category_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select v-model="uploadSubcategoryId" :disabled="!uploadSubcategories.length"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none disabled:opacity-50">
                                <option value="">Aucun</option>
                                <option v-for="s in uploadSubcategories" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Année *</label>
                            <input v-model="uploadYear" type="number" min="2000" max="2099" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                        </div>
                    </div>
                    <!-- Zone fichier -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fichier *</label>
                        <label class="flex flex-col items-center justify-center w-full border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition p-4"
                            :class="selectedFileName ? 'border-blue-400 bg-blue-50' : ''">
                            <input type="file" required class="hidden" @change="onFileChange" />
                            <svg v-if="!selectedFileName" class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            <svg v-else class="w-8 h-8 text-blue-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span v-if="!selectedFileName" class="text-sm text-gray-500">Cliquer pour choisir un fichier</span>
                            <span v-else class="text-sm font-medium text-blue-700 text-center truncate max-w-full">{{ selectedFileName }}</span>
                            <span v-if="selectedFileSize" class="text-xs text-gray-400 mt-1">{{ selectedFileSize }}</span>
                        </label>
                        <p v-if="uploadErrors.file" class="text-red-500 text-xs mt-1">{{ uploadErrors.file }}</p>
                    </div>

                    <!-- Barre de progression -->
                    <div v-if="isUploading" class="space-y-1">
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>Envoi en cours... ({{ uploadProgress < 100 ? 'chunk ' + Math.ceil(uploadProgress / 100 * Math.ceil(uploadFile?.size / (5*1024*1024))) + '/' + Math.ceil(uploadFile?.size / (5*1024*1024)) : 'finalisation' }})</span>
                            <span>{{ uploadProgress }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                                :style="{ width: uploadProgress + '%' }"></div>
                        </div>
                        <p class="text-xs text-gray-400 text-center">Ne fermez pas cette fenêtre</p>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="isUploading || !selectedFileName"
                            class="flex-1 py-2.5 bg-blue-800 text-white rounded-lg hover:bg-blue-900 transition text-sm font-medium disabled:opacity-50 flex items-center justify-center gap-2">
                            <svg v-if="isUploading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ isUploading ? `Envoi ${uploadProgress}%` : 'Ajouter' }}
                        </button>
                        <button type="button" @click="showUpload = false" :disabled="isUploading"
                            class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition text-sm disabled:opacity-50">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AppLayout>
</template>
