<script setup>
import { ref, onMounted, computed } from 'vue'
import PimpinanLayout from '@/Layouts/PimpinanLayout.vue';
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
    <Head title="SIKAP" />
    <PimpinanLayout>
        <div class="p-6 bg-white rounded-lg shadow-md">

            <div class="mb-5">
                <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    Sistem Informasi Kinerja Aparatur Pegawai · Metode SAW
                </p>
                <div class="inline-flex items-center gap-1.5 mt-2 text-xs px-3 py-1
                            rounded-full bg-green-100 text-green-700 font-medium
                            border border-green-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                    Periode aktif: {{ namaPeriode }}
                </div>
            </div>

            <div class="grid grid-cols-4 gap-3 mb-5">
                <div class="bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 relative overflow-hidden">
                    <div class="text-xs text-gray-400 uppercase tracking-wide font-medium">
                        Total karyawan
                    </div>
                    <div class="text-2xl font-semibold text-gray-900 mt-1">
                        {{ totalKaryawan }}
                    </div>
                    <div class="text-xs text-gray-400 mt-0.5">Terdaftar di sistem</div>
                    <div class="absolute right-3 top-3 w-8 h-8 rounded-lg bg-blue-50
                                flex items-center justify-center">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="#3B6DDD" stroke-width="1.5">
                            <circle cx="6" cy="5" r="3"/>
                            <path d="M1 14c0-3 2.5-5 5-5s5 2 5 5"/>
                            <circle cx="12" cy="5" r="2"/>
                            <path d="M14 14c0-2-1-3.5-2-4"/>
                        </svg>
                    </div>
                </div>

                <div class="bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 relative overflow-hidden">
                    <div class="text-xs text-gray-400 uppercase tracking-wide font-medium">
                        Total periode
                    </div>
                    <div class="text-2xl font-semibold text-green-600 mt-1">
                        {{ totalPeriode }}
                    </div>
                    <div class="text-xs text-gray-400 mt-0.5">Periode sudah dinilai</div>
                    <div class="absolute right-3 top-3 w-8 h-8 rounded-lg bg-green-50
                                flex items-center justify-center">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="#639922" stroke-width="1.5">
                            <rect x="1" y="3" width="14" height="12" rx="2"/>
                            <path d="M1 7h14M5 1v4M11 1v4"/>
                        </svg>
                    </div>
                </div>

                <div class="bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 relative overflow-hidden">
                    <div class="text-xs text-gray-400 uppercase tracking-wide font-medium">
                        Rata-rata nilai SAW
                    </div>
                    <div class="text-2xl font-semibold text-blue-600 mt-1">
                        {{ Number(rataRataAktif).toFixed(2) }}
                    </div>
                    <div class="text-xs text-gray-400 mt-0.5">Periode aktif</div>
                    <div class="absolute right-3 top-3 w-8 h-8 rounded-lg bg-blue-50
                                flex items-center justify-center">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="#378ADD" stroke-width="1.5">
                            <path d="M2 12l4-4 3 3 5-6" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>

                <div class="bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 relative overflow-hidden">
                    <div class="text-xs text-gray-400 uppercase tracking-wide font-medium">
                        Rata-rata keseluruhan
                    </div>
                    <div class="text-2xl font-semibold text-purple-600 mt-1">
                        {{ Number(rataRataKeseluruhan).toFixed(2) }}
                    </div>
                    <div class="text-xs text-gray-400 mt-0.5">Semua periode</div>
                    <div class="absolute right-3 top-3 w-8 h-8 rounded-lg bg-purple-50
                                flex items-center justify-center">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="#7c3aed" stroke-width="1.5">
                            <path d="M2 8h12M8 2v12" stroke-linecap="round"/>
                        </svg>
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

                <div class="border border-gray-200 rounded-xl overflow-hidden flex flex-col">
                    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between bg-white">
                        <span class="text-sm font-medium text-gray-800">Ranking periode aktif</span>
                        <a href="/saw/hasil" class="text-xs text-blue-500 hover:underline">
                            Lihat detail →
                        </a>
                    </div>
                    <div class="flex-1 overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th class="text-gray-600 font-semibold text-xs uppercase tracking-wide py-3 px-4 text-left w-10">No</th>
                                    <th class="text-gray-600 font-semibold text-xs uppercase tracking-wide py-3 text-left">Karyawan</th>
                                    <th class="text-center text-gray-600 font-semibold text-xs uppercase tracking-wide py-3">Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="h in paginatedHasil" :key="h.nip"
                                    class="border-b border-gray-100 last:border-0 hover:bg-gray-50 bg-white">
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-medium"
                                            :class="rankClass(h.peringkat)">
                                            {{ h.peringkat }}
                                        </span>
                                    </td>
                                    <td class="py-2.5">
                                        <div class="font-medium text-sm text-gray-900">{{ h.nama }}</div>
                                        <div class="text-xs text-gray-400 font-mono mt-0.5">
                                            {{ h.jabatan }} · #{{ h.nip }}
                                        </div>
                                    </td>
                                    <td class="text-center py-2.5 font-semibold text-gray-800 text-sm">
                                        {{ Number(h.nilai_akhir).toFixed(2) }}
                                    </td>
                                </tr>
                                <tr v-if="!paginatedHasil.length">
                                    <td colspan="3" class="text-center py-8 text-gray-400 text-sm bg-white">
                                        Belum ada data.
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

                <div class="border border-gray-200 rounded-xl overflow-hidden flex flex-col">
                    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between bg-white">
                        <span class="text-sm font-medium text-gray-800">
                            Ranking rata-rata semua periode
                        </span>
                        <span class="text-xs text-gray-400">Avg nilai SAW</span>
                    </div>
                    <div class="flex-1 overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th class="text-gray-600 font-semibold text-xs uppercase tracking-wide py-3 px-4 text-left w-10">No</th>
                                    <th class="text-gray-600 font-semibold text-xs uppercase tracking-wide py-3 text-left">Karyawan</th>
                                    <th class="text-center text-gray-600 font-semibold text-xs uppercase tracking-wide py-3">Avg</th>
                                    <th class="text-center text-gray-600 font-semibold text-xs uppercase tracking-wide py-3">Periode</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(h, i) in paginatedRankingKeseluruhan" :key="h.nip"
                                    class="border-b border-gray-100 last:border-0 hover:bg-gray-50 bg-white">
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-medium"
                                            :class="rankClass((currentPageRata - 1) * ITEMS_PER_PAGE + i + 1)">
                                            {{ (currentPageRata - 1) * ITEMS_PER_PAGE + i + 1 }}
                                        </span>
                                    </td>
                                    <td class="py-2.5">
                                        <div class="font-medium text-sm text-gray-900">{{ h.nama }}</div>
                                        <div class="text-xs text-gray-400 font-mono mt-0.5">
                                            {{ h.jabatan }}
                                        </div>
                                    </td>
                                    <td class="text-center py-2.5 font-semibold text-gray-800 text-sm">
                                        {{ Number(h.avg_nilai).toFixed(2) }}
                                    </td>
                                    <td class="text-center py-2.5 text-xs text-gray-400">
                                        {{ h.total_periode }}x
                                    </td>
                                </tr>
                                <tr v-if="!paginatedRankingKeseluruhan.length">
                                    <td colspan="4" class="text-center py-8 text-gray-400 text-sm bg-white">
                                        Belum ada data.
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
    </PimpinanLayout>
</template>