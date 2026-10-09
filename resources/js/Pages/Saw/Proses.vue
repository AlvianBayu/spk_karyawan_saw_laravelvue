<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    periodeList:     Array,
    periodeId:       [String, Number, null],
    kriteria:        Array,
    rawData:         Array,
    normData:        Array,
    weightedData:    Array,
    optimum:         Object,
    hasExistingData: Boolean,
    progress100:     Boolean,
    pendingCount:    Number,
})

const periodeId  = ref(props.periodeId ?? '')
const activeTab  = ref('raw')

// ── Pagination per tab ──
const PER_PAGE = 10
const pageRaw      = ref(1)
const pageNorm     = ref(1)
const pageWeighted = ref(1)

const tabs = [
    { key: 'raw',      label: 'Data mentah' },
    { key: 'norm',     label: 'Normalisasi' },
    { key: 'weighted', label: 'Nilai akhir' },
]

// Reset halaman saat ganti tab
function setTab(key) {
    activeTab.value = key
}

// ── Computed paginated data ──
const totalPagesRaw = computed(() =>
    Math.ceil(props.rawData.length / PER_PAGE)
)
const pagedRaw = computed(() => {
    const start = (pageRaw.value - 1) * PER_PAGE
    return props.rawData.slice(start, start + PER_PAGE)
})

const totalPagesNorm = computed(() =>
    Math.ceil(props.normData.length / PER_PAGE)
)
const pagedNorm = computed(() => {
    const start = (pageNorm.value - 1) * PER_PAGE
    return props.normData.slice(start, start + PER_PAGE)
})

const totalPagesWeighted = computed(() =>
    Math.ceil(props.weightedData.length / PER_PAGE)
)
const pagedWeighted = computed(() => {
    const start = (pageWeighted.value - 1) * PER_PAGE
    return props.weightedData.slice(start, start + PER_PAGE)
})

// ── Page info ──
function pageInfo(current, total, dataLength) {
    if (dataLength === 0) return ''
    const start = (current - 1) * PER_PAGE + 1
    const end   = Math.min(current * PER_PAGE, dataLength)
    return `${start}–${end} dari ${dataLength} data`
}

function cariPeriode() {
    pageRaw.value      = 1
    pageNorm.value     = 1
    pageWeighted.value = 1
    router.get('/saw/proses', { periode_id: periodeId.value }, {
        preserveState: true,
        replace: true,
    })
}

const form = useForm({ periode_id: '', weighted_data: [] })

function simpanHasil() {
    form.periode_id    = periodeId.value
    form.weighted_data = props.weightedData
    form.post('/saw/simpan')
}

function rankClass(r) {
    if (r === 1) return 'bg-yellow-100 text-yellow-800'
    if (r === 2) return 'bg-gray-100 text-gray-600'
    if (r === 3) return 'bg-orange-100 text-orange-700'
    return 'bg-slate-100 text-slate-500'
}
</script>

