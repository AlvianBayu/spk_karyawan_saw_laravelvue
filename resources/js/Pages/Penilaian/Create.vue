<script setup>
import { useForm, Head } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    periodeList:         Array,
    kriteria:            Array,
    karyawan:            Array,
    periodeSudahDiinput: Array,
})

const search    = ref('')
const activeTab = ref('all')

// ── Pagination ──
const PER_PAGE   = ref(10)
const currentPage = ref(1)

const form = useForm({
    periode_id: '',
    penilaian: props.karyawan.map(k => ({
        nip:     k.nip,
        nama:    k.nama || k.nama_karyawan || '-',
        jabatan: k.jabatan || '-',
        nilai:   Object.fromEntries(props.kriteria.map(kr => [kr.kode, 0]))
    }))
})

// ── GROUPING DINAMIS ──
const groupAkumulasi = computed(() =>
    props.kriteria.filter(k => k.kelompok === 'Akumulasi').map(k => k.kode)
)

const groupIndependen = computed(() =>
    props.kriteria.filter(k => k.kelompok === 'Independen').map(k => k.kode)
)

// ── TOTAL_HARI dinamis ──
const TOTAL_HARI = computed(() => {
    if (!form.periode_id) return 20
    const p = props.periodeList.find(p => p.id == form.periode_id)
    return p?.hari_kerja ?? 20
})

const periodeBlocked = computed(() =>
    form.periode_id !== '' &&
    props.periodeSudahDiinput.includes(Number(form.periode_id))
)

const periodeTerpilih = computed(() =>
    props.periodeList.find(p => p.id == form.periode_id)
)

function totalAkumulasi(nilai) {
    return groupAkumulasi.value.reduce((a, k) => a + Number(nilai[k] ?? 0), 0)
}

function isInputError(row, kriteriaItem) {
    if (kriteriaItem.kelompok === 'Akumulasi' && totalAkumulasi(row.nilai) > TOTAL_HARI.value) return true
    if (kriteriaItem.kelompok === 'Independen' && Number(row.nilai[kriteriaItem.kode] ?? 0) > TOTAL_HARI.value) return true
    return false
}

const jumlahDone = computed(() =>
    form.penilaian.filter(r => totalAkumulasi(r.nilai) === TOTAL_HARI.value).length
)

const jumlahPending = computed(() =>
    form.penilaian.filter(r => totalAkumulasi(r.nilai) !== TOTAL_HARI.value).length
)

const adaOverAkumulasi = computed(() =>
    form.penilaian.some(r => totalAkumulasi(r.nilai) > TOTAL_HARI.value)
)

const adaOverIndependen = computed(() =>
    form.penilaian.some(r =>
        groupIndependen.value.some(kode => Number(r.nilai[kode] ?? 0) > TOTAL_HARI.value)
    )
)

// ── Filter ──
const filteredPenilaian = computed(() => {
    // HAPUS baris ini: currentPage.value = 1 
    return form.penilaian.filter(row => {
        const matchSearch = row.nama.toLowerCase().includes(search.value.toLowerCase())
        const total       = totalAkumulasi(row.nilai)
        const matchTab    =
            activeTab.value === 'all' ||
            (activeTab.value === 'done'    && total === TOTAL_HARI.value) ||
            (activeTab.value === 'pending' && total !== TOTAL_HARI.value)
        return matchSearch && matchTab
    })
})

// Tambahkan Watcher ini: Reset ke halaman 1 HANYA saat search atau tab berubah
watch([search, activeTab], () => {
    currentPage.value = 1
})

// ── Pagination computed ──
const totalPages = computed(() =>
    Math.ceil(filteredPenilaian.value.length / PER_PAGE.value)
)

const pagedPenilaian = computed(() => {
    const start = (currentPage.value - 1) * PER_PAGE.value
    return filteredPenilaian.value.slice(start, start + PER_PAGE.value)
})

const showingStart = computed(() =>
    filteredPenilaian.value.length === 0
        ? 0
        : (currentPage.value - 1) * PER_PAGE.value + 1
)

const showingEnd = computed(() =>
    Math.min(currentPage.value * PER_PAGE.value, filteredPenilaian.value.length)
)

function changePage(page) {
    if (page >= 1 && page <= totalPages.value) currentPage.value = page
}

function getIndex(nip) {
    return form.penilaian.findIndex(r => r.nip === nip)
}

function submit() {
    if (
        adaOverAkumulasi.value  ||
        adaOverIndependen.value ||
        jumlahPending.value > 0 ||
        !form.periode_id        ||
        periodeBlocked.value
    ) return
    form.post('/penilaian')
}
</script>

