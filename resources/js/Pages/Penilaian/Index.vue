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
    // Validasi: akumulasi tidak boleh melebihi batas dan independen juga tidak boleh melebihi batas
    if (totalAkumulasiEdit() > TOTAL_HARI.value || adaOverIndependenEdit()) return
    
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
    <Head title="Data Penilaian - SIKAP" />

    <MainLayout>
        <div class="p-6 sm:p-8 bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-gray-100/50">

            <!-- Header -->
            <div class="flex flex-col md:flex-row items-start justify-between mb-8 pb-6 border-b border-gray-100 gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-indigo-600 tracking-tight flex items-center gap-3">
                        Data Absensi Pegawai
                    </h1>
                    <p class="text-sm text-gray-500 mt-2 font-medium">
                        <span v-if="periodeTerpilih">
                            Menampilkan data input penilaian periode 
                            <strong class="text-gray-800 bg-gray-100 px-2 py-0.5 rounded ml-0.5">
                                {{ periodeTerpilih.bulan }} {{ periodeTerpilih.tahun }}
                            </strong>
                        </span>
                        <span v-else>Pilih periode untuk melihat data yang sudah diinput</span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-regular fa-calendar-days text-gray-400 group-hover:text-blue-500 transition-colors"></i>
                        </div>
                        <select v-model="periodeId"
                            class="select bg-white text-gray-800 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 pl-10 min-w-[200px] h-11 rounded-xl font-medium">
                            <option value="">-- Pilih Periode --</option>
                            <option v-for="p in periodeList" :key="p.id" :value="p.id">
                                {{ p.bulan }} {{ p.tahun }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!periodeId"
                class="flex flex-col items-center justify-center py-20 text-center bg-slate-50/50 rounded-3xl border border-dashed border-gray-200">
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 flex items-center justify-center mb-4 border border-indigo-100 shadow-sm">
                    <i class="fa-solid fa-list-check text-2xl text-indigo-500"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-800 mb-2">Belum Ada Periode Dipilih</h2>
                <p class="text-sm text-gray-500 max-w-sm leading-relaxed mb-6">
                    Pilih periode penilaian dari dropdown di atas untuk melihat data input dan hasil penilaian pegawai.
                </p>
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-600 rounded-lg text-xs font-semibold border border-blue-100">
                    <i class="fa-solid fa-circle-info"></i>
                    Tip: Mulai dari periode aktif terbaru
                </div>
            </div>

            <!-- Data Section -->
            <template v-else>

                <!-- Stat Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                    <div class="bg-gradient-to-br from-white to-blue-50/30 border border-gray-100 rounded-2xl p-5 relative overflow-hidden shadow-sm hover:shadow-md transition-shadow group">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-50 rounded-full blur-xl group-hover:bg-blue-100 transition-colors"></div>
                        <div class="text-[11px] text-gray-500 uppercase tracking-widest font-bold mb-1 relative z-10">Total Pegawai Aktif</div>
                        <div class="text-3xl font-extrabold text-blue-600 mb-1 relative z-10">{{ karyawan.length }}</div>
                        <div class="absolute right-4 top-4 w-10 h-10 rounded-xl bg-blue-100/50 flex items-center justify-center text-blue-600 shadow-sm">
                            <i class="fa-solid fa-users"></i>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-white to-emerald-50/30 border border-gray-100 rounded-2xl p-5 relative overflow-hidden shadow-sm hover:shadow-md transition-shadow group">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-50 rounded-full blur-xl group-hover:bg-emerald-100 transition-colors"></div>
                        <div class="text-[11px] text-gray-500 uppercase tracking-widest font-bold mb-1 relative z-10">Total Kriteria</div>
                        <div class="text-3xl font-extrabold text-emerald-600 mb-1 relative z-10">{{ kriteria.length }}</div>
                        <div class="absolute right-4 top-4 w-10 h-10 rounded-xl bg-emerald-100/50 flex items-center justify-center text-emerald-600 shadow-sm">
                            <i class="fa-solid fa-list-check"></i>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-white to-amber-50/30 border border-gray-100 rounded-2xl p-5 relative overflow-hidden shadow-sm hover:shadow-md transition-shadow group">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-50 rounded-full blur-xl group-hover:bg-amber-100 transition-colors"></div>
                        <div class="text-[11px] text-gray-500 uppercase tracking-widest font-bold mb-1 relative z-10">Hari Kerja</div>
                        <div class="text-3xl font-extrabold text-amber-600 mb-1 relative z-10">{{ TOTAL_HARI }}<span class="text-base font-semibold text-amber-600/70 ml-1">hari</span></div>
                        <div class="absolute right-4 top-4 w-10 h-10 rounded-xl bg-amber-100/50 flex items-center justify-center text-amber-600 shadow-sm">
                            <i class="fa-regular fa-calendar"></i>
                        </div>
                    </div>
                </div>

                <!-- Tabel -->
                <div class="border border-gray-200 rounded-2xl overflow-hidden shadow-sm bg-white">
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead class="bg-slate-50/80 border-b border-gray-100">
                                <tr>
                                    <th class="text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest text-left">
                                        Pegawai
                                    </th>
                                    <th v-for="k in kriteria" :key="k.kode"
                                        class="text-center py-4 px-2 text-[10px] uppercase tracking-widest text-gray-500 font-bold">
                                        <div class="flex flex-col items-center gap-1">
                                            {{ k.nama_kriteria }}
                                        </div>
                                    </th>
                                    <th class="text-center py-4 px-5 text-[10px] uppercase tracking-widest text-gray-500 font-bold w-20">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="kar in karyawan" :key="kar.nip"
                                    class="hover:bg-blue-50/30 transition-colors">
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-sm text-gray-900">
                                            {{ kar.nama || kar.nama_karyawan }}
                                        </div>
                                        <div class="text-[11px] text-gray-400 font-mono mt-0.5">
                                            {{ kar.jabatan }} <span class="mx-1 text-gray-300">|</span> NIP: {{ kar.nip }}
                                        </div>
                                    </td>
                                    <td v-for="k in kriteria" :key="k.kode"
                                        class="text-center py-4 font-semibold text-gray-800 text-sm">
                                        <div class="inline-flex items-center justify-center min-w-[36px] h-8 rounded-lg bg-gray-50 border border-gray-100">
                                            {{ getNilai(kar.penilaian, k.kode) }}
                                        </div>
                                    </td>
                                    <td class="text-center py-4 px-5">
                                        <button
                                            @click="openModal(kar)"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center bg-amber-50 text-amber-600 hover:bg-amber-100 hover:text-amber-700 transition-colors border border-amber-200"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="karyawan.length === 0">
                                    <td :colspan="2 + kriteria.length"
                                        class="text-center py-12 bg-slate-50/50">
                                        <div class="flex flex-col items-center justify-center text-gray-400">
                                            <i class="fa-solid fa-file-circle-xmark text-4xl mb-3 text-gray-300"></i>
                                            <p class="font-medium">Belum ada data penilaian untuk periode ini.</p>
                                            <a href="/penilaian/create"
                                                class="btn btn-sm mt-4 bg-blue-600 hover:bg-blue-700 text-white border-none rounded-lg px-4 shadow-sm">
                                                + Input Data Sekarang
                                            </a>
                                        </div>
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
            class="fixed inset-0 z-50 flex items-center justify-center p-4 backdrop-blur-sm"
            style="background:rgba(15, 23, 42, 0.4)"
            @click.self="closeModal">

            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all border border-gray-100">

                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-slate-50/50">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 tracking-tight">
                            Edit Data Penilaian
                        </h2>
                        <p class="text-[11px] text-gray-500 mt-1 uppercase tracking-wider font-semibold">
                            Periode: {{ periodeTerpilih?.bulan }} {{ periodeTerpilih?.tahun }}
                            <span class="text-indigo-600 ml-1 bg-indigo-50 px-1.5 py-0.5 rounded">
                                {{ TOTAL_HARI }} HK
                            </span>
                        </p>
                    </div>
                    <button @click="closeModal"
                        class="w-8 h-8 flex items-center justify-center rounded-xl text-gray-400 hover:text-gray-700 hover:bg-gray-200 transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-5 space-y-4 overflow-y-auto max-h-[60vh]">

                    <!-- Info Karyawan -->
                    <div class="flex items-center gap-3 p-3 bg-blue-50/50 rounded-xl border border-blue-100/50">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">
                            {{ selectedKaryawan?.nama?.charAt(0) || selectedKaryawan?.nama_karyawan?.charAt(0) || '?' }}
                        </div>
                        <div>
                            <div class="text-sm font-bold text-gray-900">
                                {{ selectedKaryawan?.nama || selectedKaryawan?.nama_karyawan }}
                            </div>
                            <div class="text-xs text-gray-500 font-mono mt-0.5">
                                {{ selectedKaryawan?.jabatan }} <span class="mx-1">|</span> NIP: {{ selectedKaryawan?.nip }}
                            </div>
                        </div>
                    </div>

                    <!-- Input per Kriteria -->
                    <div class="space-y-3">
                        <div v-for="k in kriteria" :key="k.kode"
                            class="flex items-center justify-between p-3 rounded-xl border border-gray-100 bg-white shadow-sm hover:border-blue-100 transition-colors">
                            <div>
                                <div class="text-sm font-semibold text-gray-800">
                                    {{ k.nama_kriteria }}
                                </div>
                                <span
                                    class="text-[10px] px-2 py-0.5 rounded uppercase tracking-wider font-bold mt-1.5 inline-block"
                                    :class="k.kelompok === 'Akumulasi'
                                        ? 'bg-blue-50 text-blue-600'
                                        : 'bg-purple-50 text-purple-600'">
                                    {{ k.kelompok }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model.number="editForm.nilai[k.kode]"
                                    type="number"
                                    min="0"
                                    class="input input-sm w-20 text-center font-semibold rounded-lg shadow-inner"
                                    :class="(k.kelompok === 'Akumulasi' && totalAkumulasiEdit() > TOTAL_HARI) || (k.kelompok === 'Independen' && editForm.nilai[k.kode] > TOTAL_HARI)
                                        ? 'border-red-300 bg-red-50 text-red-700 focus:border-red-500 focus:ring-red-200'
                                        : 'border-gray-200 bg-gray-50 text-gray-900 focus:border-blue-500 focus:bg-white'"
                                />
                                <span class="text-xs font-medium text-gray-400 w-6">
                                    {{ k.kelompok === 'Independen' ? 'x' : 'hr' }}
                                </span>
                            </div>           
                        </div>
                    </div>

                    <!-- Tracker Total Akumulasi -->
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-gray-100">
                        <span class="text-sm font-semibold text-gray-600">Total Akumulasi</span>
                        <span class="text-sm font-extrabold px-3 py-1 rounded-lg"
                            :class="totalAkumulasiEdit() !== TOTAL_HARI ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'">
                            {{ totalAkumulasiEdit() }} / {{ TOTAL_HARI }}
                        </span>
                    </div>

                    <!-- Alert Validasi Kesalahan -->
                    <div v-if="totalAkumulasiEdit() > TOTAL_HARI"
                        class="flex items-start gap-2.5 px-3 py-2.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 font-medium">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500 mt-0.5"></i>
                        Total kriteria akumulasi melebihi {{ TOTAL_HARI }} hari kerja.
                    </div>

                    <div v-if="adaOverIndependenEdit()"
                        class="flex items-start gap-2.5 px-3 py-2.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 font-medium">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500 mt-0.5"></i>
                        Nilai kriteria independen tidak boleh melebihi batas {{ TOTAL_HARI }} kali.
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
                        :disabled="editForm.processing || totalAkumulasiEdit() > TOTAL_HARI || adaOverIndependenEdit()"
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