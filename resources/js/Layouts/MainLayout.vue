<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="drawer lg:drawer-open">
        <input id="my-drawer-2" type="checkbox" class="drawer-toggle" />
        
        <div class="drawer-content flex flex-col bg-slate-50 min-h-screen">
            
            <div class="sticky top-0 z-30 flex w-full items-center justify-between bg-white/90 backdrop-blur-md shadow-sm border-b border-slate-200 px-4 py-3 lg:hidden">
                <div class="flex items-center">
                    <label for="my-drawer-2" class="btn btn-square btn-ghost drawer-button mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 stroke-slate-700"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </label>
                    <span class="text-lg font-bold text-slate-800">SPK SIKAP</span>
                </div>
            </div>

            <main class="p-4 lg:p-8 flex-1 w-full max-w-7xl mx-auto">
                <slot />
            </main>
        </div> 

        <div class="drawer-side z-[99]">
            <label for="my-drawer-2" aria-label="close sidebar" class="drawer-overlay"></label> 
            
            <ul class="menu p-4 w-72 min-h-full bg-slate-900 border-r border-slate-800 text-slate-300 flex flex-col gap-1.5 shadow-2xl">
                
                <div class="mb-8 mt-2 px-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-white tracking-tight">SIKAP</h2>
                        <p class="text-[11px] font-medium text-slate-400 uppercase tracking-widest mt-0.5">Metode SAW</p>
                    </div>
                </div>

                <li class="menu-title text-slate-500 font-bold tracking-wider uppercase text-[11px] mt-2 mb-1">Menu Utama</li>
                <li>
                    <Link href="/dashboard" :class="[
                        'flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all duration-200',
                        $page.url === '/dashboard' ? 'bg-blue-600 text-white font-semibold shadow-md shadow-blue-900/50' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                    ]">
                        📊 Dashboard
                    </Link>
                </li>
                
                <li class="menu-title text-slate-500 font-bold tracking-wider uppercase text-[11px] mt-6 mb-1">Master Data</li>
                <li>
                    <Link href="/karyawan" :class="[
                        'flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all duration-200',
                        $page.url === '/karyawan' ? 'bg-blue-600 text-white font-semibold shadow-md shadow-blue-900/50' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                    ]">
                        👥 Data Pegawai
                    </Link>
                </li>
                <li>
                    <Link href="/kriteria" :class="[
                        'flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all duration-200',
                        $page.url === '/kriteria' ? 'bg-blue-600 text-white font-semibold shadow-md shadow-blue-900/50' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                    ]">
                        ⚙️ Data Kriteria
                    </Link>
                </li>
                <li>
                    <Link href="/periode" :class="[
                        'flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all duration-200',
                        $page.url === '/periode' ? 'bg-blue-600 text-white font-semibold shadow-md shadow-blue-900/50' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                    ]">
                        📅 Periode
                    </Link>
                </li>

                <li class="menu-title text-slate-500 font-bold tracking-wider uppercase text-[11px] mt-6 mb-1">Proses SAW</li>
                <li>
                    <Link href="/penilaian/create" :class="[
                        'flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all duration-200',
                        $page.url === '/penilaian/create' ? 'bg-blue-600 text-white font-semibold shadow-md shadow-blue-900/50' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                    ]">
                        📝 Form Input Absensi
                    </Link>
                </li>
                <li>
                    <Link href="/penilaian" :class="[
                        'flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all duration-200',
                        $page.url === '/penilaian' ? 'bg-blue-600 text-white font-semibold shadow-md shadow-blue-900/50' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                    ]">
                        📋 Data Hasil Absensi
                    </Link>
                </li>
                <li>
                    <Link href="/saw/proses" :class="[
                        'flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all duration-200',
                        $page.url === '/saw/proses' ? 'bg-blue-600 text-white font-semibold shadow-md shadow-blue-900/50' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                    ]">
                        🧮 Perhitungan SAW
                    </Link>
                </li>
                <li>
                    <Link href="/saw/hasil" :class="[
                        'flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all duration-200',
                        $page.url === '/saw/hasil' ? 'bg-blue-600 text-white font-semibold shadow-md shadow-blue-900/50' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                    ]">
                        🏆 Hasil & Ranking
                    </Link>
                </li>

                <div class="flex-grow"></div>

                <div class="mt-4 pt-4 border-t border-slate-800">
                    <li>
                        <Link href="/logout" method="post" as="button" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-colors">
                            🚪 Logout
                        </Link>
                    </li>
                </div>
            </ul>
        </div>
    </div>
</template>