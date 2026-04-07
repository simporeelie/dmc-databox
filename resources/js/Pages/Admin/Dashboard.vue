<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    stats: Object,
    by_type: Object,
    by_year: Array,
    by_entity: Array,
    recent_documents: Array,
    top_downloaded: Array,
});

const formatSize = (bytes) => {
    if (!bytes) return '0 B';
    if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
    if (bytes >= 1048576)    return (bytes / 1048576).toFixed(2) + ' MB';
    if (bytes >= 1024)       return (bytes / 1024).toFixed(2) + ' KB';
    return bytes + ' B';
};

const typeColors = {
    document: 'bg-red-100 text-red-700',
    image:    'bg-green-100 text-green-700',
    video:    'bg-purple-100 text-purple-700',
    audio:    'bg-yellow-100 text-yellow-700',
    other:    'bg-accent text-foreground/80',
};

const typeLabels = {
    document: 'Documents',
    image:    'Images',
    video:    'Vidéos',
    audio:    'Audios',
    other:    'Autres',
};

const typeIcons = {
    document: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    image:    'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
    video:    'M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
    audio:    'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3',
    other:    'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
};

const maxByEntity = computed(() => Math.max(...(props.by_entity?.map(e => e.count) ?? [1])));
const maxByYear = computed(() => Math.max(...(props.by_year?.map(y => y.count) ?? [1])));
</script>

