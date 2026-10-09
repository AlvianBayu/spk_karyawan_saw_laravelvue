<script setup>
import { computed, onMounted, ref } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'
import Chart from 'chart.js/auto'

const props = defineProps({
    karyawan:     Object,
    penilaian:    Array,
    hasilSaw:     Array,
    sanksi:       Array,
    kriteria:     Array,
    rataRataSaw:  Number,
    totalSanksi:  Number,
    totalPeriode: Number,
})

const nama = computed(() =>
    props.karyawan.nama || props.karyawan.nama_karyawan || '-'
)

function initials(n) {
    if (!n) return '?'
    return n.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

function rankClass(r) {
    if (r === 1) return 'bg-yellow-100 text-yellow-800'
    if (r === 2) return 'bg-gray-100 text-gray-600'
    if (r === 3) return 'bg-orange-100 text-orange-700'
    return 'bg-slate-100 text-slate-500'
}

function getNilai(nilaiObj, kode) {
    return nilaiObj?.[kode] ?? '-'
}

function formatPeriode(periode) {
    if (!periode) return '-'
    return `${periode.bulan} ${periode.tahun}`
}

// ── Line Chart ──
const lineChartRef = ref(null)

onMounted(() => {
    if (!lineChartRef.value || !props.hasilSaw.length) return

    // Data dibalik karena hasilSaw urutan desc (terbaru dulu)
    const sorted   = [...props.hasilSaw].reverse()
    const labels   = sorted.map(h => formatPeriode(h.periode))
    const values   = sorted.map(h => Number(h.nilai_akhir))

    new Chart(lineChartRef.value, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Nilai akhir SAW',
                data: values,
                borderColor: '#2563EB',
                backgroundColor: 'rgba(37,99,235,0.08)',
                borderWidth: 2,
                pointBackgroundColor: '#2563EB',
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: true,
                tension: 0.3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` Nilai: ${Number(ctx.raw).toFixed(2)}`
                    }
                }
            },
            scales: {
                y: {
                    min: 0,
                    max: 1,
                    ticks: { stepSize: 0.25, font: { size: 11 } },
                    grid:  { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    ticks: { font: { size: 11 } },
                    grid:  { display: false }
                }
            }
        }
    })
})
</script>