<template>
    <Head title="" />
    <MainLayout>
        <div class="p-6 bg-white rounded-lg shadow-md">

            <!-- Header -->
            <div class="flex items-start justify-between mb-4 flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-semibold flex items-center gap-2 text-gray-900">
                        Penilaian Pegawai
                        <span v-if="form.periode_id && !periodeBlocked"
                            class="text-xs px-2.5 py-0.5 rounded-full font-medium
                                   bg-green-100 text-green-700 border border-green-200">
                            Periode Aktif
                        </span>
                        <span v-if="periodeBlocked"
                            class="text-xs px-2.5 py-0.5 rounded-full font-medium
                                   bg-amber-100 text-amber-700 border border-amber-200">
                            Sudah Diinput
                        </span>
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        <span v-if="periodeTerpilih">
                            {{ periodeTerpilih.bulan }} {{ periodeTerpilih.tahun }} ·
                        </span>
                        {{ karyawan.length }} Pegawai dalam scope
                        <span v-if="periodeTerpilih" class="text-blue-600 font-medium">
                            · {{ TOTAL_HARI }} hari kerja
                        </span>
                    </p>
                </div>

                <!-- Dropdown Periode — selaras dengan halaman lain -->
                <div class="relative">
                    <svg class="absolute left-3 top-2.5 w-3.5 h-3.5 text-gray-400 pointer-events-none"
                        viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="1" y="3" width="14" height="12" rx="2"/>
                        <path d="M1 7h14M5 1v4M11 1v4"/>
                    </svg>
                    <select v-model="form.periode_id"
                        class="select select-bordered select-sm pl-8 min-w-[200px] bg-white
                               border-gray-300 shadow-sm focus:border-blue-500"
                        :class="periodeBlocked
                            ? 'text-amber-700 border-amber-300'
                            : 'text-gray-800'">
                        <option value="">-- Pilih Periode --</option>
                        <option
                            v-for="p in periodeList" :key="p.id" :value="p.id"
                            :style="periodeSudahDiinput.includes(p.id) ? 'color:#92400e' : ''">
                            {{ p.bulan }} / {{ p.tahun }}
                            ({{ p.hari_kerja ?? 20 }} hari)
                            {{ periodeSudahDiinput.includes(p.id) ? '— sudah diinput' : '' }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Alert: Periode blocked -->
            <div v-if="periodeBlocked"
                class="flex items-center justify-between gap-4 mb-5 px-4 py-3
                       bg-amber-50 border border-amber-200 rounded-xl shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center
                                justify-center flex-shrink-0">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <circle cx="8" cy="8" r="7" stroke="#D97706" stroke-width="1.5"/>
                            <path d="M8 5v4M8 11h.01" stroke="#D97706" stroke-width="1.5"
                                stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-amber-800">
                            Periode ini sudah pernah diinput
                        </p>
                        <p class="text-xs text-amber-600 mt-0.5">
                            Data untuk periode
                            <strong>{{ periodeTerpilih?.bulan }} {{ periodeTerpilih?.tahun }}</strong>
                            sudah tersimpan. Gunakan halaman Edit untuk mengubah data.
                        </p>
                    </div>
                </div>
                <a href="/penilaian"
                    class="btn btn-sm bg-amber-500 hover:bg-amber-600 text-white
                           border-none whitespace-nowrap flex-shrink-0">
                    Ke halaman Edit →
                </a>
            </div>

            <!-- Alert: Error server -->
            <div v-if="form.errors.periode_id || form.errors.penilaian"
                class="flex items-center gap-3 mb-5 px-4 py-3 bg-red-50 border
                       border-red-200 rounded-xl text-sm text-red-700 shadow-sm">
                <svg width="16" height="16" viewBox="0 0 12 12" fill="none">
                    <circle cx="6" cy="6" r="5.5" stroke="#EF4444" stroke-width="1.5"/>
                    <path d="M6 3.5v3M6 8h.01" stroke="#EF4444" stroke-width="1.5"
                        stroke-linecap="round"/>
                </svg>
                {{ form.errors.periode_id || form.errors.penilaian }}
            </div>

            <!-- Info hari kerja -->
            <div v-if="form.periode_id && !periodeBlocked"
                class="flex items-center gap-2 mb-4 px-3 py-2 bg-blue-50 border
                       border-blue-200 rounded-lg text-xs text-blue-700">
                <svg width="13" height="13" viewBox="0 0 12 12" fill="none">
                    <circle cx="6" cy="6" r="5.5" stroke="#2563EB" stroke-width="1.5"/>
                    <path d="M6 5v4M6 3.5h.01" stroke="#2563EB" stroke-width="1.5"
                        stroke-linecap="round"/>
                </svg>
                Total Absensi <strong class="mx-0.5">Pada Periode</strong> harus tepat
                <strong class="mx-0.5">{{ TOTAL_HARI }} hari</strong>
                sesuai hari kerja periode ini.
            </div>

            <!-- Toolbar -->
            <div class="flex items-center justify-between mt-4 mb-4 flex-wrap gap-3">
                <div class="relative">
                    <svg class="absolute left-2.5 top-2 w-4 h-4 text-gray-400"
                        viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="6.5" cy="6.5" r="4.5"/><path d="M10 10l3 3"/>
                    </svg>
                    <input v-model="search" type="text"
                        placeholder="Cari Pegawai..."
                        class="input input-bordered input-sm pl-8 w-60 bg-white
                               border-gray-300 text-gray-800 shadow-sm focus:border-blue-500"
                        :disabled="periodeBlocked" />
                </div>
                <div class="flex gap-1.5 p-1 bg-gray-200/60 rounded-lg">
                    <button
                        v-for="tab in [
                            { key: 'all',     label: 'Semua',       count: karyawan.length },
                            { key: 'pending', label: 'Belum diisi', count: jumlahPending },
                            { key: 'done',    label: 'Selesai',     count: jumlahDone },
                        ]"
                        :key="tab.key"
                        @click="activeTab = tab.key; currentPage = 1"
                        :disabled="periodeBlocked"
                        class="btn btn-xs rounded-md px-4 border-none transition-all duration-200"
                        :class="activeTab === tab.key
                            ? 'bg-white text-gray-900 shadow-sm font-semibold hover:bg-white'
                            : 'bg-transparent text-gray-500 hover:text-gray-700 font-normal shadow-none'"
                    >
                        {{ tab.label }} ({{ tab.count }})
                    </button>
                </div>
            </div>

            <!-- Tabel -->
            <div class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm mb-3"
                :class="periodeBlocked ? 'opacity-40 pointer-events-none select-none' : ''">
                <div class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="text-gray-900 font-bold py-3 px-4 w-12 text-center">
                                    No
                                </th>
                                <th class="text-gray-900 font-bold py-3 px-4">Pegawai</th>
                                <th v-for="k in kriteria" :key="k.kode"
                                    class="text-center text-gray-600 font-semibold py-3">
                                    <div class="flex flex-col items-center gap-1">
                                        {{ k.nama_kriteria }}
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, index) in pagedPenilaian"
                                :key="row.nip"
                                class="border-b border-gray-100 last:border-0 transition-colors"
                                :class="totalAkumulasi(row.nilai) > TOTAL_HARI ||
                                        groupIndependen.some(kode => Number(row.nilai[kode] ?? 0) > TOTAL_HARI)
                                    ? 'bg-red-50/50 hover:bg-red-50'
                                    : totalAkumulasi(row.nilai) === TOTAL_HARI
                                    ? 'bg-green-50/20 hover:bg-green-50/40'
                                    : 'hover:bg-gray-50/50'"
                            >
                                <td class="px-4 py-3 text-center text-sm font-medium text-gray-500">
                                    {{ showingStart + index }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-sm text-gray-900">
                                        {{ row.nama }}
                                    </div>
                                    <div class="text-xs text-gray-400 font-mono mt-0.5">
                                        {{ row.jabatan }} {{ row.nip }}
                                    </div>
                                </td>
                                <td v-for="k in kriteria" :key="k.kode" class="text-center py-3">
                                    <input
                                        v-model.number="form.penilaian[getIndex(row.nip)].nilai[k.kode]"
                                        type="number"
                                        min="0"
                                        :max="TOTAL_HARI"
                                        class="input input-bordered input-sm w-20 text-center
                                               bg-white text-gray-800 transition-colors focus:ring-1"
                                        :class="isInputError(row, k)
                                            ? 'border-red-400 focus:border-red-500 bg-red-50 text-red-700 focus:ring-red-300'
                                            : k.kelompok === 'Akumulasi'
                                            ? 'border-blue-200 focus:border-blue-400 focus:ring-blue-200'
                                            : 'border-purple-200 focus:border-purple-400 focus:ring-purple-200'"
                                    />
                                </td>
                            </tr>

                            <tr v-if="pagedPenilaian.length === 0">
                                <td :colspan="3 + kriteria.length"
                                    class="text-center py-12 text-gray-400 text-sm">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="1.5"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    Tidak ada data Pegawai yang cocok.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="filteredPenilaian.length > 0"
                class="flex flex-col sm:flex-row items-center justify-between
                       px-1 py-3 mb-4 flex-wrap gap-3">
                <div class="text-sm text-gray-500">
                    Menampilkan
                    <span class="font-semibold text-gray-700">{{ showingStart }}</span>–<span class="font-semibold text-gray-700">{{ showingEnd }}</span>
                    dari
                    <span class="font-semibold text-gray-700">{{ filteredPenilaian.length }}</span>
                    pegawai
                </div>
                <div v-if="totalPages > 1" class="flex gap-1">
                    <button
                        @click="changePage(currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm
                               font-medium text-gray-700 bg-white hover:bg-gray-50
                               disabled:opacity-40 disabled:cursor-not-allowed transition">
                        ‹
                    </button>
                    <button
                        v-for="page in totalPages" :key="page"
                        @click="changePage(page)"
                        class="px-3 py-1.5 border rounded-lg text-sm font-medium transition"
                        :class="currentPage === page
                            ? 'bg-blue-600 text-white border-blue-600'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'">
                        {{ page }}
                    </button>
                    <button
                        @click="changePage(currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm
                               font-medium text-gray-700 bg-white hover:bg-gray-50
                               disabled:opacity-40 disabled:cursor-not-allowed transition">
                        ›
                    </button>
                </div>
            </div>

            <!-- Footer alerts + tombol -->
            <div class="flex items-center justify-between flex-wrap gap-4 mt-2">
                <div class="flex flex-col gap-2">

                    <div v-if="adaOverAkumulasi && !periodeBlocked"
                        class="flex items-center gap-2 text-sm text-red-700 bg-red-50
                               border border-red-200 rounded-lg px-4 py-2.5 shadow-sm">
                        <svg width="14" height="14" viewBox="0 0 12 12" fill="none">
                            <circle cx="6" cy="6" r="5.5" stroke="#EF4444" stroke-width="1.5"/>
                            <path d="M6 3.5v3M6 8h.01" stroke="#EF4444" stroke-width="1.5"
                                stroke-linecap="round"/>
                        </svg>
                        Total kelompok akumulasi melebihi {{ TOTAL_HARI }} hari.
                    </div>

                    <div v-if="adaOverIndependen && !periodeBlocked"
                        class="flex items-center gap-2 text-sm text-red-700 bg-red-50
                               border border-red-200 rounded-lg px-4 py-2.5 shadow-sm">
                        <svg width="14" height="14" viewBox="0 0 12 12" fill="none">
                            <circle cx="6" cy="6" r="5.5" stroke="#EF4444" stroke-width="1.5"/>
                            <path d="M6 3.5v3M6 8h.01" stroke="#EF4444" stroke-width="1.5"
                                stroke-linecap="round"/>
                        </svg>
                        Nilai kriteria Independen tidak boleh melebihi {{ TOTAL_HARI }} hari.
                    </div>

                    <div v-if="jumlahPending > 0 && !adaOverAkumulasi &&
                               !adaOverIndependen && !periodeBlocked && form.periode_id"
                        class="flex items-center gap-2 text-sm text-amber-700 bg-amber-50
                               border border-amber-200 rounded-lg px-4 py-2.5 shadow-sm">
                        <svg width="14" height="14" viewBox="0 0 12 12" fill="none">
                            <path d="M6 11A5 5 0 1 0 6 1a5 5 0 0 0 0 10Z"
                                stroke="#D97706" stroke-width="1.5"/>
                            <path d="M6 3v3.5L8 8" stroke="#D97706" stroke-width="1.5"
                                stroke-linecap="round"/>
                        </svg>
                        Masih ada {{ jumlahPending }} Pegawai yang belum dinilai.
                    </div>
                </div>

                <div class="flex gap-3 ml-auto">
                    <a href="/penilaian"
                        class="btn btn-ghost bg-gray-100 text-gray-700
                               hover:bg-gray-200 border-none px-6">
                        Batal
                    </a>
                    <button
                        class="btn bg-blue-600 hover:bg-blue-700 text-white border-none px-6"
                        :disabled="form.processing || adaOverAkumulasi || adaOverIndependen ||
                                   jumlahPending > 0 || !form.periode_id || periodeBlocked"
                        @click="submit"
                    >
                        <span v-if="form.processing"
                            class="loading loading-spinner loading-xs"></span>
                        Simpan Penilaian
                    </button>
                </div>
            </div>

        </div>
    </MainLayout>
</template>