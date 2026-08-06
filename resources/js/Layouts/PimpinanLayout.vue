<script setup>
import { computed } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3' // Tambahkan Link disini

const page = usePage()
const user = computed(() => page.props.auth?.user)

const navItems = [
    { label: 'Dashboard',       href: '/pimpinan/dashboard', icon: 'dashboard' },
    { label: 'Data Karyawan',   href: '/pimpinan/karyawan',  icon: 'karyawan'  },
    { label: 'Hasil & Laporan', href: '/pimpinan/laporan',   icon: 'laporan'   },
]

function isActive(href) {
    return page.url.startsWith(href)
}

function logout() {
    router.post('/logout')
}
</script>

<template>
    <!-- PERBAIKAN 1: Gunakan h-screen dan overflow-hidden -->
    <div class="h-screen flex bg-gray-100 overflow-hidden">

        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 text-white flex flex-col flex-shrink-0">
            <!-- Logo -->
            <div class="px-6 py-5 border-b border-slate-700">
                <div class="text-lg font-bold tracking-wide">SIKAP</div>
                <div class="text-xs text-slate-400 mt-0.5">Metode SAW</div>
            </div>

            <!-- Navigation -->
            <!-- PERBAIKAN 2: Tambahkan overflow-y-auto pada nav -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <div class="text-xs text-slate-500 uppercase tracking-wider
                            px-3 mb-2 font-medium">
                    Menu
                </div>
                
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm
                           font-medium transition-colors"
                    :class="isActive(item.href)
                        ? 'bg-blue-600 text-white'
                        : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
                >
                    <!-- Dashboard icon -->
                    <svg v-if="item.icon === 'dashboard'" width="16" height="16"
                        viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="2" y="2" width="7" height="7" rx="1"/>
                        <rect x="11" y="2" width="7" height="7" rx="1"/>
                        <rect x="2" y="11" width="7" height="7" rx="1"/>
                        <rect x="11" y="11" width="7" height="7" rx="1"/>
                    </svg>
                    <!-- Karyawan icon -->
                    <svg v-if="item.icon === 'Pegawai'" width="16" height="16"
                        viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="8" cy="7" r="4"/>
                        <path d="M2 18c0-4 2.5-6 6-6s6 2 6 6"/>
                        <circle cx="15" cy="7" r="2.5"/>
                        <path d="M17 18c0-2.5-1-4-2-4.5"/>
                    </svg>
                    <!-- Laporan icon -->
                    <svg v-if="item.icon === 'laporan'" width="16" height="16"
                        viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M9 17v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2z"/>
                        <path d="M17 17V5a2 2 0 00-2-2h-2a2 2 0 00-2 2v12a2 2 0 002 2h2a2 2 0 002-2z"/>
                    </svg>
                    
                    {{ item.label }}
                </Link>
            </nav>

            <!-- Logout -->
            <div class="px-3 py-4 border-t border-slate-700">
                <button
                    @click="logout"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm
                           font-medium text-slate-300 hover:bg-slate-800 hover:text-white
                           transition-colors w-full">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none"
                        stroke="currentColor" stroke-width="1.5">
                        <path d="M13 7l4 3-4 3M17 10H7"/>
                        <path d="M7 3H5a2 2 0 00-2 2v10a2 2 0 002 2h2"/>
                    </svg>
                    Logout
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto">
            <div class="p-6">
                <slot />
            </div>
        </main>
    </div>
</template>