<script setup>
import { ref, computed, watch } from 'vue'
import { router, useForm, Head } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    karyawan:    Array,
    kriteria:    Array,
    periodeList: Array,
    periodeId:   [String, Number, null],
})

const periodeId = ref(props.periodeId ?? '')

watch(periodeId, (val) => {
    router.get('/penilaian', { periode_id: val }, {
        preserveState: true,
        replace: true,
    })
})

const periodeTerpilih = computed(() =>
    props.periodeList.find(p => p.id == periodeId.value)
)

// ── Hari kerja dinamis dari periode terpilih ──
const TOTAL_HARI = computed(() =>
    periodeTerpilih.value?.hari_kerja ?? 20
)

// ── GROUPING DINAMIS BERDASARKAN KELOMPOK DI DATABASE ──
const groupAkumulasi = computed(() => 
    props.kriteria.filter(k => k.kelompok === 'Akumulasi').map(k => k.kode)
)

const groupIndependen = computed(() => 
    props.kriteria.filter(k => k.kelompok === 'Independen').map(k => k.kode)
)

// ── Modal State ──
const showModal        = ref(false)
const selectedKaryawan = ref(null)

const editForm = useForm({
    periode_id: '',
    nilai: {}
})

function openModal(kar) {
    selectedKaryawan.value  = kar
    editForm.periode_id     = periodeId.value
    editForm.nilai          = Object.fromEntries(
        props.kriteria.map(k => [
            k.kode,
            parseInt(getNilaiRaw(kar.penilaian, k.kode))
        ])
    )
    showModal.value = true
}

function closeModal() {
    showModal.value        = false
    selectedKaryawan.value = null
    editForm.reset()
}

// ── Hitung Total Akumulasi di Form Modal Edit ──
function totalAkumulasiEdit() {
    return groupAkumulasi.value.reduce((total, kode) => {
        return total + Number(editForm.nilai[kode] ?? 0)
    }, 0)
}

// ── Cek Apakah Ada Nilai Independen yang Melebihi Batas ──
function adaOverIndependenEdit() {
    return groupIndependen.value.some(kode => Number(editForm.nilai[kode] ?? 0) > TOTAL_HARI.value)
}

function saveEdit() {
    // Validasi ketat disamakan dengan Controller (Harus tepat TOTAL_HARI & tidak boleh over di kriteria independen)
    if (totalAkumulasiEdit() !== TOTAL_HARI.value || adaOverIndependenEdit()) return
    
    editForm.put(`/penilaian/${selectedKaryawan.value.nip}`, {
        onSuccess: () => closeModal()
    })
}

function getNilaiRaw(penilaianArr, kode) {
    const found = penilaianArr?.find(p => p.kode_kriteria === kode)
    return found ? found.nilai : 0
}

function getNilai(penilaianArr, kode) {
    const found = penilaianArr?.find(p => p.kode_kriteria === kode)
    return found ? parseInt(found.nilai) : '-'
}
</script>

