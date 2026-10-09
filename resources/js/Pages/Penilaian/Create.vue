<script setup>
import { useForm, Head } from '@inertiajs/vue3'
import { ref, computed, watch, onMounted } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    periodeList:           Array,
    kriteria:              Array,
    karyawan:              Array,
    existingNipsByPeriode: Object,
    eligibleNipsByPeriode: Object,
    errors:                Object,
    importErrors:          Array,
})

const selectedPeriodeId = ref('')
const selectedDivisi    = ref('')
const search            = ref('')
const inputMode         = ref('manual') // 'manual' | 'upload'

// ── Upload State & Methods ──
const uploadForm = useForm({
    periode_id: '',
    file: null,
})
const isUploading    = ref(false)
const uploadProgress = ref(0)

function handleFileUpload(e) {
    uploadForm.file = e.target.files[0]
}

function submitUpload() {
    if (!uploadForm.file || !uploadForm.periode_id) return
    
    isUploading.value = true
    uploadProgress.value = 0
    
    const interval = setInterval(() => {
        if (uploadProgress.value < 90) uploadProgress.value += Math.random() * 15
    }, 200)

    uploadForm.post('/penilaian/import', {
        preserveScroll: true,
        onSuccess: () => {
            clearInterval(interval)
            uploadProgress.value = 100
            setTimeout(() => {
                isUploading.value = false
                uploadProgress.value = 0
                uploadForm.reset('file')
            }, 800)
        },
        onError: () => {
            clearInterval(interval)
            isUploading.value = false
            uploadProgress.value = 0
        },
        onFinish: () => {
            clearInterval(interval)
            isUploading.value = false
        }
    })
}

function downloadTemplate() {
    const pId = uploadForm.periode_id || selectedPeriodeId.value || ''
    window.location.href = '/penilaian/download-template' + (pId ? `?periode_id=${pId}` : '')
}

// ── Form State ──
const form = useForm({
    periode_id: '',
    penilaian: []
})

// Group Kriteria
const groupAkumulasi = computed(() =>
    props.kriteria.filter(k => k.kelompok === 'Akumulasi').map(k => k.kode)
)

const groupIndependen = computed(() =>
    props.kriteria.filter(k => k.kelompok === 'Independen').map(k => k.kode)
)

// Hari Kerja dari periode terpilih
const TOTAL_HARI = computed(() => {
    if (!selectedPeriodeId.value) return 20
    const p = props.periodeList.find(p => p.id == selectedPeriodeId.value)
    return p?.hari_kerja ?? 20
})

const periodeTerpilih = computed(() =>
    props.periodeList.find(p => p.id == selectedPeriodeId.value)
)

// Karyawan yang sah/eligible untuk periode terpilih (tidak mencantumkan karyawan baru pada periode lama)
const eligibleKaryawanForPeriode = computed(() => {
    if (!selectedPeriodeId.value) return props.karyawan
    if (!props.eligibleNipsByPeriode || !props.eligibleNipsByPeriode[selectedPeriodeId.value]) {
        return props.karyawan
    }
    const eligibleNips = (props.eligibleNipsByPeriode[selectedPeriodeId.value] || []).map(String)
    return props.karyawan.filter(k => eligibleNips.includes(String(k.nip)))
})

// NIP yang sudah tersimpan untuk periode terpilih
const savedNipsForPeriode = computed(() => {
    if (!selectedPeriodeId.value || !props.existingNipsByPeriode) return []
    const rawSaved = (props.existingNipsByPeriode[selectedPeriodeId.value] || []).map(String)
    const eligibleNips = (props.eligibleNipsByPeriode?.[selectedPeriodeId.value] || []).map(String)
    if (eligibleNips.length > 0) {
        return rawSaved.filter(nip => eligibleNips.includes(nip))
    }
    return rawSaved
})

// List Divisi unik dari pegawai yang ada di periode ini
const listDivisi = computed(() => {
    const divs = eligibleKaryawanForPeriode.value.map(k => k.divisi).filter(Boolean)
    return [...new Set(divs)].sort()
})