<template>
    <Head title="Detail Pegawai - SIKAP" />
    <MainLayout>
        <div class="p-6 sm:p-8 bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-gray-100/50">

            <!-- Back -->
            <a href="/karyawan"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500
                       hover:text-gray-700 mb-5 transition-colors">
                <svg width="14" height="14" viewBox="0 0 12 12" fill="none"
                    stroke="currentColor" stroke-width="1.5">
                    <path d="M8 2L4 6l4 4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Kembali ke Data Pegawai
            </a>

            <!-- Profile Card -->
            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 p-6 border border-gray-100
                        rounded-2xl bg-gradient-to-br from-slate-50 to-white shadow-sm mb-6 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 rounded-bl-full -z-10 opacity-60"></div>
                
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center
                            justify-center text-white font-bold text-2xl flex-shrink-0 shadow-lg shadow-blue-500/30">
                    {{ initials(nama) }}
                </div>
                <div class="text-center sm:text-left flex-1">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mb-1">
                        <h2 class="text-2xl font-bold text-gray-900 tracking-tight">{{ nama }}</h2>
                        <span 
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold self-center"
                            :class="(karyawan.status || '').toLowerCase() === 'aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                        >
                            {{ karyawan.status }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-500 font-mono mt-0.5 mb-2">
                        <span class="text-blue-600 font-medium">{{ karyawan.jabatan }}</span> <span class="mx-1 text-gray-300">|</span> NIP: {{ karyawan.nip }}
                    </div>
                    <div class="inline-flex items-center">
                        <span v-if="karyawan.divisi" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-sm font-medium bg-indigo-50 text-indigo-700 border border-indigo-100/50">
                            <i class="fa-solid fa-layer-group text-xs opacity-70"></i>
                            {{ karyawan.divisi }}
                        </span>
                        <span v-else class="text-gray-400 text-sm italic">Divisi belum diatur</span>
                    </div>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-2">
                        <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Total Periode</div>
                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                            <i class="fa-regular fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-gray-900 mb-1">{{ totalPeriode }}</div>
                    <div class="text-xs text-gray-400 font-medium">Kali penilaian masuk</div>
                </div>
                
                <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-2">
                        <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Rata-rata SAW</div>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 mb-1">
                        {{ Number(rataRataSaw).toFixed(2) }}
                    </div>
                    <div class="text-xs text-gray-400 font-medium">Dari {{ totalPeriode }} periode</div>
                </div>
                
                <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-2">
                        <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Total Sanksi</div>
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" :class="totalSanksi > 0 ? 'bg-rose-50 text-rose-600' : 'bg-gray-50 text-gray-400'">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                    </div>
                    <div class="text-3xl font-bold mb-1" :class="totalSanksi > 0 ? 'text-rose-600' : 'text-gray-900'">
                        {{ totalSanksi }}
                    </div>
                    <div class="text-xs font-medium" :class="totalSanksi > 0 ? 'text-rose-500' : 'text-emerald-500'">
                        {{ totalSanksi > 0 ? 'Teguran akumulatif' : 'Disiplin baik, tidak ada sanksi' }}
                    </div>
                </div>
            </div>

            <!-- Line Chart — full width -->
            <div class="border border-gray-200 rounded-xl overflow-hidden mb-4">
                <div class="px-4 py-3 border-b border-gray-100 text-sm font-medium text-gray-800">
                    Tren nilai akhir SAW
                </div>
                <div class="p-4">
                    
                    <div class="relative h-64 max-w-3xl mx-auto"> 
                        <canvas ref="lineChartRef"></canvas>
                    </div>

                    <div v-if="hasilSaw.length === 0"
                        class="text-center py-8 text-gray-400 text-sm">
                        Belum ada data untuk ditampilkan.
                    </div>
                </div>
            </div>

            <!-- Grid 2 kolom: Hasil SAW | Sanksi -->
            <div class="grid grid-cols-2 gap-4 mb-4">

                <!-- Riwayat Hasil SAW -->
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 text-sm
                                font-medium text-gray-800">
                        Riwayat hasil SAW & peringkat
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="text-gray-600 font-semibold text-xs uppercase
                                           tracking-wide py-3 px-4 text-left">
                                    Periode
                                </th>
                                <th class="text-center text-gray-600 font-semibold
                                           text-xs uppercase tracking-wide py-3">
                                    Nilai
                                </th>
                                <th class="text-center text-gray-600 font-semibold
                                           text-xs uppercase tracking-wide py-3">
                                    Rank
                                </th>
                                <th class="text-center text-gray-600 font-semibold
                                           text-xs uppercase tracking-wide py-3">
                                    Tren
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="h in hasilSaw" :key="h.periode_id"
                                class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                <td class="px-4 py-2.5 font-medium text-sm text-gray-800">
                                    {{ formatPeriode(h.periode) }}
                                </td>
                                <td class="text-center py-2.5 font-semibold text-gray-800 text-sm">
                                    {{ Number(h.nilai_akhir).toFixed(2) }}
                                </td>
                                <td class="text-center py-2.5">
                                    <span class="inline-flex items-center justify-center
                                                 w-6 h-6 rounded-full text-xs font-medium"
                                        :class="rankClass(h.peringkat)">
                                        {{ h.peringkat }}
                                    </span>
                                </td>
                                <td class="text-center py-2.5 text-xs font-medium">
                                    <span v-if="h.tren === null" class="text-gray-400">
                                        — awal
                                    </span>
                                    <span v-else-if="String(h.tren).startsWith('+')"
                                        class="text-green-600">
                                        ▲ {{ h.tren }}
                                    </span>
                                    <span v-else-if="String(h.tren).startsWith('-')"
                                        class="text-red-500">
                                        ▼ {{ h.tren }}
                                    </span>
                                    <span v-else class="text-gray-400">{{ h.tren }}</span>
                                </td>
                            </tr>
                            <tr v-if="hasilSaw.length === 0">
                                <td colspan="4"
                                    class="text-center py-8 text-gray-400 text-sm">
                                    Belum ada hasil SAW.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Riwayat Sanksi -->
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 text-sm
                                font-medium text-gray-800">
                        Riwayat sanksi
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="text-gray-600 font-semibold text-xs uppercase
                                           tracking-wide py-3 px-4 text-left">
                                    Periode
                                </th>
                                <th class="text-center text-gray-600 font-semibold
                                           text-xs uppercase tracking-wide py-3">
                                    Alpa
                                </th>
                                <th class="text-center text-gray-600 font-semibold
                                           text-xs uppercase tracking-wide py-3">
                                    Ket.
                                </th>
                                <th class="text-center text-gray-600 font-semibold
                                           text-xs uppercase tracking-wide py-3">
                                    Potongan
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in sanksi" :key="s.periode_id"
                                class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                <td class="px-4 py-2.5 font-medium text-sm text-gray-800">
                                    {{ formatPeriode(s.periode) }}
                                </td>
                                <td class="text-center py-2.5 text-gray-700 text-sm">
                                    {{ s.jumlah_alpa }} hari
                                </td>
                                <td class="text-center py-2.5">
                                    <span v-if="s.keterangan"
                                        class="text-xs px-2 py-0.5 rounded-full font-medium
                                               bg-amber-100 text-amber-700 border border-amber-200">
                                        {{ s.keterangan }}
                                    </span>
                                    <span v-else class="text-gray-300 text-sm">—</span>
                                </td>
                                <td class="text-center py-2.5">
                                    <span v-if="s.potongan_persen > 0"
                                        class="text-xs px-2 py-0.5 rounded-full font-medium
                                               bg-red-100 text-red-600 border border-red-200">
                                        {{ s.potongan_persen }}%
                                    </span>
                                    <span v-else
                                        class="text-xs px-2 py-0.5 rounded-full font-medium
                                               bg-gray-100 text-gray-400">
                                        0%
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="sanksi.length === 0">
                                <td colspan="4"
                                    class="text-center py-8 text-gray-400 text-sm">
                                    Belum ada riwayat sanksi.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Riwayat Penilaian — full width -->
            <div class="border border-gray-200 rounded-xl overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 text-sm font-medium text-gray-800">
                    Riwayat penilaian absensi
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="text-gray-600 font-semibold text-xs uppercase
                                           tracking-wide py-3 px-4">
                                    Periode
                                </th>
                                <th v-for="k in kriteria" :key="k.kode"
                                    class="text-center text-gray-600 font-semibold
                                           text-xs uppercase tracking-wide py-3">
                                    <div class="flex flex-col items-center gap-1">
                                        {{ k.nama_kriteria }}
                                        <span
                                            class="text-xs px-1.5 py-0.5 rounded-full
                                                   font-medium normal-case tracking-normal"
                                            :class="k.jenis?.toLowerCase() === 'benefit'
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-red-100 text-red-600'">
                                            {{ k.jenis?.toLowerCase() }}
                                        </span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in penilaian" :key="p.periode_id"
                                class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-sm text-gray-800">
                                    {{ formatPeriode(p.periode) }}
                                </td>
                                <td v-for="k in kriteria" :key="k.kode"
                                    class="text-center py-3 text-gray-700 text-sm">
                                    {{ getNilai(p.nilai, k.kode) !== null && getNilai(p.nilai, k.kode) !== '' 
                                        ? Number(getNilai(p.nilai, k.kode)) + ' hari' 
                                        : '-' 
                                    }}
                                </td>
                            </tr>
                            <tr v-if="penilaian.length === 0">
                                <td :colspan="1 + kriteria.length"
                                    class="text-center py-8 text-gray-400 text-sm">
                                    Belum ada data penilaian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </MainLayout>
</template>