<template>
    <Head title="" />

    <MainLayout>
        <div class="p-6 bg-white rounded-lg shadow-md">

            <!-- Header -->
            <div class="flex items-start justify-between mb-6 flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Data Absensi Pegawai</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        <span v-if="periodeTerpilih">
                            Menampilkan data penilaian · 
                            <strong class="text-gray-700">
                                {{ periodeTerpilih.bulan }} {{ periodeTerpilih.tahun }}
                            </strong>
                        </span>
                        <span v-else>Pilih periode untuk melihat data yang sudah diinput</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <svg class="absolute left-3 top-2.5 w-3.5 h-3.5 text-gray-400 pointer-events-none"
                            viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="1" y="3" width="14" height="12" rx="2"/>
                            <path d="M1 7h14M5 1v4M11 1v4"/>
                        </svg>
                        <select v-model="periodeId"
                            class="select select-bordered select-sm pl-8 min-w-[180px] bg-white
                                   text-gray-800 border-gray-300 shadow-sm focus:border-blue-500">
                            <option value="">-- Pilih Periode --</option>
                            <option v-for="p in periodeList" :key="p.id" :value="p.id">
                                {{ p.bulan }} / {{ p.tahun }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!periodeId"
                class="flex flex-col items-center justify-center py-24 text-center">
                <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center mb-4">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                        stroke="#4F46E5" stroke-width="1.5">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <path d="M3 9h18M9 4v5M15 4v5"/>
                        <path d="M8 13h2M8 17h6" stroke-linecap="round"/>
                    </svg>
                </div>
                <h2 class="text-base font-semibold text-gray-800 mb-2">Belum Ada Periode Dipilih</h2>
                <p class="text-sm text-gray-500 max-w-xs leading-relaxed mb-4">
                    Pilih periode penilaian dari dropdown di atas untuk melihat data input
                    dan hasil penilaian pegawai.
                </p>
                <div class="flex items-center gap-1.5 text-xs text-blue-500">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="#3B82F6">
                        <circle cx="6" cy="6" r="6"/>
                        <path d="M6 5v4M6 3.5h.01" stroke="#fff" stroke-width="1.2"
                            stroke-linecap="round"/>
                    </svg>
                    Tip: Mulai dari periode aktif terbaru
                </div>
            </div>

            <!-- Data Section -->
            <template v-else>

                <!-- Stat Cards -->
                <div class="grid grid-cols-3 gap-3 mb-5">
                    <div class="bg-slate-50 border border-gray-200 rounded-xl px-4 py-3">
                        <div class="text-xs text-gray-400 uppercase tracking-wide font-medium">
                            Total Pegawai Aktif
                        </div>
                        <div class="text-xl font-semibold text-gray-900 mt-1">
                            {{ karyawan.length }}
                        </div>
                    </div>
                    <div class="bg-slate-50 border border-gray-200 rounded-xl px-4 py-3">
                        <div class="text-xs text-gray-400 uppercase tracking-wide font-medium">
                            Total Kriteria
                        </div>
                        <div class="text-xl font-semibold text-gray-900 mt-1">
                            {{ kriteria.length }}
                        </div>
                    </div>
                    <div class="bg-slate-50 border border-gray-200 rounded-xl px-4 py-3">
                        <div class="text-xs text-gray-400 uppercase tracking-wide font-medium">
                            Hari Kerja
                        </div>
                        <div class="text-xl font-semibold text-gray-900 mt-1">
                            {{ TOTAL_HARI }} hari
                        </div>
                    </div>
                </div>

                <!-- Tabel -->
                <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="table table-sm w-full">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th class="text-gray-700 font-bold py-3 px-4 text-xs uppercase tracking-wide">
                                        Pegawai
                                    </th>
                                    <th v-for="k in kriteria" :key="k.kode"
                                        class="text-center py-3 text-xs uppercase tracking-wide text-gray-600 font-bold">
                                        <div class="flex flex-col items-center gap-1">
                                            {{ k.nama_kriteria }}
                                        </div>
                                    </th>
                                    <th class="text-center py-3 text-xs uppercase tracking-wide text-gray-600 font-bold">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="kar in karyawan" :key="kar.nip"
                                    class="border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-sm text-gray-900">
                                            {{ kar.nama || kar.nama_karyawan }}
                                        </div>
                                        <div class="text-xs text-gray-400 font-mono mt-0.5">
                                            {{ kar.jabatan }} · #{{ kar.nip }}
                                        </div>
                                    </td>
                                    <td v-for="k in kriteria" :key="k.kode"
                                        class="text-center py-3 font-medium text-gray-800">
                                        {{ getNilai(kar.penilaian, k.kode) }}
                                    </td>
                                    <td class="text-center py-3 px-4">
                                        <button
                                            @click="openModal(kar)"
                                            class="btn btn-sm btn-warning text-black"
                                            title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="karyawan.length === 0">
                                    <td :colspan="2 + kriteria.length"
                                        class="text-center py-12 text-gray-400 text-sm">
                                        <svg class="w-10 h-10 mx-auto mb-3 text-gray-300"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="1.5"
                                                d="M9 17v-2m3 2v-4m3 4v-6M5 20h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z"/>
                                        </svg>
                                        Belum ada data penilaian untuk periode ini.
                                        <br>
                                        <a href="/penilaian/create"
                                            class="text-blue-500 hover:underline text-xs mt-1 inline-block">
                                            + Input data sekarang
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </template>
        </div>

        <!-- ── MODAL EDIT ── -->
        <div v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background:rgba(0,0,0,0.4)"
            @click.self="closeModal">

            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden">

                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">
                            Edit Data Penilaian
                        </h2>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ selectedKaryawan?.nama || selectedKaryawan?.nama_karyawan }} ·
                            {{ periodeTerpilih?.bulan }} {{ periodeTerpilih?.tahun }}
                            <span class="text-blue-500 font-medium ml-1">
                                ({{ TOTAL_HARI }} hari kerja)
                            </span>
                        </p>
                    </div>
                    <button @click="closeModal"
                        class="w-7 h-7 flex items-center justify-center rounded-lg
                               text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                        <svg width="14" height="14" viewBox="0 0 12 12" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M2 2l8 8M10 2l-8 8" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 space-y-3 overflow-y-auto max-h-[60vh]">

                    <!-- Info Karyawan -->
                    <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-gray-100">
                        <div>
                            <div class="text-sm font-medium text-gray-900">
                                {{ selectedKaryawan?.nama || selectedKaryawan?.nama_karyawan }}
                            </div>
                            <div class="text-xs text-gray-400 font-mono">
                                {{ selectedKaryawan?.jabatan }} · #{{ selectedKaryawan?.nip }}
                            </div>
                        </div>
                    </div>

                    <!-- Input per Kriteria -->
                    <div v-for="k in kriteria" :key="k.kode"
                        class="flex items-center justify-between py-2.5 border-b
                               border-gray-100 last:border-0">
                        <div>
                            <div class="text-sm font-medium text-gray-800">
                                {{ k.nama_kriteria }}
                            </div>
                            <span
                                class="text-xs px-2 py-0.5 rounded-full font-medium mt-0.5 inline-block border capitalize"
                                :class="k.kelompok === 'Akumulasi'
                                    ? 'bg-blue-50 text-blue-700 border-blue-200'
                                    : 'bg-purple-50 text-purple-700 border-purple-200'">
                                {{ k.kelompok }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                v-model.number="editForm.nilai[k.kode]"
                                type="number"
                                min="0"
                                class="input input-bordered input-sm w-24 text-center bg-white text-gray-800"
                                :class="(k.kelompok === 'Akumulasi' && totalAkumulasiEdit() !== TOTAL_HARI) || (k.kelompok === 'Independen' && editForm.nilai[k.kode] > TOTAL_HARI)
                                    ? 'border-red-400 bg-red-50 text-red-700'
                                    : 'border-gray-300 focus:border-blue-500'"
                            />
                            <span class="text-xs text-gray-400 w-8">
                                {{ k.kelompok === 'Independen' ? 'kali' : 'hari' }}
                            </span>
                        </div>           
                    </div>

                    <!-- Tracker Total Akumulasi -->
                    <div class="flex items-center justify-between pt-2 px-1">
                        <span class="text-sm text-gray-500">Total Hari Absensi (Akumulasi)</span>
                        <span class="text-sm font-semibold"
                            :class="totalAkumulasiEdit() !== TOTAL_HARI ? 'text-red-600' : 'text-green-600'">
                            {{ totalAkumulasiEdit() }} / {{ TOTAL_HARI }} hari
                        </span>
                    </div>

                    <!-- Alert Validasi Kesalahan -->
                    <div v-if="totalAkumulasiEdit() !== TOTAL_HARI"
                        class="flex items-center gap-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700">
                        <svg width="13" height="13" viewBox="0 0 12 12" fill="none">
                            <circle cx="6" cy="6" r="5.5" stroke="#D97706" stroke-width="1.5"/>
                            <path d="M6 3.5v3M6 8h.01" stroke="#D97706" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        Total kriteria akumulasi harus tepat berdurasi {{ TOTAL_HARI }} hari.
                    </div>

                    <div v-if="adaOverIndependenEdit()"
                        class="flex items-center gap-2 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700">
                        <svg width="13" height="13" viewBox="0 0 12 12" fill="none">
                            <circle cx="6" cy="6" r="5.5" stroke="#EF4444" stroke-width="1.5"/>
                            <path d="M6 3.5v3M6 8h.01" stroke="#EF4444" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        Nilai kriteria independen tidak boleh melebihi {{ TOTAL_HARI }}.
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-2 px-6 py-4 border-t
                            border-gray-100 bg-gray-50">
                    <button @click="closeModal"
                        class="btn btn-sm btn-ghost bg-white border border-gray-200
                               text-gray-600 hover:bg-gray-100 px-5">
                        Batal
                    </button>
                    <button
                        @click="saveEdit"
                        :disabled="editForm.processing || totalAkumulasiEdit() !== TOTAL_HARI || adaOverIndependenEdit()"
                        class="btn btn-sm bg-blue-600 hover:bg-blue-700 text-white border-none px-5"
                    >
                        <span v-if="editForm.processing"
                            class="loading loading-spinner loading-xs"></span>
                        Simpan
                    </button>
                </div>
            </div>
        </div>

    </MainLayout>
</template>