<template>
    <Head title="Dashboard — Admin" />
    <AppLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Dashboard</h1>
            <p class="text-muted-foreground text-sm mt-1">Vue d'ensemble de la plateforme</p>
        </div>

        <!-- Cartes statistiques principales -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-background rounded-xl border border-border p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-muted-foreground">Documents</span>
                    <div class="w-9 h-9 rounded-lg bg-blue-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-bold text-foreground">{{ stats.documents.toLocaleString() }}</div>
                <div class="text-xs text-muted-foreground mt-1">{{ formatSize(stats.total_size) }} utilisés</div>
            </div>

            <div class="bg-background rounded-xl border border-border p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-muted-foreground">Téléchargements</span>
                    <div class="w-9 h-9 rounded-lg bg-green-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-bold text-foreground">{{ stats.downloads.toLocaleString() }}</div>
                <div class="text-xs text-muted-foreground mt-1">Total cumulé</div>
            </div>

            <div class="bg-background rounded-xl border border-border p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-muted-foreground">Utilisateurs</span>
                    <div class="w-9 h-9 rounded-lg bg-purple-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-bold text-foreground">{{ stats.active_users }}</div>
                <div class="text-xs text-muted-foreground mt-1">{{ stats.users }} au total</div>
            </div>

            <div class="bg-background rounded-xl border border-border p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-muted-foreground">Entités / Filiales</span>
                    <div class="w-9 h-9 rounded-lg bg-orange-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-bold text-foreground">{{ stats.entities }}</div>
                <div class="text-xs text-muted-foreground mt-1">{{ stats.filiales }} filiales</div>
            </div>
        </div>

        <!-- Grille 2 colonnes -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Documents par type -->
            <div class="bg-background rounded-xl border border-border p-5">
                <h2 class="font-semibold text-foreground mb-4">Répartition par type</h2>
                <div class="space-y-3">
                    <div v-for="(count, type) in by_type" :key="type" class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                            :class="typeColors[type] || 'bg-accent text-foreground/80'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="typeIcons[type] || typeIcons.other"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-foreground">{{ typeLabels[type] || type }}</span>
                                <span class="text-muted-foreground">{{ count }}</span>
                            </div>
                            <div class="w-full bg-accent/60 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full bg-blue-500 transition-all"
                                    :style="{ width: stats.documents ? (count / stats.documents * 100) + '%' : '0%' }"/>
                            </div>
                        </div>
                    </div>
                    <div v-if="!Object.keys(by_type).length" class="text-sm text-muted-foreground text-center py-4">
                        Aucun document
                    </div>
                </div>
            </div>

            <!-- Documents par année -->
            <div class="bg-background rounded-xl border border-border p-5">
                <h2 class="font-semibold text-foreground mb-4">Documents par année</h2>
                <div class="space-y-3">
                    <div v-for="row in by_year" :key="row.year" class="flex items-center gap-3">
                        <span class="text-sm font-medium text-muted-foreground w-12 flex-shrink-0">{{ row.year }}</span>
                        <div class="flex-1">
                            <div class="w-full bg-accent/60 rounded-full h-2">
                                <div class="h-2 rounded-full bg-blue-600 transition-all"
                                    :style="{ width: maxByYear ? (row.count / maxByYear * 100) + '%' : '0%' }"/>
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-foreground w-8 text-right">{{ row.count }}</span>
                    </div>
                    <div v-if="!by_year?.length" class="text-sm text-muted-foreground text-center py-4">
                        Aucun document
                    </div>
                </div>
            </div>

            <!-- Documents par entité -->
            <div class="bg-background rounded-xl border border-border p-5">
                <h2 class="font-semibold text-foreground mb-4">Top entités</h2>
                <div class="space-y-3">
                    <div v-for="(row, i) in by_entity" :key="i" class="flex items-center gap-3">
                        <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center flex-shrink-0">{{ i + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-foreground truncate">{{ row.name }}</span>
                                <span class="text-muted-foreground ml-2 flex-shrink-0">{{ row.count }}</span>
                            </div>
                            <div class="w-full bg-accent/60 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full bg-indigo-500 transition-all"
                                    :style="{ width: maxByEntity ? (row.count / maxByEntity * 100) + '%' : '0%' }"/>
                            </div>
                        </div>
                    </div>
                    <div v-if="!by_entity?.length" class="text-sm text-muted-foreground text-center py-4">
                        Aucun document
                    </div>
                </div>
            </div>

            <!-- Top téléchargements -->
            <div class="bg-background rounded-xl border border-border p-5">
                <h2 class="font-semibold text-foreground mb-4">Top téléchargements</h2>
                <div class="space-y-3">
                    <div v-for="(doc, i) in top_downloaded" :key="doc.id" class="flex items-center gap-3">
                        <span class="w-5 h-5 rounded-full bg-green-100 text-green-700 text-xs font-bold flex items-center justify-center flex-shrink-0">{{ i + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-foreground truncate">{{ doc.title }}</p>
                            <p class="text-xs text-muted-foreground">{{ doc.entity?.name }} · {{ doc.file_extension?.toUpperCase() }}</p>
                        </div>
                        <span class="text-sm font-bold text-foreground flex-shrink-0">{{ doc.download_count }}</span>
                    </div>
                    <div v-if="!top_downloaded?.length" class="text-sm text-muted-foreground text-center py-4">
                        Aucun téléchargement
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents récents -->
        <div class="bg-background rounded-xl border border-border overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-border">
                <h2 class="font-semibold text-foreground">Ajouts récents</h2>
                <Link :href="route('library.index')" class="text-sm text-blue-600 hover:text-blue-800">
                    Voir la bibliothèque →
                </Link>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="doc in recent_documents" :key="doc.id"
                    class="flex items-center gap-4 px-5 py-3 hover:bg-accent/20 transition">
                    <!-- Icône type -->
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                        :class="{
                            'bg-red-100': doc.file_extension === 'pdf',
                            'bg-green-100': doc.file_type === 'image',
                            'bg-purple-100': doc.file_type === 'video',
                            'bg-yellow-100': doc.file_type === 'audio',
                            'bg-blue-100': doc.file_type === 'document' && doc.file_extension !== 'pdf',
                            'bg-accent': doc.file_type === 'other',
                        }">
                        <svg class="w-4 h-4"
                            :class="{
                                'text-red-600': doc.file_extension === 'pdf',
                                'text-green-600': doc.file_type === 'image',
                                'text-purple-600': doc.file_type === 'video',
                                'text-yellow-600': doc.file_type === 'audio',
                                'text-blue-600': doc.file_type === 'document' && doc.file_extension !== 'pdf',
                                'text-muted-foreground': doc.file_type === 'other',
                            }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-foreground truncate">{{ doc.title }}</p>
                        <p class="text-xs text-muted-foreground">{{ doc.entity?.name }} · {{ doc.category?.name }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-xs text-muted-foreground">{{ doc.uploader?.name }}</p>
                        <p class="text-xs text-muted-foreground">{{ new Date(doc.created_at).toLocaleDateString('fr-FR') }}</p>
                    </div>
                    <div class="flex items-center gap-1 text-xs text-muted-foreground flex-shrink-0 w-14 justify-end">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        {{ doc.download_count }}
                    </div>
                </div>
                <div v-if="!recent_documents?.length" class="px-5 py-8 text-center text-sm text-muted-foreground">
                    Aucun document récent
                </div>
            </div>
        </div>

    </AppLayout>
</template>
