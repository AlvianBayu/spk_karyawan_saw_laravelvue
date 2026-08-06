<script setup>
import { ref, computed } from 'vue'
import {Link} from '@inertiajs/vue3'
import PimpinanLayout from '@/Layouts/PimpinanLayout.vue';

const props = defineProps({
    karyawan: Array,
})

const search = ref('')

const filtered = computed(() =>
    props.karyawan.filter(k =>
        (k.nama || '').toLowerCase().includes(search.value.toLowerCase()) ||
        k.nip.includes(search.value)
    )
)
</script>

<template>
    <Head title="SIKAP" />
    <PimpinanLayout>
        <div class="bg-white rounded-lg shadow-md p-6">

            <div class="flex items-center justify-between mb-5">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Data Karyawan</h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ karyawan.length }} karyawan terdaftar
                    </p>
                </div>
                <div class="relative">
                    <svg class="absolute left-2.5 top-2 w-4 h-4 text-gray-400"
                        viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="6.5" cy="6.5" r="4.5"/><path d="M10 10l3 3"/>
                    </svg>
                    <input v-model="search" type="text"
                        placeholder="Cari karyawan..."
                        class="input input-bordered input-sm pl-8 w-56 bg-white
                               border-gray-300 text-gray-800" />
                </div>
            </div>

            <div class="border border-gray-200 rounded-xl overflow-hidden">
                <table class="table table-sm w-full">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="text-gray-700 font-semibold text-xs uppercase
                                       tracking-wide py-3 px-4">No</th>
                            <th class="text-gray-700 font-semibold text-xs uppercase
                                       tracking-wide py-3">Karyawan</th>
                            <th class="text-center text-gray-700 font-semibold text-xs
                                       uppercase tracking-wide py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(k, i) in filtered" :key="k.nip"
                            class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-500">{{ i + 1 }}</td>
                            <td class="py-3">
                                <div class="font-medium text-sm text-gray-900">{{ k.nama }}</div>
                                <div class="text-xs text-gray-400 font-mono mt-0.5">
                                    {{ k.jabatan }} · #{{ k.nip }}
                                </div>
                            </td>
                            <td class="text-center py-3">
                                <Link :href="`/pimpinan/karyawan/${k.nip}`"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5
                                           rounded-lg text-xs font-medium bg-blue-50
                                           text-blue-600 hover:bg-blue-100 border
                                           border-blue-200 transition-colors">
                                    <svg width="11" height="11" viewBox="0 0 12 12" fill="none"
                                        stroke="currentColor" stroke-width="1.5">
                                        <circle cx="6" cy="6" r="5"/>
                                        <path d="M6 5v4M6 3.5h.01" stroke-linecap="round"/>
                                    </svg>
                                    Detail
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="filtered.length === 0">
                            <td colspan="3" class="text-center py-10 text-gray-400 text-sm">
                                Tidak ada karyawan yang cocok.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </PimpinanLayout>
</template>