<template>
    <Head title="" />
    <MainLayout>
        <div class="p-6 bg-white rounded-lg shadow-md">

            <!-- Top Card -->
            <div class="flex items-center justify-between mb-5 flex-wrap gap-3
                        border border-gray-200 rounded-xl px-5 py-4 bg-slate-50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-blue-100 flex items-center
                                justify-center flex-shrink-0">
                        <svg width="18" height="18" viewBox="0 0 20 20" fill="none"
                            stroke="#185FA5" stroke-width="1.5">
                            <rect x="2" y="3" width="16" height="14" rx="2"/>
                            <path d="M6 7h8M6 10h5M6 13h3"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-900">
                            Proses perhitungan SAW
                        </div>
                        <div class="text-xs text-gray-500 mt-0.5">
                            Simple Additive Weighting untuk penilaian karyawan
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <select v-model="periodeId"
                        class="select select-bordered select-sm bg-white text-gray-800
                               border-gray-300 min-w-[160px]">
                        <option value="">Pilih periode</option>
                        <option v-for="p in periodeList" :key="p.id" :value="p.id">
                            {{ p.bulan }} / {{ p.tahun }}
                        </option>
                    </select>
                    <button @click="cariPeriode"
                        class="btn btn-sm bg-blue-600 hover:bg-blue-700
                               text-white border-none gap-1.5">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="#fff">
                            <polygon points="3,1 11,6 3,11"/>
                        </svg>
                        Jalankan
                    </button>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="!periodeId || (periodeId && rawData.length === 0 && progress100)"
                class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center
                            justify-center mb-3">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="#378ADD" stroke-width="1.5">
                        <path d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2v-3"/>
                        <path d="M9 15h3l8-8a2 2 0 00-3-3l-8 8z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-700 mb-1">
                    Pilih periode lalu klik Jalankan
                </p>
                <p class="text-xs text-gray-400">
                    Hasil perhitungan akan muncul di sini
                </p>
            </div>

            <!-- Incomplete Progress State -->
            <div v-else-if="periodeId && !progress100"
                class="flex flex-col items-center justify-center py-20 text-center bg-rose-50/50 rounded-2xl border border-rose-200 mt-4">
                <div class="w-16 h-16 rounded-2xl bg-rose-100 flex items-center
                            justify-center mb-4">
                    <i class="fa-solid fa-triangle-exclamation text-3xl text-rose-500"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-800 mb-2">Progres Penginputan Belum 100%</h2>
                <p class="text-sm text-gray-600 max-w-md leading-relaxed mb-6">
                    Terdapat <strong class="text-rose-600 font-bold">{{ pendingCount }} pegawai</strong> yang datanya belum lengkap atau belum diinput pada periode ini. <br>Anda tidak dapat melakukan perhitungan SAW sebelum semua nilai pegawai lengkap.
                </p>
                <a :href="'/penilaian?periode_id=' + periodeId"
                    class="btn bg-rose-600 hover:bg-rose-700 text-white border-none rounded-xl px-6 shadow-sm">
                    Lengkapi Data Penilaian
                </a>
            </div>

            <!-- Tabel Perhitungan -->
            <template v-else-if="progress100 && rawData.length > 0">
                <div class="flex items-center justify-between mb-3 flex-wrap gap-3">
                    <span class="text-sm font-medium text-gray-800">Matriks keputusan</span>
                    <div class="flex gap-1 p-1 bg-gray-100 rounded-lg">
                        <button
                            v-for="tab in tabs" :key="tab.key"
                            @click="setTab(tab.key)"
                            class="text-xs px-3 py-1.5 rounded-md font-medium transition-all"
                            :class="activeTab === tab.key
                                ? 'bg-white text-gray-900 shadow-sm'
                                : 'text-gray-500 hover:text-gray-700'"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <!-- ── Tab: Data Mentah ── -->
                <div v-if="activeTab === 'raw'"
                    class="border border-gray-200 rounded-xl overflow-hidden mb-3">
                    <div class="overflow-x-auto">
                        <table class="table table-sm w-full">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th class="text-gray-600 font-semibold text-xs uppercase
                                               tracking-wide py-3 px-4">
                                        Alternatif
                                    </th>
                                    <th v-for="k in kriteria" :key="k.kode"
                                        class="text-center text-gray-600 font-semibold text-xs
                                               uppercase tracking-wide py-3">
                                        {{ k.kode }}: {{ k.nama }}<br>
                                        <span class="normal-case tracking-normal font-normal
                                                     text-gray-400">
                                            ({{ k.jenis?.toLowerCase() === 'benefit' ? 'MAX' : 'MIN' }})
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in pagedRaw" :key="row.nip"
                                    class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-sm text-gray-900">
                                            {{ row.nama }}
                                        </div>
                                        <div class="text-xs text-gray-400 font-mono mt-0.5">
                                            {{ row.jabatan }} · #{{ row.nip }}
                                        </div>
                                    </td>
                                    <td v-for="k in kriteria" :key="k.kode"
                                        class="text-center py-3 text-gray-800 font-medium">
                                        {{ row.nilai[k.kode] }}
                                    </td>
                                </tr>

                                <!-- Optimum row -->
                                <tr class="bg-slate-50 border-t border-gray-200">
                                    <td class="px-4 py-3 text-xs text-gray-500 font-medium">
                                        Nilai {{ optimum.label }}
                                    </td>
                                    <td v-for="k in kriteria" :key="k.kode"
                                        class="text-center py-3">
                                        <span class="font-medium text-gray-800"></span>
                                        <span class="text-xs px-1.5 py-0.5 rounded-full
                                                     font-medium ml-1"
                                            :class="k.jenis?.toLowerCase() === 'benefit'
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-red-100 text-red-600'">
                                            {{ k.jenis?.toLowerCase() === 'benefit' ? 'Max' : 'Min' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Raw -->
                    <div v-if="totalPagesRaw > 1"
                        class="flex items-center justify-between px-4 py-3 border-t
                               border-gray-100 bg-white">
                        <span class="text-xs text-gray-400">
                            {{ pageInfo(pageRaw, totalPagesRaw, rawData.length) }}
                        </span>
                        <div class="flex gap-1">
                            <button
                                @click="pageRaw--"
                                :disabled="pageRaw === 1"
                                class="w-7 h-7 rounded-lg border border-gray-200 bg-white
                                       text-gray-600 text-xs flex items-center justify-center
                                       hover:bg-gray-50 disabled:opacity-30
                                       disabled:cursor-not-allowed">
                                ‹
                            </button>
                            <button
                                v-for="p in totalPagesRaw" :key="p"
                                @click="pageRaw = p"
                                class="w-7 h-7 rounded-lg border text-xs flex items-center
                                       justify-center transition-colors"
                                :class="pageRaw === p
                                    ? 'bg-gray-900 text-white border-gray-900'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'"
                            >
                                {{ p }}
                            </button>
                            <button
                                @click="pageRaw++"
                                :disabled="pageRaw === totalPagesRaw"
                                class="w-7 h-7 rounded-lg border border-gray-200 bg-white
                                       text-gray-600 text-xs flex items-center justify-center
                                       hover:bg-gray-50 disabled:opacity-30
                                       disabled:cursor-not-allowed">
                                ›
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── Tab: Normalisasi ── -->
                <div v-if="activeTab === 'norm'"
                    class="border border-gray-200 rounded-xl overflow-hidden mb-3">
                    <div class="overflow-x-auto">
                        <table class="table table-sm w-full">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th class="text-gray-600 font-semibold text-xs uppercase
                                               tracking-wide py-3 px-4">
                                        Alternatif
                                    </th>
                                    <th v-for="k in kriteria" :key="k.kode"
                                        class="text-center text-gray-600 font-semibold text-xs
                                               uppercase tracking-wide py-3">
                                        {{ k.kode }}: {{ k.nama }}<br>
                                        <span class="normal-case tracking-normal font-normal
                                                     text-gray-400">
                                            Bobot {{ k.bobot }}
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in pagedNorm" :key="row.nip"
                                    class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-sm text-gray-900">
                                            {{ row.nama }}
                                        </div>
                                        <div class="text-xs text-gray-400 font-mono mt-0.5">
                                            {{ row.jabatan }} · #{{ row.nip }}
                                        </div>
                                    </td>
                                    <td v-for="k in kriteria" :key="k.kode"
                                        class="text-center py-3 text-gray-800 font-medium">
                                        {{ row.norm[k.kode].toFixed(2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Norm -->
                    <div v-if="totalPagesNorm > 1"
                        class="flex items-center justify-between px-4 py-3 border-t
                               border-gray-100 bg-white">
                        <span class="text-xs text-gray-400">
                            {{ pageInfo(pageNorm, totalPagesNorm, normData.length) }}
                        </span>
                        <div class="flex gap-1">
                            <button
                                @click="pageNorm--"
                                :disabled="pageNorm === 1"
                                class="w-7 h-7 rounded-lg border border-gray-200 bg-white
                                       text-gray-600 text-xs flex items-center justify-center
                                       hover:bg-gray-50 disabled:opacity-30
                                       disabled:cursor-not-allowed">
                                ‹
                            </button>
                            <button
                                v-for="p in totalPagesNorm" :key="p"
                                @click="pageNorm = p"
                                class="w-7 h-7 rounded-lg border text-xs flex items-center
                                       justify-center transition-colors"
                                :class="pageNorm === p
                                    ? 'bg-gray-900 text-white border-gray-900'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'"
                            >
                                {{ p }}
                            </button>
                            <button
                                @click="pageNorm++"
                                :disabled="pageNorm === totalPagesNorm"
                                class="w-7 h-7 rounded-lg border border-gray-200 bg-white
                                       text-gray-600 text-xs flex items-center justify-center
                                       hover:bg-gray-50 disabled:opacity-30
                                       disabled:cursor-not-allowed">
                                ›
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── Tab: Nilai Akhir ── -->
                <div v-if="activeTab === 'weighted'"
                    class="border border-gray-200 rounded-xl overflow-hidden mb-3">
                    <div class="overflow-x-auto">
                        <table class="table table-sm w-full">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th class="text-gray-600 font-semibold text-xs uppercase
                                               tracking-wide py-3 px-4 w-10">
                                        No
                                    </th>
                                    <th class="text-gray-600 font-semibold text-xs uppercase
                                               tracking-wide py-3 px-4">
                                        Alternatif
                                    </th>
                                    <th v-for="k in kriteria" :key="k.kode"
                                        class="text-center text-gray-600 font-semibold text-xs
                                               uppercase tracking-wide py-3">
                                        {{ k.kode }} × {{ k.bobot > 1
                                            ? (k.bobot / 100).toFixed(2)
                                            : k.bobot }}
                                    </th>
                                    <th class="text-center text-gray-600 font-semibold text-xs
                                               uppercase tracking-wide py-3">
                                        Nilai akhir
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in pagedWeighted" :key="row.nip"
                                    class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center justify-center
                                                     w-6 h-6 rounded-full text-xs font-medium"
                                            :class="rankClass(row.peringkat)">
                                            {{ row.peringkat }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-sm text-gray-900">
                                            {{ row.nama }}
                                        </div>
                                        <div class="text-xs text-gray-400 font-mono mt-0.5">
                                            {{ row.jabatan }} · #{{ row.nip }}
                                        </div>
                                    </td>
                                    <td v-for="k in kriteria" :key="k.kode"
                                        class="text-center py-3 text-gray-700">
                                        {{ row.weighted[k.kode].toFixed(2) }}
                                    </td>
                                    <td class="text-center py-3 font-semibold text-gray-900">
                                        {{ row.nilai_akhir.toFixed(2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Weighted -->
                    <div v-if="totalPagesWeighted > 1"
                        class="flex items-center justify-between px-4 py-3 border-t
                               border-gray-100 bg-white">
                        <span class="text-xs text-gray-400">
                            {{ pageInfo(pageWeighted, totalPagesWeighted, weightedData.length) }}
                        </span>
                        <div class="flex gap-1">
                            <button
                                @click="pageWeighted--"
                                :disabled="pageWeighted === 1"
                                class="w-7 h-7 rounded-lg border border-gray-200 bg-white
                                       text-gray-600 text-xs flex items-center justify-center
                                       hover:bg-gray-50 disabled:opacity-30
                                       disabled:cursor-not-allowed">
                                ‹
                            </button>
                            <button
                                v-for="p in totalPagesWeighted" :key="p"
                                @click="pageWeighted = p"
                                class="w-7 h-7 rounded-lg border text-xs flex items-center
                                       justify-center transition-colors"
                                :class="pageWeighted === p
                                    ? 'bg-gray-900 text-white border-gray-900'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'"
                            >
                                {{ p }}
                            </button>
                            <button
                                @click="pageWeighted++"
                                :disabled="pageWeighted === totalPagesWeighted"
                                class="w-7 h-7 rounded-lg border border-gray-200 bg-white
                                       text-gray-600 text-xs flex items-center justify-center
                                       hover:bg-gray-50 disabled:opacity-30
                                       disabled:cursor-not-allowed">
                                ›
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between mt-2">
                    <span class="text-xs text-gray-400">
                        {{ activeTab === 'raw'
                            ? `Menampilkan data mentah (R) · ${rawData.length} alternatif`
                            : activeTab === 'norm'
                            ? `Menampilkan matriks normalisasi (N) · ${normData.length} alternatif`
                            : `Menampilkan nilai akhir berbobot (V) · ${weightedData.length} alternatif` }}
                    </span>
                    <button
                        v-if="activeTab === 'weighted'"
                        @click="simpanHasil"
                        :disabled="form.processing"
                        class="btn btn-sm text-white border-none transition-colors"
                        :class="hasExistingData
                            ? 'bg-amber-600 hover:bg-amber-700'
                            : 'bg-green-600 hover:bg-green-700'"
                    >
                        <span v-if="form.processing"
                            class="loading loading-spinner loading-xs"></span>
                        <svg v-if="hasExistingData && !form.processing"
                            class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        {{ hasExistingData ? 'Hitung Ulang & Update' : 'Simpan Hasil & Ranking →' }}
                    </button>
                </div>

            </template>
        </div>
    </MainLayout>
</template>