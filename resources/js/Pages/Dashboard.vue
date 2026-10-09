<script setup>
import { ref, onMounted, computed } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'
import Chart from 'chart.js/auto'

const props = defineProps({
    periode:             Object,
    totalKaryawan:       Number,
    totalPeriode:        Number,
    rataRataAktif:       Number,
    rataRataKeseluruhan: Number,
    hasil:               Array,
    top3:                Array,
    rankingKeseluruhan:  Array,
    trenPeriode:         Array,
    kriteria:            Array,
})

const barChartRef    = ref(null)
const donutChartRef  = ref(null)
const lineChartRef   = ref(null)

const namaPeriode = computed(() =>
    props.periode
        ? `${props.periode.bulan} ${props.periode.tahun}`
        : 'Belum ada periode'
)

function rankClass(r) {
    if (r === 1) return 'bg-yellow-100 text-yellow-800'
    if (r === 2) return 'bg-gray-100 text-gray-600'
    if (r === 3) return 'bg-orange-100 text-orange-700'
    return 'bg-slate-100 text-slate-500'
}

const chartColors = ['#378ADD','#E24B4A','#EF9F27','#639922','#7F77DD','#1D9E75']

// ── Pagination Setup ──
const ITEMS_PER_PAGE = 10

// Pagination: Ranking Periode Aktif
const currentPageAktif = ref(1)
const totalPagesAktif = computed(() => Math.ceil((props.hasil?.length || 0) / ITEMS_PER_PAGE))
const paginatedHasil = computed(() => {
    if (!props.hasil) return []
    const start = (currentPageAktif.value - 1) * ITEMS_PER_PAGE
    return props.hasil.slice(start, start + ITEMS_PER_PAGE)
})

// Pagination: Ranking Rata-rata
const currentPageRata = ref(1)
const totalPagesRata = computed(() => Math.ceil((props.rankingKeseluruhan?.length || 0) / ITEMS_PER_PAGE))
const paginatedRankingKeseluruhan = computed(() => {
    if (!props.rankingKeseluruhan) return []
    const start = (currentPageRata.value - 1) * ITEMS_PER_PAGE
    return props.rankingKeseluruhan.slice(start, start + ITEMS_PER_PAGE)
})

