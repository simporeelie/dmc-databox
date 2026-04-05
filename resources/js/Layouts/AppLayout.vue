<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;
const sidebarOpen = ref(false);

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex">

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-64 bg-blue-900 text-white transform transition-transform duration-300"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <!-- Logo -->
            <div class="flex items-center gap-3 px-6 py-5 border-b border-blue-800">
                <svg class="w-8 h-8 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
                <div>
                    <div class="font-bold text-sm">DMC DATA BOX</div>
                    <div class="text-blue-400 text-xs">Coris Holding</div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="px-4 py-6 space-y-1">
                <Link
                    :href="route('library.index')"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition"
                    :class="$page.url.startsWith('/library') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white'"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    Bibliothèque
                </Link>

                <!-- Menu Admin -->
                <template v-if="user.role === 'admin'">
                    <div class="pt-4 pb-1 px-3 text-xs font-semibold text-blue-400 uppercase tracking-wider">
                        Administration
                    </div>
                    <Link
                        :href="route('admin.users.index')"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition"
                        :class="$page.url.startsWith('/admin/users') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white'"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Utilisateurs
                    </Link>
                    <Link
                        :href="route('admin.entities.index')"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition"
                        :class="$page.url.startsWith('/admin/entities') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white'"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        Entités
                    </Link>
                    <Link
                        :href="route('admin.filiales.index')"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition"
                        :class="$page.url.startsWith('/admin/filiales') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white'"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                        </svg>
                        Filiales
                    </Link>
                    <Link
                        :href="route('admin.categories.index')"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition"
                        :class="$page.url.startsWith('/admin/categories') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white'"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Catégories
                    </Link>
                </template>
            </nav>

            <!-- User info en bas -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-blue-800">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-sm font-bold">
                        {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-white truncate">{{ user.name }}</div>
                        <div class="text-xs text-blue-400 uppercase">{{ user.role }}</div>
                    </div>
                    <button @click="logout" class="text-blue-400 hover:text-white transition" title="Déconnexion">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Overlay mobile -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Contenu principal -->
        <div class="flex-1 lg:ml-64 flex flex-col min-h-screen">
            <!-- Header mobile -->
            <header class="lg:hidden bg-blue-900 text-white px-4 py-3 flex items-center gap-3">
                <button @click="sidebarOpen = true">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <span class="font-bold">DMC DATA BOX</span>
            </header>

            <!-- Slot -->
            <main class="flex-1 p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
