<script setup>
import { computed } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth?.user)

const navItems = [
    { label: 'Dashboard',       href: '/pimpinan/dashboard', icon: 'fa-solid fa-gauge-high' },
    { label: 'Data Karyawan',   href: '/pimpinan/karyawan',  icon: 'fa-solid fa-users' },
    { label: 'Hasil & Laporan', href: '/pimpinan/laporan',   icon: 'fa-solid fa-file-invoice' },
]

function isActive(href) {
    return page.url.startsWith(href)
}

function logout() {
    router.post('/logout')
}
</script>

<template>
    <div class="drawer lg:drawer-open">
        <input id="my-drawer-2" type="checkbox" class="drawer-toggle" />
        
        <div class="drawer-content flex flex-col bg-slate-50 min-h-screen">
            
            <!-- Mobile Header Bar -->
            <div class="sticky top-0 z-30 flex w-full items-center justify-between bg-white/90 backdrop-blur-md shadow-xs border-b border-slate-200/80 px-4 py-3 lg:hidden">
                <div class="flex items-center gap-2.5">
                    <label for="my-drawer-2" class="btn btn-square btn-sm btn-ghost drawer-button text-slate-700">
                        <i class="fa-solid fa-bars-staggered text-base"></i>
                    </label>
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white text-xs shadow-xs">
                            <i class="fa-solid fa-chart-simple"></i>
                        </div>
                        <span class="text-base font-extrabold text-slate-800">SPK SIKAP</span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-semibold text-slate-600">{{ user?.nama || 'Pimpinan' }}</span>
                </div>
            </div>

            <!-- Page Main Content Slot -->
            <main class="p-4 lg:p-8 flex-1 w-full max-w-7xl mx-auto">
                <slot />
            </main>
        </div>

        <!-- Modern Sidebar Drawer -->
        <div class="drawer-side z-[99]">
            <label for="my-drawer-2" aria-label="close sidebar" class="drawer-overlay"></label> 
            
            <aside class="w-72 min-h-full bg-slate-900 border-r border-slate-800 flex flex-col justify-between shadow-2xl select-none">
                
                <!-- Brand Header -->
                <div class="px-5 py-5 flex items-center gap-3.5 border-b border-slate-800/80 bg-slate-900/50">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/25 ring-1 ring-white/20">
                        <i class="fa-solid fa-chart-line text-white text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-black text-white tracking-tight">SIKAP</h2>
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">Pimpinan</span>
                        </div>
                        <p class="text-[11px] font-medium text-slate-400 mt-0.5">Executive Dashboard</p>
                    </div>
                </div>

                <!-- Navigation List -->
                <div class="flex-1 px-3 py-5 space-y-6 overflow-y-auto">
                    <div>
                        <div class="px-3 mb-2 text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                            Menu Pimpinan
                        </div>
                        <div class="space-y-1">
                            <Link
                                v-for="item in navItems"
                                :key="item.href"
                                :href="item.href"
                                class="group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200"
                                :class="isActive(item.href)
                                    ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-bold'
                                    : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60'"
                            >
                                <!-- Active Indicator Bar on Left -->
                                <span v-if="isActive(item.href)" class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span>

                                <!-- Icon Box -->
                                <span class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-200 flex-shrink-0"
                                    :class="isActive(item.href)
                                        ? 'bg-white/20 text-white'
                                        : 'text-slate-400 group-hover:text-indigo-400 group-hover:bg-slate-800/80'">
                                    <i :class="[item.icon, 'text-sm']"></i>
                                </span>
                                
                                <span class="flex-1 truncate">{{ item.label }}</span>
                                
                                <i v-if="isActive(item.href)" class="fa-solid fa-chevron-right text-[10px] opacity-75"></i>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- User Profile & Logout Bottom Card -->
                <div class="p-3 border-t border-slate-800 bg-slate-950/40">
                    <div class="p-2.5 rounded-xl bg-slate-800/50 border border-slate-800/80 mb-2 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white text-xs font-black shadow-sm flex-shrink-0 ring-1 ring-white/10">
                            {{ user?.nama ? user.nama.charAt(0).toUpperCase() : 'P' }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-bold text-white truncate leading-tight">{{ user?.nama || 'Pimpinan Perusahaan' }}</div>
                            <div class="text-[10px] text-slate-400 font-medium capitalize mt-0.5 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                {{ user?.role || 'Pimpinan' }}
                            </div>
                        </div>
                    </div>

                    <button
                        @click="logout"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 border border-rose-500/20 transition-all duration-200 cursor-pointer"
                    >
                        <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                        <span>Keluar Akun</span>
                    </button>
                </div>

            </aside>
        </div>
    </div>
</template>