onMounted(() => {
    // ── Bar Chart ──
    if (barChartRef.value && props.hasil?.length) {
        new Chart(barChartRef.value, {
            type: 'bar',
            data: {
                labels: props.hasil.map(h => h.nama),
                datasets: [{
                    data: props.hasil.map(h => Number(h.nilai_akhir)),
                    backgroundColor: props.hasil.map((_, i) => chartColors[i % chartColors.length]),
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { min: 0, max: 1, ticks: { stepSize: 0.25, font: { size: 10 } }, grid: { color: 'rgba(0,0,0,0.04)' } },
                    x: { ticks: { font: { size: 11 } }, grid: { display: false } }
                }
            }
        })
    }

    // ── Donut Chart ──
    if (donutChartRef.value && props.kriteria?.length) {
        new Chart(donutChartRef.value, {
            type: 'doughnut',
            data: {
                labels: props.kriteria.map(k => k.nama_kriteria),
                datasets: [{
                    data: props.kriteria.map(k => k.bobot),
                    backgroundColor: chartColors,
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.raw}%`
                        }
                    }
                }
            }
        })
    }

    // ── Line Chart ──
    if (lineChartRef.value && props.trenPeriode?.length) {
        new Chart(lineChartRef.value, {
            type: 'line',
            data: {
                labels: props.trenPeriode.map(t => t.label),
                datasets: [{
                    label: 'Rata-rata',
                    data: props.trenPeriode.map(t => t.avg),
                    borderColor: '#378ADD',
                    backgroundColor: 'rgba(55,138,221,0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: '#378ADD',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                    tension: 0.3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` Rata-rata: ${Number(ctx.raw).toFixed(2)}`
                        }
                    }
                },
                scales: {
                    y: { min: 0, max: 1, ticks: { stepSize: 0.25, font: { size: 10 } }, grid: { color: 'rgba(0,0,0,0.04)' } },
                    x: { ticks: { font: { size: 11 } }, grid: { display: false } }
                }
            }
        })
    }
})
</script>

<template>
    <Head title="Dashboard - SIKAP" />
    <MainLayout>
        <div class="p-6 sm:p-8 bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-gray-100/50">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4 border-b border-gray-100 pb-6">
                <div>
                    <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-indigo-600 tracking-tight">Dashboard Analitik</h1>
                    <p class="text-sm text-gray-500 mt-2 font-medium">Sistem Informasi Kedisiplinan Absensi Pegawai · Metode SAW</p>
                </div>
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100 shadow-sm">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    Periode Aktif: {{ namaPeriode }}
                </div>
            </div>

            <!-- STATS CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-gradient-to-br from-white to-slate-50 border border-gray-100 rounded-2xl p-5 relative overflow-hidden shadow-sm hover:shadow-md transition-shadow group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-50 rounded-full blur-xl group-hover:bg-blue-100 transition-colors"></div>
                    <div class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1 relative z-10">Total Pegawai</div>
                    <div class="text-3xl font-extrabold text-gray-900 mb-1 relative z-10">{{ totalKaryawan }}</div>
                    <div class="text-xs text-gray-400 font-medium relative z-10">Terdaftar di sistem</div>
                    <div class="absolute right-4 top-4 w-10 h-10 rounded-xl bg-blue-100/50 flex items-center justify-center text-blue-600 shadow-sm">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-white to-emerald-50/30 border border-gray-100 rounded-2xl p-5 relative overflow-hidden shadow-sm hover:shadow-md transition-shadow group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-50 rounded-full blur-xl group-hover:bg-emerald-100 transition-colors"></div>
                    <div class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1 relative z-10">Total Periode</div>
                    <div class="text-3xl font-extrabold text-emerald-600 mb-1 relative z-10">{{ totalPeriode }}</div>
                    <div class="text-xs text-gray-400 font-medium relative z-10">Periode sudah dinilai</div>
                    <div class="absolute right-4 top-4 w-10 h-10 rounded-xl bg-emerald-100/50 flex items-center justify-center text-emerald-600 shadow-sm">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-white to-indigo-50/30 border border-gray-100 rounded-2xl p-5 relative overflow-hidden shadow-sm hover:shadow-md transition-shadow group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-50 rounded-full blur-xl group-hover:bg-indigo-100 transition-colors"></div>
                    <div class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1 relative z-10">Rata-rata SAW Aktif</div>
                    <div class="text-3xl font-extrabold text-indigo-600 mb-1 relative z-10">{{ Number(rataRataAktif).toFixed(2) }}</div>
                    <div class="text-xs text-gray-400 font-medium relative z-10">Pada periode ini</div>
                    <div class="absolute right-4 top-4 w-10 h-10 rounded-xl bg-indigo-100/50 flex items-center justify-center text-indigo-600 shadow-sm">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-white to-purple-50/30 border border-gray-100 rounded-2xl p-5 relative overflow-hidden shadow-sm hover:shadow-md transition-shadow group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-purple-50 rounded-full blur-xl group-hover:bg-purple-100 transition-colors"></div>
                    <div class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1 relative z-10">Rata-rata Global</div>
                    <div class="text-3xl font-extrabold text-purple-600 mb-1 relative z-10">{{ Number(rataRataKeseluruhan).toFixed(2) }}</div>
                    <div class="text-xs text-gray-400 font-medium relative z-10">Dari seluruh periode</div>
                    <div class="absolute right-4 top-4 w-10 h-10 rounded-xl bg-purple-100/50 flex items-center justify-center text-purple-600 shadow-sm">
                        <i class="fa-solid fa-ranking-star"></i>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 mb-4" style="grid-template-columns:2fr 1fr">
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-800">Perbandingan nilai akhir</span>
                        <span class="text-xs text-gray-400">{{ namaPeriode }}</span>
                    </div>
                    <div class="p-4">
                        <canvas ref="barChartRef" height="160"></canvas>
                        <div v-if="!hasil?.length" class="text-center py-8 text-gray-400 text-sm">
                            Belum ada data perhitungan SAW.
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <span class="text-sm font-medium text-gray-800">Bobot kriteria</span>
                    </div>
                    <div class="flex justify-center pt-3 pb-1">
                        <canvas ref="donutChartRef" width="150" height="130"></canvas>
                    </div>
                    <div class="px-4 pb-4 flex flex-col gap-1.5">
                        <div v-for="(k, i) in kriteria" :key="k.kode" class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                :style="{ background: chartColors[i % chartColors.length] }">
                            </div>
                            <span class="text-xs text-gray-700 flex-1">{{ k.nama_kriteria }}</span>
                            <span class="text-xs px-1.5 py-0.5 rounded-full font-medium"
                                :class="k.jenis?.toLowerCase() === 'benefit'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-600'">
                                {{ k.jenis?.toLowerCase() }}
                            </span>
                            <span class="text-xs font-semibold text-gray-500 min-w-[32px] text-right">
                                {{ k.bobot }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border border-gray-200 rounded-xl overflow-hidden mb-4">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-800">
                        Tren rata-rata nilai SAW per periode
                    </span>
                    <span class="text-xs text-gray-400">Semua periode</span>
                </div>
                <div class="p-4">
                    <canvas ref="lineChartRef" height="90"></canvas>
                    <div v-if="!trenPeriode?.length" class="text-center py-6 text-gray-400 text-sm">
                        Belum ada data untuk ditampilkan.
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">

                <div class="border border-gray-200 rounded-2xl overflow-hidden flex flex-col shadow-sm bg-white">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-trophy text-amber-500"></i>
                            <span class="text-sm font-bold text-gray-800">Ranking Periode Aktif</span>
                        </div>
                        <a href="/saw/hasil" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors">
                            Lihat Laporan Detail &rarr;
                        </a>
                    </div>
                    <div class="flex-1 overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50/80 border-b border-gray-100">
                                <tr>
                                    <th class="text-gray-500 font-bold text-[10px] uppercase tracking-wider py-3 px-4 text-center w-14">Rank</th>
                                    <th class="text-gray-500 font-bold text-[10px] uppercase tracking-wider py-3 px-4 text-left">Pegawai</th>
                                    <th class="text-gray-500 font-bold text-[10px] uppercase tracking-wider py-3 px-4 text-left">Divisi</th>
                                    <th class="text-gray-500 font-bold text-[10px] uppercase tracking-wider py-3 px-4 text-center">Nilai Akhir</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="h in paginatedHasil" :key="h.nip" class="hover:bg-blue-50/30 transition-colors">
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold"
                                            :class="rankClass(h.peringkat)">
                                            {{ h.peringkat }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-sm text-gray-900">{{ h.nama }}</div>
                                        <div class="text-xs text-gray-400 font-mono mt-0.5">
                                            {{ h.jabatan }} <span class="mx-1 text-gray-300">|</span> NIP: {{ h.nip }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span v-if="h.karyawan?.divisi" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100/50">
                                            {{ h.karyawan.divisi }}
                                        </span>
                                        <span v-else class="text-gray-400 text-xs italic">-</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="font-bold text-gray-800 text-sm bg-gray-50 px-3 py-1 rounded-lg border border-gray-100">
                                            {{ Number(h.nilai_akhir).toFixed(4) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!paginatedHasil.length">
                                    <td colspan="4" class="text-center py-10 text-gray-400 text-sm">
                                        <div class="flex flex-col items-center">
                                            <i class="fa-regular fa-folder-open text-3xl mb-2 text-gray-300"></i>
                                            Belum ada data penilaian pada periode aktif.
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div v-if="totalPagesAktif > 1" class="px-4 py-2.5 border-t border-gray-100 bg-slate-50 flex justify-between items-center mt-auto">
                        <span class="text-xs text-gray-500">Hal {{ currentPageAktif }} dari {{ totalPagesAktif }}</span>
                        <div class="flex gap-1.5">
                            <button @click="currentPageAktif--" :disabled="currentPageAktif === 1"
                                class="px-2.5 py-1 text-xs border border-gray-300 rounded bg-white text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:hover:bg-white transition-colors">
                                Prev
                            </button>
                            <button @click="currentPageAktif++" :disabled="currentPageAktif === totalPagesAktif"
                                class="px-2.5 py-1 text-xs border border-gray-300 rounded bg-white text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:hover:bg-white transition-colors">
                                Next
                            </button>
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-2xl overflow-hidden flex flex-col shadow-sm bg-white">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-globe text-indigo-500"></i>
                            <span class="text-sm font-bold text-gray-800">Ranking Keseluruhan</span>
                        </div>
                        <span class="text-[10px] font-semibold tracking-wider text-gray-400 uppercase">Avg Nilai SAW</span>
                    </div>
                    <div class="flex-1 overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50/80 border-b border-gray-100">
                                <tr>
                                    <th class="text-gray-500 font-bold text-[10px] uppercase tracking-wider py-3 px-4 text-center w-14">Rank</th>
                                    <th class="text-gray-500 font-bold text-[10px] uppercase tracking-wider py-3 px-4 text-left">Pegawai</th>
                                    <th class="text-gray-500 font-bold text-[10px] uppercase tracking-wider py-3 px-4 text-left">Divisi</th>
                                    <th class="text-gray-500 font-bold text-[10px] uppercase tracking-wider py-3 px-4 text-center">Avg Nilai</th>
                                    <th class="text-gray-500 font-bold text-[10px] uppercase tracking-wider py-3 px-4 text-center">Periode</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(h, i) in paginatedRankingKeseluruhan" :key="h.nip" class="hover:bg-indigo-50/30 transition-colors">
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold"
                                            :class="rankClass((currentPageRata - 1) * ITEMS_PER_PAGE + i + 1)">
                                            {{ (currentPageRata - 1) * ITEMS_PER_PAGE + i + 1 }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-sm text-gray-900">{{ h.nama }}</div>
                                        <div class="text-[11px] text-gray-400 font-mono mt-0.5">
                                            {{ h.jabatan }} <span class="mx-1 text-gray-300">|</span> NIP: {{ h.nip }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span v-if="h.karyawan?.divisi" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100/50">
                                            {{ h.karyawan.divisi }}
                                        </span>
                                        <span v-else class="text-gray-400 text-xs italic">-</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="font-bold text-indigo-700 text-sm bg-indigo-50 px-3 py-1 rounded-lg border border-indigo-100">
                                            {{ Number(h.avg_nilai).toFixed(4) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-xs font-medium bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                                            {{ h.total_periode }}x
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!paginatedRankingKeseluruhan.length">
                                    <td colspan="5" class="text-center py-10 text-gray-400 text-sm">
                                        <div class="flex flex-col items-center">
                                            <i class="fa-regular fa-folder-open text-3xl mb-2 text-gray-300"></i>
                                            Belum ada data history penilaian.
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div v-if="totalPagesRata > 1" class="px-4 py-2.5 border-t border-gray-100 bg-slate-50 flex justify-between items-center mt-auto">
                        <span class="text-xs text-gray-500">Hal {{ currentPageRata }} dari {{ totalPagesRata }}</span>
                        <div class="flex gap-1.5">
                            <button @click="currentPageRata--" :disabled="currentPageRata === 1"
                                class="px-2.5 py-1 text-xs border border-gray-300 rounded bg-white text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:hover:bg-white transition-colors">
                                Prev
                            </button>
                            <button @click="currentPageRata++" :disabled="currentPageRata === totalPagesRata"
                                class="px-2.5 py-1 text-xs border border-gray-300 rounded bg-white text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:hover:bg-white transition-colors">
                                Next
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </MainLayout>
</template>