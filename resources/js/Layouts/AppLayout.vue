<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    noPadding: { type: Boolean, default: false },
});

const page = usePage();
const user = page.props.auth.user;

const logout = () => router.post(route('logout'));

// ── Sidebar collapse ──────────────────────────────────────────
const SIDEBAR_FULL = 280;
const SIDEBAR_MINI = 72;
const isSidebarCollapsed = ref(localStorage.getItem('sidebar-collapsed') === '1');
const sidebarWidth = () => isSidebarCollapsed.value ? SIDEBAR_MINI : SIDEBAR_FULL;
const HEADER_HEIGHT = '70px';
const sidebarStyle = computed(() => ({ width: sidebarWidth() + 'px', transition: 'width .3s ease' }));
const wrapperStyle = computed(() => ({ paddingInlineStart: sidebarWidth() + 'px', paddingTop: HEADER_HEIGHT, transition: 'padding-inline-start .3s ease' }));
const headerStyle = computed(() => ({ insetInlineStart: sidebarWidth() + 'px', transition: 'inset-inline-start .3s ease' }));
const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem('sidebar-collapsed', isSidebarCollapsed.value ? '1' : '0');
};

// ── Navigation active ─────────────────────────────────────────
const isActive = (path) => {
    const url = page.url;
    if (path === '/library') return url.startsWith('/library');
    return url.startsWith(path);
};

// ── User dropdown (Vue-natif) ─────────────────────────────────
const showUserMenu = ref(false);
const toggleUserMenu = () => { showUserMenu.value = !showUserMenu.value; };
const closeUserMenu = () => { showUserMenu.value = false; };

// ── Thème dark/light ──────────────────────────────────────────
const themeMode = ref(localStorage.getItem('kt-theme') || 'light');