// Sync form & penilaian setiap kali periode berubah
function initPenilaianForPeriode() {
    if (!selectedPeriodeId.value) {
        form.periode_id = ''
        form.penilaian = []
        return
    }

    form.periode_id = selectedPeriodeId.value

    const savedNips = savedNipsForPeriode.value
    // Filter pegawai eligible yang BELUM tersimpan nilainya pada periode ini
    const unsavedKaryawan = eligibleKaryawanForPeriode.value.filter(k => !savedNips.includes(String(k.nip)))

    form.penilaian = unsavedKaryawan.map(k => ({
        nip:     k.nip,
        nama:    k.nama || k.nama_karyawan || '-',
        jabatan: k.jabatan || '-',
        divisi:  k.divisi || '-',
        nilai:   Object.fromEntries(props.kriteria.map(kr => [kr.kode, 0]))
    }))
}

watch(selectedPeriodeId, () => {
    initPenilaianForPeriode()
})

watch(() => [props.existingNipsByPeriode, props.eligibleNipsByPeriode], () => {
    initPenilaianForPeriode()
}, { deep: true })

onMounted(() => {
    if (props.periodeList && props.periodeList.length > 0) {
        selectedPeriodeId.value = props.periodeList[0].id
    }
})

function totalAkumulasi(nilai) {
    return groupAkumulasi.value.reduce((a, k) => a + Number(nilai[k] ?? 0), 0)
}

function isInputError(row, kriteriaItem) {
    if (kriteriaItem.kelompok === 'Akumulasi' && totalAkumulasi(row.nilai) > TOTAL_HARI.value) return true
    if (kriteriaItem.kelompok === 'Independen' && Number(row.nilai[kriteriaItem.kode] ?? 0) > TOTAL_HARI.value) return true
    return false
}

// Stats & Badges
const totalEmployeesInPeriode = computed(() => eligibleKaryawanForPeriode.value.length)
const totalSavedInPeriode     = computed(() => savedNipsForPeriode.value.length)
const isPeriodeFullySaved     = computed(() => totalEmployeesInPeriode.value > 0 && totalSavedInPeriode.value >= totalEmployeesInPeriode.value)

// Total Progres dalam Persentase (%)
const progressPercentage = computed(() => {
    if (totalEmployeesInPeriode.value === 0) return 0
    return Math.round((totalSavedInPeriode.value / totalEmployeesInPeriode.value) * 100)
})

const displayedPenilaian = computed(() => {
    return form.penilaian.filter(row => {
        const matchDivisi = !selectedDivisi.value || row.divisi === selectedDivisi.value
        const matchSearch = row.nama.toLowerCase().includes(search.value.toLowerCase()) ||
                            row.nip.toLowerCase().includes(search.value.toLowerCase())
        return matchDivisi && matchSearch
    })
})

const countSiapSimpan = computed(() => {
    return form.penilaian.filter(row => totalAkumulasi(row.nilai) === TOTAL_HARI.value).length
})

const countBelumLengkap = computed(() => {
    return form.penilaian.filter(row => {
        const tot = totalAkumulasi(row.nilai)
        return tot > 0 && tot < TOTAL_HARI.value
    }).length
})

const countBelumDiisi = computed(() => {
    return form.penilaian.filter(row => totalAkumulasi(row.nilai) === 0).length
})

const adaOverAkumulasi = computed(() =>
    form.penilaian.some(r => totalAkumulasi(r.nilai) > TOTAL_HARI.value)
)

const adaOverIndependen = computed(() =>
    form.penilaian.some(r =>
        groupIndependen.value.some(kode => Number(r.nilai[kode] ?? 0) > TOTAL_HARI.value)
    )
)

// Pagination
const PER_PAGE    = ref(10)
const currentPage = ref(1)

watch([search, selectedDivisi, selectedPeriodeId], () => {
    currentPage.value = 1
})

