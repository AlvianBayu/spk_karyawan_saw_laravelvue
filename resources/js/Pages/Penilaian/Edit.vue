<script setup>
import { useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    karyawan: Object,
    kriteria: Array,
    periodeList: Array,
    periodeId: [String, Number],
    nilaiData: Object,
})

const TOTAL_HARI = 30

const form = useForm({
    periode_id: props.periodeId,
    nilai: Object.fromEntries(
        props.kriteria.map(k => [
            k.kode,
            props.nilaiData[k.kode]?.nilai ?? 0
        ])
    )
})

function totalHari() {
    return Object.values(form.nilai).reduce((a, b) => a + Number(b), 0)
}

function submit() {
    if (totalHari() > TOTAL_HARI) return
    form.put(`/penilaian/${props.karyawan.nip}`)
}
</script>

<template>
    <Head title="SIKAP" />

    <MainLayout>
        <div class="p-6 bg-white rounded-lg shadow-md max-w-2xl">

            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-xl font-semibold text-gray-900">Edit Penilaian</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Mengubah data absensi untuk periode
                    <strong class="text-gray-700">
                        {{ periodeList.find(p => p.id == periodeId)?.bulan }}
                        {{ periodeList.find(p => p.id == periodeId)?.tahun }}
                    </strong>
                </p>
            </div>

            <!-- Info Karyawan -->
            <div class="bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 mb-6 flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-xs font-semibold text-blue-700 flex-shrink-0">
                    {{ (karyawan.nama || karyawan.nama_karyawan || '?').slice(0,2).toUpperCase() }}
                </div>
                <div>
                    <div class="font-medium text-sm text-gray-900">
                        {{ karyawan.nama || karyawan.nama_karyawan }}
                    </div>
                    <div class="text-xs text-gray-400 font-mono mt-0.5">
                        {{ karyawan.jabatan }} · #{{ karyawan.nip }}
                    </div>
                </div>
            </div>

            <!-- Form Input Nilai -->
            <div class="border border-gray-200 rounded-xl overflow-hidden mb-5">
                <table class="table table-sm w-full">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="text-gray-700 font-semibold py-3 px-4 text-xs uppercase tracking-wide">
                                Kriteria
                            </th>
                            <th class="text-center text-gray-600 font-semibold py-3 text-xs uppercase tracking-wide">
                                Jenis
                            </th>
                            <th class="text-center text-gray-600 font-semibold py-3 text-xs uppercase tracking-wide">
                                Nilai (hari)
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="k in kriteria" :key="k.kode"
                            class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-sm text-gray-800">
                                {{ k.nama_kriteria }}
                            </td>
                            <td class="text-center py-3">
                                <span
                                    class="text-xs px-2.5 py-0.5 rounded-full font-medium"
                                    :class="k.jenis === 'Benefit'
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-red-100 text-red-600'">
                                    {{ k.jenis.toLowerCase() }}
                                </span>
                            </td>
                            <td class="text-center py-3">
                                <input
                                    v-model.number="form.nilai[k.kode]"
                                    type="number" min="0" max="30"
                                    class="input input-bordered input-sm w-24 text-center bg-white text-gray-800"
                                    :class="totalHari() > TOTAL_HARI
                                        ? 'border-red-400 bg-red-50 text-red-700'
                                        : 'border-gray-300 focus:border-blue-500'"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Total hari -->
            <div class="flex items-center justify-between mb-5 px-1">
                <span class="text-sm text-gray-500">Total hari diinput</span>
                <span
                    class="text-sm font-semibold"
                    :class="totalHari() > TOTAL_HARI
                        ? 'text-red-600'
                        : totalHari() === TOTAL_HARI
                        ? 'text-green-600'
                        : 'text-gray-700'"
                >
                    {{ totalHari() }} / 30 hari
                </span>
            </div>

            <!-- Alert over -->
            <div v-if="totalHari() > TOTAL_HARI"
                class="flex items-center gap-2 mb-5 px-4 py-2.5 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                <svg width="14" height="14" viewBox="0 0 12 12" fill="none">
                    <circle cx="6" cy="6" r="5.5" stroke="#EF4444" stroke-width="1.5"/>
                    <path d="M6 3.5v3M6 8h.01" stroke="#EF4444" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                Total hari melebihi 30. Periksa kembali nilai yang diinput.
            </div>

            <!-- Footer -->
            <div class="flex gap-3 justify-end">
                <a :href="`/penilaian?periode_id=${periodeId}`"
                    class="btn btn-ghost bg-gray-100 text-gray-700 hover:bg-gray-200 border-none px-6">
                    Batal
                </a>
                <button
                    @click="submit"
                    :disabled="form.processing || totalHari() > TOTAL_HARI"
                    class="btn bg-blue-600 hover:bg-blue-700 text-white border-none px-6"
                    :class="(form.processing || totalHari() > TOTAL_HARI) ? 'opacity-40 cursor-not-allowed' : ''"
                >
                    <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
                    Simpan Perubahan
                </button>
            </div>

        </div>
    </MainLayout>
</template>