const applyTheme = (mode) => {
    let resolved = mode;
    if (mode === 'system') {
        resolved = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    document.documentElement.classList.remove('light', 'dark');
    document.documentElement.classList.add(resolved);
    document.documentElement.setAttribute('data-kt-theme-mode', resolved);
    localStorage.setItem('kt-theme', mode);
    themeMode.value = mode;
};

const toggleTheme = () => {
    applyTheme(themeMode.value === 'dark' ? 'light' : 'dark');
};

// ── Panneau paramètres ────────────────────────────────────────
const showSettings = ref(false);

// ── Click outside handler ─────────────────────────────────────
const handleClickOutside = (e) => {
    if (showUserMenu.value && !e.target.closest('[data-user-menu]')) closeUserMenu();
    if (showSettings.value && !e.target.closest('[data-settings-panel]') && !e.target.closest('[data-settings-toggle]')) {
        showSettings.value = false;
    }
};

const reinitMetronic = () => {
    if (typeof window.KTComponents !== 'undefined') window.KTComponents.init();
};

onMounted(() => {
    document.body.classList.add('demo1', 'kt-header-fixed');
    reinitMetronic();
    document.addEventListener('click', handleClickOutside);
    router.on('navigate', () => setTimeout(reinitMetronic, 50));
});

onUnmounted(() => {
    document.body.classList.remove('demo1', 'kt-header-fixed');
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <!-- Sidebar -->
    <div class="kt-sidebar bg-background border-e border-e-border fixed top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0 [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]"
        :style="sidebarStyle"
        data-kt-drawer="true" data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0" id="sidebar">

        <!-- Sidebar Header -->
        <div class="kt-sidebar-header hidden lg:flex items-center relative justify-between px-3 shrink-0" :class="isSidebarCollapsed ? 'justify-center' : 'lg:px-6'" id="sidebar_header">
            <Link :href="route('library.index')" class="flex items-center gap-2.5 min-w-0">
                <!-- Icône seule (sidebar réduit) -->
                <img v-if="isSidebarCollapsed" src="/images/logo-icon.svg" alt="Coris Holding" class="h-8 w-8 shrink-0" />
                <!-- Logo complet (sidebar étendu) -->
                <img v-else src="/images/logo.svg" alt="Coris Holding Hub" class="h-9 shrink-0" style="max-width:160px;" />
            </Link>
            <!-- Toggle collapse -->
            <button class="kt-btn kt-btn-outline kt-btn-icon size-[30px] absolute start-full top-2/4 -translate-x-2/4 -translate-y-2/4 rtl:translate-x-2/4"
                @click="toggleSidebar" id="sidebar_toggle">
                <i class="ki-filled ki-black-left-line transition-all duration-300"
                    :class="isSidebarCollapsed ? 'rotate-180' : ''"></i>
            </button>
        </div>
        <!-- End Sidebar Header -->

        <!-- Sidebar Content -->
        <div class="kt-sidebar-content flex grow shrink-0 py-5 pe-2" id="sidebar_content">
            <div class="kt-scrollable-y-hover grow shrink-0 flex ps-2 lg:ps-5 pe-1 lg:pe-3"
                data-kt-scrollable="true"
                data-kt-scrollable-dependencies="#sidebar_header"
                data-kt-scrollable-height="auto"
                data-kt-scrollable-offset="0px"
                data-kt-scrollable-wrappers="#sidebar_content"
                id="sidebar_scrollable">

                <div class="kt-menu flex flex-col grow gap-0.5" data-kt-menu="true" id="sidebar_menu">

                    <!-- Bibliothèque -->
                    <div class="kt-menu-item" :class="isActive('/library') ? 'active' : ''">
                        <Link :href="route('library.index')"
                            class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[10px] ps-[10px] pe-[10px] py-[8px]">
                            <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                                <i class="ki-filled ki-element-11 text-lg"></i>
                            </span>
                            <span v-show="!isSidebarCollapsed" class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                                Bibliothèque
                            </span>
                        </Link>
                    </div>

                    <!-- Administration (admin uniquement) -->
                    <template v-if="user.role === 'admin'">
                        <div v-show="!isSidebarCollapsed" class="kt-menu-item pt-2.5 pb-px">
                            <span class="kt-menu-heading uppercase text-2xs font-semibold text-muted-foreground/70 ps-[10px] pe-[10px]">
                                Administration
                            </span>
                        </div>

                        <div class="kt-menu-item" :class="isActive('/admin') && !isActive('/admin/users') && !isActive('/admin/entities') && !isActive('/admin/filiales') && !isActive('/admin/categories') ? 'active' : ''">
                            <Link :href="route('admin.dashboard')"
                                class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[10px] ps-[10px] pe-[10px] py-[8px]">
                                <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                                    <i class="ki-filled ki-graph-2 text-lg"></i>
                                </span>
                                <span v-show="!isSidebarCollapsed" class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                                    Dashboard
                                </span>
                            </Link>
                        </div>

                        <div class="kt-menu-item" :class="isActive('/admin/users') ? 'active' : ''">
                            <Link :href="route('admin.users.index')"
                                class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[10px] ps-[10px] pe-[10px] py-[8px]">
                                <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                                    <i class="ki-filled ki-user-square text-lg"></i>
                                </span>
                                <span v-show="!isSidebarCollapsed" class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                                    Utilisateurs
                                </span>
                            </Link>
                        </div>

                        <div class="kt-menu-item" :class="isActive('/admin/entities') ? 'active' : ''">
                            <Link :href="route('admin.entities.index')"
                                class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[10px] ps-[10px] pe-[10px] py-[8px]">
                                <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                                    <i class="ki-filled ki-office-bag text-lg"></i>
                                </span>
                                <span v-show="!isSidebarCollapsed" class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                                    Entités
                                </span>
                            </Link>
                        </div>

                        <div class="kt-menu-item" :class="isActive('/admin/filiales') ? 'active' : ''">
                            <Link :href="route('admin.filiales.index')"
                                class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[10px] ps-[10px] pe-[10px] py-[8px]">
                                <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                                    <i class="ki-filled ki-map text-lg"></i>
                                </span>
                                <span v-show="!isSidebarCollapsed" class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                                    Filiales
                                </span>
                            </Link>
                        </div>

                        <div class="kt-menu-item" :class="isActive('/admin/categories') ? 'active' : ''">
                            <Link :href="route('admin.categories.index')"
                                class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[10px] ps-[10px] pe-[10px] py-[8px]">
                                <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                                    <i class="ki-filled ki-category text-lg"></i>
                                </span>
                                <span v-show="!isSidebarCollapsed" class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                                    Catégories
                                </span>
                            </Link>
                        </div>

                        <div class="kt-menu-item" :class="isActive('/admin/document-requests') ? 'active' : ''">
                            <Link :href="route('admin.document-requests.index')"
                                class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[10px] ps-[10px] pe-[10px] py-[8px]">
                                <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                                    <i class="ki-filled ki-file-added text-lg"></i>
                                </span>
                                <span v-show="!isSidebarCollapsed" class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                                    Demandes
                                </span>
                            </Link>
                        </div>
                    </template>

                </div>
            </div>
        </div>
        <!-- End Sidebar Content -->

        <!-- Sidebar Footer — User info -->
        <div class="hidden lg:flex items-center shrink-0 px-3 py-4 border-t border-border gap-2.5"
            :class="isSidebarCollapsed ? 'justify-center' : 'justify-between lg:px-6'">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="size-9 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                    <span class="text-sm font-bold text-primary uppercase">{{ user.name.charAt(0) }}</span>
                </div>
                <div v-show="!isSidebarCollapsed" class="min-w-0">
                    <p class="text-sm font-semibold text-mono truncate leading-none">{{ user.name }}</p>
                    <p class="text-xs text-muted-foreground uppercase mt-0.5">{{ user.role }}</p>
                </div>
            </div>
            <button v-show="!isSidebarCollapsed" @click="logout"
                class="kt-btn kt-btn-icon kt-btn-ghost size-8 shrink-0"
                title="Déconnexion">
                <i class="ki-filled ki-exit-right text-base text-muted-foreground hover:text-primary"></i>
            </button>
        </div>
        <!-- End Sidebar Footer -->

    </div>
    <!-- End Sidebar -->

    <!-- Wrapper -->
    <div class="kt-wrapper flex grow flex-col" :style="wrapperStyle">

        <!-- Header -->
        <header class="kt-header fixed top-0 z-10 end-0 flex items-stretch shrink-0 bg-background" :style="headerStyle"
            data-kt-sticky="true"
            data-kt-sticky-class="border-b border-border"
            data-kt-sticky-name="header"
            id="header">
            <div class="kt-container-fixed flex justify-between items-stretch gap-4" id="headerContainer">

                <!-- Mobile : logo + burger -->
                <div class="flex gap-2 lg:hidden items-center -ms-1">
                    <button class="kt-btn kt-btn-icon kt-btn-ghost" data-kt-drawer-toggle="#sidebar">
                        <i class="ki-filled ki-menu"></i>
                    </button>
                    <span class="text-mono font-bold text-sm">DMC DataBox</span>
                </div>

                <!-- Breadcrumb / titre de la page (desktop) -->
                <div class="hidden lg:flex items-center">
                    <span class="text-muted-foreground text-sm">
                        Coris Holding — DMC DataBox
                    </span>
                </div>

                <!-- Topbar droite -->
                <div class="flex items-center gap-1 ms-auto">

                    <!-- Toggle thème dark/light -->
                    <button @click="toggleTheme"
                        class="kt-btn kt-btn-icon kt-btn-ghost size-9"
                        :title="themeMode === 'dark' ? 'Passer en mode clair' : 'Passer en mode sombre'">
                        <i v-if="themeMode === 'dark'" class="ki-filled ki-moon text-base"></i>
                        <i v-else class="ki-filled ki-sun text-base"></i>
                    </button>

                    <!-- Paramètres -->
                    <button @click.stop="showSettings = !showSettings" data-settings-toggle
                        class="kt-btn kt-btn-icon kt-btn-ghost size-9" title="Paramètres">
                        <i class="ki-filled ki-setting-2 text-base"></i>
                    </button>

                    <!-- Séparateur -->
                    <div class="w-px h-5 bg-border mx-1"></div>

                    <!-- User dropdown Vue-natif -->
                    <div class="relative" data-user-menu>
                        <button @click.stop="toggleUserMenu"
                            class="flex items-center gap-2 rounded-lg hover:bg-accent/60 transition px-2 py-1.5">
                            <div class="size-8 rounded-full bg-primary flex items-center justify-center">
                                <span class="text-xs font-bold text-white uppercase">{{ user.name.charAt(0) }}</span>
                            </div>
                            <div class="hidden sm:block text-left">
                                <p class="text-sm font-medium text-mono leading-tight">{{ user.name }}</p>
                                <p class="text-xs text-muted-foreground uppercase leading-tight">{{ user.role }}</p>
                            </div>
                            <i class="ki-filled ki-down text-xs text-muted-foreground transition-transform duration-200"
                                :class="showUserMenu ? 'rotate-180' : ''"></i>
                        </button>

                        <!-- Dropdown menu -->
                        <Transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="opacity-0 translate-y-1 scale-95"
                            enter-to-class="opacity-100 translate-y-0 scale-100"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0 scale-95">
                            <div v-if="showUserMenu"
                                class="absolute end-0 top-full mt-1.5 w-56 bg-background border border-border rounded-xl shadow-lg z-50 overflow-hidden">
                                <!-- User info -->
                                <div class="px-4 py-3 border-b border-border bg-accent/30">
                                    <p class="text-sm font-semibold text-mono truncate">{{ user.name }}</p>
                                    <p class="text-xs text-muted-foreground mt-0.5">{{ user.email }}</p>
                                    <span class="inline-block mt-1 px-1.5 py-0.5 text-xs font-medium bg-primary/10 text-primary rounded uppercase">{{ user.role }}</span>
                                </div>
                                <!-- Links -->
                                <ul class="py-1">
                                    <li>
                                        <Link :href="route('profile.edit')" @click="closeUserMenu"
                                            class="flex items-center gap-2.5 px-4 py-2 text-sm text-foreground hover:bg-accent/60 transition">
                                            <i class="ki-filled ki-user-square text-base text-muted-foreground"></i>
                                            Mon profil
                                        </Link>
                                    </li>
                                    <li>
                                        <button @click="toggleTheme; closeUserMenu()"
                                            class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-foreground hover:bg-accent/60 transition">
                                            <i class="ki-filled text-base text-muted-foreground"
                                                :class="themeMode === 'dark' ? 'ki-sun' : 'ki-moon'"></i>
                                            {{ themeMode === 'dark' ? 'Mode clair' : 'Mode sombre' }}
                                        </button>
                                    </li>
                                    <li class="border-t border-border mt-1 pt-1">
                                        <button @click="logout"
                                            class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-danger hover:bg-danger/10 transition">
                                            <i class="ki-filled ki-exit-right text-base"></i>
                                            Déconnexion
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </Transition>
                    </div>

                </div>
                <!-- End Topbar -->

            </div>
        </header>
        <!-- End Header -->

        <!-- Content -->
        <main class="grow" :class="noPadding ? 'flex flex-col overflow-hidden' : 'pt-5'" id="content" role="content">
            <div :class="noPadding ? 'flex-1 flex flex-col overflow-hidden' : 'kt-container-fixed'">
                <slot />
            </div>
        </main>
        <!-- End Content -->

        <!-- Footer -->
        <footer class="kt-footer">
            <div class="kt-container-fixed">
                <div class="flex flex-col md:flex-row justify-between items-center gap-3 py-4">
                    <span class="text-sm text-muted-foreground">
                        {{ new Date().getFullYear() }}© DMC DataBox — Coris Holding
                    </span>
                    <span class="text-xs text-muted-foreground">v1.0</span>
                </div>
            </div>
        </footer>
        <!-- End Footer -->

    </div>
    <!-- End Wrapper -->

    <!-- ── Panneau Paramètres (slide-in droite) ──────────────── -->
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-x-0"
        leave-to-class="translate-x-full">
        <div v-if="showSettings" data-settings-panel
            class="fixed top-0 end-0 h-full w-72 bg-background border-s border-border shadow-2xl z-50 flex flex-col">

            <!-- Header panneau -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-border">
                <div>
                    <h3 class="text-sm font-semibold text-mono">Paramètres</h3>
                    <p class="text-xs text-muted-foreground mt-0.5">Personnaliser l'interface</p>
                </div>
                <button @click="showSettings = false"
                    class="kt-btn kt-btn-icon kt-btn-ghost size-8">
                    <i class="ki-filled ki-cross text-base"></i>
                </button>
            </div>

            <!-- Contenu panneau -->
            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-6">

                <!-- Section : Thème -->
                <div>
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-3">Apparence</p>
                    <div class="grid grid-cols-3 gap-2">
                        <button v-for="m in [
                            { key: 'light', icon: 'ki-sun', label: 'Clair' },
                            { key: 'dark',  icon: 'ki-moon', label: 'Sombre' },
                            { key: 'system',icon: 'ki-setting-3', label: 'Système' },
                        ]" :key="m.key"
                            @click="applyTheme(m.key)"
                            class="flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 transition cursor-pointer"
                            :class="themeMode === m.key
                                ? 'border-primary bg-primary/5 text-primary'
                                : 'border-border hover:border-border hover:bg-accent/50 text-muted-foreground'">
                            <i class="ki-filled text-xl" :class="m.icon"></i>
                            <span class="text-xs font-medium">{{ m.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Section : Sidebar -->
                <div>
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-3">Navigation</p>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-accent/40">
                            <div class="flex items-center gap-2.5">
                                <i class="ki-filled ki-sidebar-left text-base text-muted-foreground"></i>
                                <span class="text-sm text-foreground">Sidebar réduite</span>
                            </div>
                            <!-- Toggle switch -->
                            <button @click="toggleSidebar"
                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200"
                                :class="isSidebarCollapsed ? 'bg-primary' : 'bg-border'">
                                <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform duration-200"
                                    :class="isSidebarCollapsed ? 'translate-x-4' : 'translate-x-1'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Section : Informations -->
                <div>
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-3">Application</p>
                    <div class="rounded-xl border border-border overflow-hidden">
                        <div class="flex items-center justify-between px-3 py-2.5 text-sm border-b border-border">
                            <span class="text-muted-foreground">Version</span>
                            <span class="font-medium text-mono">v1.0</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-2.5 text-sm border-b border-border">
                            <span class="text-muted-foreground">Utilisateur</span>
                            <span class="font-medium text-mono truncate max-w-[120px]">{{ user.name }}</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-2.5 text-sm">
                            <span class="text-muted-foreground">Rôle</span>
                            <span class="px-2 py-0.5 bg-primary/10 text-primary text-xs font-medium rounded uppercase">{{ user.role }}</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer panneau -->
            <div class="px-5 py-4 border-t border-border">
                <Link :href="route('profile.edit')" @click="showSettings = false"
                    class="flex items-center gap-2.5 w-full px-3 py-2.5 rounded-lg text-sm text-foreground hover:bg-accent/60 transition">
                    <i class="ki-filled ki-user-square text-base text-muted-foreground"></i>
                    Modifier mon profil
                </Link>
            </div>
        </div>
    </Transition>

    <!-- Overlay settings panel -->
    <Transition enter-active-class="transition duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100"
        leave-active-class="transition duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showSettings" class="fixed inset-0 bg-black/20 z-40" @click="showSettings = false"></div>
    </Transition>

</template>