const totalPages = computed(() =>
    Math.ceil(displayedPenilaian.value.length / PER_PAGE.value)
)

const pagedPenilaian = computed(() => {
    const start = (currentPage.value - 1) * PER_PAGE.value
    return displayedPenilaian.value.slice(start, start + PER_PAGE.value)
})

const showingStart = computed(() =>
    displayedPenilaian.value.length === 0 ? 0 : (currentPage.value - 1) * PER_PAGE.value + 1
)

const showingEnd = computed(() =>
    Math.min(currentPage.value * PER_PAGE.value, displayedPenilaian.value.length)
)

function changePage(page) {
    if (page >= 1 && page <= totalPages.value) currentPage.value = page
}

function getIndexInForm(nip) {
    return form.penilaian.findIndex(r => r.nip === nip)
}

function submit() {
    if (!form.periode_id || adaOverAkumulasi.value || adaOverIndependen.value) return

    if (countSiapSimpan.value === 0) {
        alert("Tidak ada data pegawai yang memenuhi syarat kriteria untuk disimpan. Pastikan total absensi akumulasi tepat " + TOTAL_HARI.value + " hari.")
        return
    }

    form.post('/penilaian', {
        preserveScroll: true
    })
}
</script>

<template>
    <Head title="Input Penilaian - SIKAP" />
    <MainLayout>
        <div class="p-6 sm:p-8 bg-white rounded-2xl border border-slate-200">

            <!-- Header & Mode Toggle -->
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8 pb-6 border-b border-slate-100 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                        Input Penilaian Pegawai
                    </h1>
                    <p class="text-sm text-slate-500 mt-1 font-medium">
                        Pilih periode dan divisi untuk mengisi data kriteria absensi & kinerja.
                    </p>
                </div>

                <div class="flex bg-slate-100 p-1.5 rounded-2xl w-full md:w-auto border border-slate-200/60 shadow-inner">
                    <button 
                        @click="inputMode = 'manual'"
                        class="flex-1 md:w-36 py-2 px-4 text-sm font-semibold rounded-xl transition-all duration-300"
                        :class="inputMode === 'manual' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    >
                        <i class="fa-solid fa-keyboard mr-1.5 opacity-75"></i> Input Manual
                    </button>
                    <button 
                        @click="inputMode = 'upload'"
                        class="flex-1 md:w-36 py-2 px-4 text-sm font-semibold rounded-xl transition-all duration-300"
                        :class="inputMode === 'upload' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    >
                        <i class="fa-solid fa-file-excel mr-1.5 opacity-75"></i> Upload Excel
                    </button>
                </div>
            </div>

            <!-- TAB: UPLOAD FILE -->
            <div v-if="inputMode === 'upload'" class="max-w-3xl mx-auto py-4">
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-8 text-center relative overflow-hidden">
                    <h2 class="text-xl font-bold text-slate-800 mb-2 relative z-10">Upload Data Penilaian via Excel</h2>
                    <p class="text-slate-500 text-sm mb-6 relative z-10 max-w-lg mx-auto">Unduh template Excel, isi nilai kriteria per NIP, lalu unggah kembali.</p>
                    
                    <div class="flex flex-col sm:flex-row justify-center items-center gap-4 mb-6 relative z-10">
                        <select v-model="uploadForm.periode_id"
                            class="select bg-white border-slate-200 text-slate-800 rounded-xl focus:border-blue-500 shadow-sm w-full sm:w-60 h-11 text-sm font-medium">
                            <option value="">-- Pilih Periode --</option>
                            <option v-for="p in periodeList" :key="p.id" :value="p.id">
                                {{ p.bulan }} {{ p.tahun }} ({{ p.hari_kerja }} Hari Kerja)
                            </option>
                        </select>

                        <button @click="downloadTemplate" class="btn h-11 bg-white hover:bg-slate-50 text-indigo-700 border border-indigo-200 shadow-sm rounded-xl w-full sm:w-auto px-5 font-semibold transition-all">
                            <i class="fa-solid fa-download mr-2"></i> Download Template
                        </button>
                    </div>

                    <!-- Drag & Drop Area -->
                    <label class="flex justify-center w-full h-44 px-4 transition-all bg-white border-2 border-slate-300 border-dashed rounded-2xl appearance-none cursor-pointer hover:border-blue-400 hover:bg-blue-50/20 group relative z-10 shadow-sm" :class="{'border-blue-500 bg-blue-50/50': uploadForm.file}">
                        <span class="flex items-center space-x-2">
                            <i v-if="!uploadForm.file" class="fa-solid fa-cloud-arrow-up text-3xl text-slate-400 group-hover:text-blue-500 transition-colors"></i>
                            <i v-else class="fa-solid fa-file-excel text-4xl text-emerald-500"></i>
                            
                            <span class="font-medium text-slate-500 group-hover:text-blue-600 transition-colors">
                                <span v-if="uploadForm.file" class="text-emerald-700 text-base font-bold">{{ uploadForm.file.name }}</span>
                                <span v-else>Pilih atau seret file ke sini</span>
                            </span>
                        </span>
                        <input type="file" name="file_upload" class="hidden" accept=".xlsx, .xls, .csv" @change="handleFileUpload" />
                    </label>

                    <!-- Import Errors Alert -->
                    <div v-if="errors.file" class="mt-5 p-4 bg-rose-50 rounded-2xl border border-rose-200 text-left relative z-10 shadow-sm">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-exclamation text-rose-500 mt-1 text-base"></i>
                            <div>
                                <h4 class="font-bold text-rose-800 text-sm">Gagal mengunggah file</h4>
                                <p class="text-sm text-rose-700 mt-0.5">{{ errors.file }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 relative z-10">
                        <button 
                            @click="submitUpload" 
                            class="btn w-full sm:w-auto min-w-[200px] h-11 bg-blue-600 hover:bg-blue-700 text-white border-none rounded-xl transition-colors text-sm font-bold"
                            :disabled="!uploadForm.file || !uploadForm.periode_id || isUploading"
                        >
                            <span v-if="isUploading">
                                <span class="loading loading-spinner loading-xs mr-2"></span> Memproses...
                            </span>
                            <span v-else>
                                <i class="fa-solid fa-upload mr-2"></i> Unggah Data Excel
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB: MANUAL INPUT -->
            <div v-show="inputMode === 'manual'">

                <!-- STEP WIZARD BAR -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    
                    <!-- STEP 1: Pilih Periode -->
                    <div class="p-5 rounded-xl border transition-all duration-300"
                        :class="selectedPeriodeId ? 'bg-white border-blue-200 shadow-sm' : 'bg-slate-50 border-slate-200'">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="flex items-center gap-2.5">
                                <span class="w-7 h-7 rounded-lg bg-blue-600 text-white text-xs font-bold flex items-center justify-center">1</span>
                                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Langkah 1: Pilih Periode</label>
                            </div>

                            <!-- Indikator Progres di Header Card (Rapi & Bebas Overflow) -->
                            <div v-if="selectedPeriodeId" class="flex items-center gap-2 px-2.5 py-1 rounded-lg border text-xs font-bold transition-all shadow-xs"
                                :class="progressPercentage === 100 
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                                    : 'bg-blue-50 text-blue-700 border-blue-200'">
                                <span class="text-[11px] font-semibold text-slate-500">Progres:</span>
                                <span class="font-black text-xs">{{ progressPercentage }}%</span>
                                <div class="w-12 bg-slate-200 rounded-full h-1.5 overflow-hidden ml-0.5 hidden xs:block">
                                    <div class="h-1.5 rounded-full transition-all duration-300"
                                        :class="progressPercentage === 100 ? 'bg-emerald-600' : 'bg-blue-600'"
                                        :style="{ width: progressPercentage + '%' }"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <select v-model="selectedPeriodeId"
                                class="select select-md h-12 bg-white border-slate-300 text-slate-800 shadow-sm focus:border-blue-500 font-bold rounded-xl w-full text-sm">
                                <option value="">-- Pilih Periode --</option>
                                <option v-for="p in periodeList" :key="p.id" :value="p.id">
                                    {{ p.bulan }} {{ p.tahun }} (Hari Kerja: {{ p.hari_kerja }} Hari)
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- STEP 2: Pilih Divisi -->
                    <div class="p-5 rounded-xl border transition-all duration-300"
                        :class="!selectedPeriodeId ? 'bg-slate-50 border-slate-200 opacity-60 pointer-events-none' : selectedDivisi ? 'bg-white border-indigo-200 shadow-sm' : 'bg-white border-slate-200'">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="w-7 h-7 rounded-lg bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">2</span>
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Langkah 2: Pilih Divisi</label>
                        </div>

                        <select v-model="selectedDivisi" :disabled="!selectedPeriodeId"
                            class="select select-md h-12 bg-white border-slate-300 text-slate-800 shadow-sm focus:border-indigo-500 font-bold rounded-xl w-full text-sm">
                            <option value="">Semua Divisi (Tampilkan Semua)</option>
                            <option v-for="d in listDivisi" :key="d" :value="d">
                                Divisi {{ d }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- ALERT: Periode Selesai 100% -->
                <div v-if="selectedPeriodeId && isPeriodeFullySaved" 
                    class="p-5 bg-emerald-50 border border-emerald-200 rounded-2xl mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center flex-shrink-0 shadow-md">
                            <i class="fa-solid fa-check text-lg"></i>
                        </div>
                        <div>
                            <h4 class="font-extrabold text-emerald-900 text-base">Seluruh Pegawai Telah Diinput (100% Selesai)</h4>
                            <p class="text-xs text-emerald-700 mt-0.5">Semua data penilaian untuk periode <strong>{{ periodeTerpilih?.bulan }} {{ periodeTerpilih?.tahun }}</strong> telah tersimpan lengkap.</p>
                        </div>
                    </div>
                    <a href="/penilaian" class="btn btn-sm bg-emerald-600 hover:bg-emerald-700 text-white border-none rounded-xl font-bold whitespace-nowrap shadow-sm">
                        Lihat Data Penilaian →
                    </a>
                </div>

                <!-- ALERT: Server validation errors -->
                <div v-if="form.errors.penilaian || form.errors.periode_id" 
                    class="p-4 bg-rose-50 border border-rose-200 rounded-2xl mb-6 text-sm text-rose-700 flex items-center gap-3 shadow-sm">
                    <i class="fa-solid fa-triangle-exclamation text-rose-500 text-lg"></i>
                    <div class="font-medium">{{ form.errors.penilaian || form.errors.periode_id }}</div>
                </div>

                <!-- STEP 3: INPUT TABLE DATA -->
                <div v-if="selectedPeriodeId && !isPeriodeFullySaved">

                    <!-- Toolbar Search & Summary Pills -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 mb-4">
                        <div class="relative w-full sm:w-64">
                            <i class="fa-solid fa-search absolute left-3 top-3 text-slate-400 text-xs"></i>
                            <input v-model="search" type="text" placeholder="Cari Pegawai (NIP / Nama)..."
                                class="input input-sm pl-8 w-full bg-white border-slate-300 text-xs font-medium rounded-xl shadow-xs focus:border-blue-500" />
                        </div>

                        <!-- Ringkasan Status Input -->
                        <div class="flex items-center gap-2 flex-wrap text-xs">
                            <span class="px-3 py-1.5 rounded-xl font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                {{ countSiapSimpan }} Siap Disimpan
                            </span>
                            <span class="px-3 py-1.5 rounded-xl font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                {{ countBelumLengkap }} Belum Lengkap
                            </span>
                            <span class="px-3 py-1.5 rounded-xl font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                {{ countBelumDiisi }} Belum Diisi (0)
                            </span>
                        </div>
                    </div>

                    <!-- Table Input -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-md mb-4">
                        <div class="overflow-x-auto">
                            <table class="table table-sm w-full">
                                <thead class="bg-slate-100/90 text-slate-700 border-b border-slate-200">
                                    <tr>
                                        <th class="py-3 px-4 text-center w-12 font-bold">No</th>
                                        <th class="py-3 px-4 font-bold">Pegawai & Divisi</th>
                                        <th v-for="k in kriteria" :key="k.kode" class="text-center py-3 px-2 font-bold">
                                            <div class="text-xs">{{ k.nama_kriteria }}</div>
                                            <div class="text-[10px] font-normal uppercase tracking-wider text-slate-400 mt-0.5">
                                                ({{ k.kelompok }})
                                            </div>
                                        </th>
                                        <th class="text-center py-3 px-3 font-bold w-36">Status Akumulasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, index) in pagedPenilaian" :key="row.nip"
                                        class="border-b border-slate-100 last:border-0 hover:bg-slate-50/70 transition-colors"
                                        :class="totalAkumulasi(row.nilai) === TOTAL_HARI ? 'bg-emerald-50/30' : totalAkumulasi(row.nilai) > TOTAL_HARI ? 'bg-rose-50/50' : ''">
                                        
                                        <td class="text-center font-semibold text-xs text-slate-500 py-3">
                                            {{ showingStart + index }}
                                        </td>
                                        
                                        <td class="py-3 px-4">
                                            <div class="font-bold text-sm text-slate-800">{{ row.nama }}</div>
                                            <div class="text-xs text-slate-500 flex items-center gap-2 mt-0.5">
                                                <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-[11px] font-semibold text-slate-600">{{ row.nip }}</span>
                                                <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100 text-[10px] font-bold uppercase tracking-wider">{{ row.divisi }}</span>
                                            </div>
                                        </td>

                                        <td v-for="k in kriteria" :key="k.kode" class="text-center py-3 px-2">
                                            <input 
                                                v-model.number="form.penilaian[getIndexInForm(row.nip)].nilai[k.kode]"
                                                type="number"
                                                min="0"
                                                :max="TOTAL_HARI"
                                                class="w-20 h-9 px-2 text-center text-sm font-bold rounded-xl border bg-white shadow-xs transition-all duration-150 focus:outline-none focus:ring-2"
                                                :class="isInputError(row, k) 
                                                    ? 'border-rose-400 bg-rose-50 text-rose-700 focus:ring-rose-200 focus:border-rose-500' 
                                                    : k.kelompok === 'Akumulasi' 
                                                    ? 'border-slate-300 text-slate-800 hover:border-blue-400 focus:border-blue-600 focus:ring-blue-100' 
                                                    : 'border-slate-300 text-slate-800 hover:border-purple-400 focus:border-purple-600 focus:ring-purple-100'"
                                            />
                                        </td>

                                        <td class="text-center py-3 px-3">
                                            <span v-if="totalAkumulasi(row.nilai) === TOTAL_HARI" 
                                                class="inline-flex items-center gap-1 text-[11px] px-2.5 py-1 rounded-full font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                <i class="fa-solid fa-circle-check text-emerald-600"></i> Siap Simpan
                                            </span>
                                            <span v-else-if="totalAkumulasi(row.nilai) > TOTAL_HARI" 
                                                class="inline-flex items-center gap-1 text-[11px] px-2.5 py-1 rounded-full font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                                <i class="fa-solid fa-triangle-exclamation text-rose-600"></i> Over ({{ totalAkumulasi(row.nilai) }}/{{ TOTAL_HARI }})
                                            </span>
                                            <span v-else-if="totalAkumulasi(row.nilai) > 0" 
                                                class="inline-flex items-center gap-1 text-[11px] px-2.5 py-1 rounded-full font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                                <i class="fa-solid fa-clock text-amber-600"></i> {{ totalAkumulasi(row.nilai) }}/{{ TOTAL_HARI }} Hari
                                            </span>
                                            <span v-else 
                                                class="inline-flex items-center gap-1 text-[11px] px-2.5 py-1 rounded-full font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                                                Belum Diisi
                                            </span>
                                        </td>
                                    </tr>

                                    <tr v-if="pagedPenilaian.length === 0">
                                        <td :colspan="3 + kriteria.length" class="text-center py-12 text-slate-400">
                                            <i class="fa-solid fa-folder-open text-4xl mb-3 text-slate-300"></i>
                                            <p class="text-sm font-medium">Tidak ada pegawai yang perlu diinput pada divisi ini.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="displayedPenilaian.length > 0" class="flex flex-col sm:flex-row items-center justify-between px-1 py-2 mb-6 gap-3">
                        <div class="text-xs text-slate-500 font-medium">
                            Menampilkan <span class="font-bold text-slate-700">{{ showingStart }}–{{ showingEnd }}</span> dari <span class="font-bold text-slate-700">{{ displayedPenilaian.length }}</span> pegawai belum diinput
                        </div>
                        <div v-if="totalPages > 1" class="flex gap-1">
                            <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1"
                                class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 disabled:opacity-40">
                                ‹ Prev
                            </button>
                            <button v-for="page in totalPages" :key="page" @click="changePage(page)"
                                class="px-3 py-1.5 border rounded-lg text-xs font-bold transition"
                                :class="currentPage === page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'">
                                {{ page }}
                            </button>
                            <button @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages"
                                class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 disabled:opacity-40">
                                Next ›
                            </button>
                        </div>
                    </div>

                    <!-- Bottom Action Bar -->
                    <div class="flex flex-col sm:flex-row items-center justify-between bg-slate-50 p-5 rounded-2xl border border-slate-200 gap-4 shadow-sm">
                        <div class="text-xs text-slate-600 font-medium">
                            <span v-if="countSiapSimpan > 0" class="text-emerald-700 font-bold">
                                <i class="fa-solid fa-circle-check mr-1 text-emerald-500"></i>
                                {{ countSiapSimpan }} pegawai siap disimpan ke database.
                            </span>
                            <span v-else class="text-slate-500 italic">
                                Lengkapi nilai total absensi ({{ TOTAL_HARI }} hari) untuk mengaktifkan tombol simpan.
                            </span>
                        </div>

                        <div class="flex gap-3 w-full sm:w-auto">
                            <a href="/penilaian" class="btn bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl px-6 flex-1 sm:flex-none font-semibold text-sm">
                                Batal
                            </a>
                            <button 
                                @click="submit"
                                class="btn bg-blue-600 hover:bg-blue-700 text-white border-none rounded-xl px-8 transition-colors flex-1 sm:flex-none font-bold text-sm"
                                :disabled="form.processing || countSiapSimpan === 0 || adaOverAkumulasi || adaOverIndependen"
                            >
                                <span v-if="form.processing" class="loading loading-spinner loading-xs mr-2"></span>
                                <i v-else class="fa-solid fa-floppy-disk mr-2"></i>
                                Simpan Data ({{ countSiapSimpan }})
                            </button>
                        </div>
                    </div>

                </div>

                <!-- IF NO PERIODE SELECTED -->
                <div v-else-if="!selectedPeriodeId" class="text-center py-16 bg-slate-50 rounded-3xl border border-slate-200 border-dashed">
                    <i class="fa-regular fa-calendar-days text-5xl text-slate-300 mb-3"></i>
                    <h3 class="text-base font-bold text-slate-700">Silakan Pilih Periode Penilaian</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Pilih periode penilaian pada langkah 1 di atas untuk memulai penginputan data pegawai per divisi.</p>
                </div>

            </div> <!-- END TAB MANUAL -->

        </div>
    </MainLayout>
</template>