<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    periodes: Array,
    bulanList: Array,
    hariKerjaDefault: Number,
    errors: Object,
    flash: Object
});

const isModalOpen = ref(false);
const isEditMode = ref(false);

// Tambahkan hari_kerja di form
const form = useForm({
    id: null,
    bulan: '',
    tahun: new Date().getFullYear(),
    hari_kerja: props.hariKerjaDefault ?? 20,
});

const searchQuery = ref('');

const filteredPeriodes = computed(() => {
    if (!searchQuery.value) return props.periodes;
    const q = searchQuery.value.toLowerCase();
    return props.periodes.filter(p =>
        String(p.bulan || '').toLowerCase().includes(q) ||
        String(p.tahun || '').toLowerCase().includes(q)
    );
});

// Pagination
const itemsPerPage = ref(5);
const currentPage = ref(1);

watch(searchQuery, () => { currentPage.value = 1; });

const totalPages = computed(() =>
    Math.ceil(filteredPeriodes.value.length / itemsPerPage.value)
);

const paginatedPeriodes = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredPeriodes.value.slice(start, start + itemsPerPage.value);
});

const showingStart = computed(() =>
    filteredPeriodes.value.length === 0
        ? 0
        : (currentPage.value - 1) * itemsPerPage.value + 1
);

const showingEnd = computed(() =>
    Math.min(currentPage.value * itemsPerPage.value, filteredPeriodes.value.length)
);

const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value) currentPage.value = page;
};

const openModalAdd = () => {
    isEditMode.value = false;
    form.reset();
    form.clearErrors();
    form.tahun      = new Date().getFullYear();
    form.hari_kerja = props.hariKerjaDefault ?? 20;
    isModalOpen.value = true;
};

const openModalEdit = (periodeData) => {
    isEditMode.value = true;
    form.clearErrors();
    form.id         = periodeData.id;
    form.bulan      = periodeData.bulan;
    form.tahun      = periodeData.tahun;
    form.hari_kerja = periodeData.hari_kerja ?? props.hariKerjaDefault ?? 20;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const saveData = () => {
    if (isEditMode.value) {
        form.put(`/periode/${form.id}`, {
            onSuccess: () => {
                closeModal();
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Data Periode berhasil diperbarui.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            },
        });
    } else {
        form.post('/periode', {
            onSuccess: () => {
                closeModal();
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Periode baru berhasil ditambahkan.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            },
        });
    }
};

const deleteData = (id) => {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: 'Data Periode ini akan dihapus permanen!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/periode/${id}`, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Terhapus!',
                        text: 'Data Periode berhasil dihapus.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Data Periode - SIKAP" />
    <MainLayout>
        <div class="p-6 sm:p-8 bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-gray-100/50">

            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4 border-b border-gray-100 pb-6">
                <div>
                    <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-indigo-600 tracking-tight flex items-center gap-3">
                        Data Periode
                    </h1>
                    <p class="text-sm text-gray-500 mt-2 font-medium">Kelola periode penilaian dan penentuan hari kerja bulanan</p>
                </div>
                <button @click="openModalAdd" class="btn h-11 bg-blue-600 hover:bg-blue-700 text-white border-none rounded-xl font-semibold shadow-sm px-6 transition-all">
                    <i class="fa-solid fa-plus mr-1.5"></i> Tambah Periode
                </button>
            </div>

            <!-- Flash success -->
            <div v-if="$page.props.flash?.success"
                class="flex items-center gap-3 px-4 py-3 mb-6 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 font-medium">
                <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
                <span>{{ $page.props.flash.success }}</span>
            </div>

            <!-- Search -->
            <div class="mb-6 flex">
                <div class="relative w-full sm:w-72">
                    <i class="fa-solid fa-search absolute left-3 top-2.5 w-4 h-4 text-gray-400"></i>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari bulan atau tahun..."
                        class="input h-9 pl-9 text-sm w-full bg-white border-gray-200 text-gray-800 rounded-xl shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 font-medium"
                    />
                </div>
            </div>

            <!-- Tabel -->
            <div class="border border-gray-200 rounded-2xl overflow-hidden shadow-sm bg-white mb-4">
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead class="bg-slate-50/80 border-b border-gray-100">
                            <tr>
                                <th class="text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest w-16 text-center">No</th>
                                <th class="text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest text-left">Bulan</th>
                                <th class="text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest text-left">Tahun</th>
                                <th class="text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest text-center">Hari Kerja</th>
                                <th class="text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest text-center w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="paginatedPeriodes.length === 0">
                                <td colspan="5" class="text-center py-12 bg-slate-50/50">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i class="fa-regular fa-folder-open text-4xl mb-3 text-gray-300"></i>
                                        <p class="font-medium">
                                            <span v-if="searchQuery">Tidak ada periode yang cocok dengan "{{ searchQuery }}"</span>
                                            <span v-else>Belum ada data periode yang ditambahkan.</span>
                                        </p>
                                    </div>
                                </td>
                            </tr>

                            <tr
                                v-for="(item, index) in paginatedPeriodes"
                                :key="item.id"
                                class="hover:bg-blue-50/30 transition-colors"
                            >
                                <td class="px-5 py-4 text-center text-sm font-semibold text-gray-500">{{ showingStart + index }}</td>
                                <td class="px-5 py-4 font-bold text-sm text-gray-900">{{ item.bulan }}</td>
                                <td class="px-5 py-4 font-semibold text-sm text-gray-600">{{ item.tahun }}</td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-lg text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-100/50">
                                        {{ item.hari_kerja ?? 20 }} <span class="ml-1 text-[10px] text-indigo-400 uppercase tracking-wider font-semibold">Hari</span>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button
                                            @click="openModalEdit(item)"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center bg-amber-50 text-amber-600 hover:bg-amber-100 hover:text-amber-700 transition-colors border border-amber-200"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button
                                            @click="deleteData(item.id)"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 transition-colors border border-rose-200"
                                            title="Hapus">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="filteredPeriodes.length > 0"
                class="flex flex-col sm:flex-row justify-between items-center px-2 py-2 gap-4">
                <div class="text-sm font-medium text-gray-500">
                    Menampilkan <span class="font-bold text-gray-900">{{ showingStart }}</span> hingga <span class="font-bold text-gray-900">{{ showingEnd }}</span> dari <span class="font-bold text-gray-900">{{ filteredPeriodes.length }}</span> data
                </div>
                <div class="flex gap-1.5 p-1 bg-gray-50 border border-gray-200 rounded-xl">
                    <button
                        @click="changePage(currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="w-9 h-9 rounded-lg flex items-center justify-center text-sm font-bold transition-all"
                        :class="currentPage === 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-white hover:shadow-sm'">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <button
                        v-for="page in totalPages" :key="page"
                        @click="changePage(page)"
                        class="w-9 h-9 rounded-lg flex items-center justify-center text-sm font-bold transition-all"
                        :class="currentPage === page
                            ? 'bg-blue-600 text-white shadow-sm'
                            : 'text-gray-600 hover:bg-white hover:shadow-sm'">
                        {{ page }}
                    </button>
                    <button
                        @click="changePage(currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        class="w-9 h-9 rounded-lg flex items-center justify-center text-sm font-bold transition-all"
                        :class="currentPage === totalPages ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-white hover:shadow-sm'">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Tambah/Edit -->
        <dialog class="modal" :class="{ 'modal-open': isModalOpen }">
            <div class="modal-box bg-white text-gray-900 max-w-md p-0 overflow-hidden rounded-2xl">
                <div class="px-6 py-4 bg-slate-50/80 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-lg text-gray-800">
                        {{ isEditMode ? 'Edit' : 'Tambah' }} Periode
                    </h3>
                    <button @click="closeModal" class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-200 transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="saveData" class="p-6">

                    <!-- Bulan -->
                    <div class="form-control w-full mb-3">
                        <label class="label">
                            <span class="label-text font-bold text-gray-700">Pilih Bulan</span>
                        </label>
                        <select v-model="form.bulan"
                            class="select select-bordered w-full bg-white text-gray-900"
                            :class="{ 'select-error': form.errors.bulan }">
                            <option value="" disabled>-- Pilih Bulan --</option>
                            <option v-for="bln in bulanList" :key="bln" :value="bln">
                                {{ bln }}
                            </option>
                        </select>
                        <span v-if="form.errors.bulan"
                            class="label-text-alt text-error font-semibold mt-1">
                            {{ form.errors.bulan }}
                        </span>
                    </div>

                    <!-- Tahun -->
                    <div class="form-control w-full mb-3">
                        <label class="label">
                            <span class="label-text font-bold text-gray-700">Tahun</span>
                        </label>
                        <input type="number" v-model="form.tahun" min="2000" max="2099"
                            class="input input-bordered w-full bg-white text-gray-900"
                            :class="{ 'input-error': form.errors.tahun }" />
                        <span v-if="form.errors.tahun"
                            class="label-text-alt text-error font-semibold mt-1">
                            {{ form.errors.tahun }}
                        </span>
                    </div>

                    <!-- Hari Kerja -->
                    <div class="form-control w-full mb-6">
                        <label class="label">
                            <span class="label-text font-bold text-gray-700">Hari Kerja</span>
                            <span class="label-text-alt text-gray-400">Jumlah hari kerja bulan ini</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <input
                                type="number"
                                v-model="form.hari_kerja"
                                min="1"
                                max="31"
                                class="input input-bordered w-32 bg-white text-gray-900 text-center font-semibold"
                                :class="{ 'input-error': form.errors.hari_kerja }"
                            />
                            <span class="text-sm text-gray-500">hari kerja</span>
                            <!-- Info badge default -->
                            <span class="text-xs text-blue-600 bg-blue-50 px-2 py-1
                                         rounded-lg border border-blue-200">
                                Default: {{ hariKerjaDefault }} hari
                            </span>
                        </div>
                        <span v-if="form.errors.hari_kerja"
                            class="label-text-alt text-error font-semibold mt-1">
                            {{ form.errors.hari_kerja }}
                        </span>
                        <p class="text-xs text-gray-400 mt-1.5">
                            Sesuaikan dengan kalender kerja bulan
                            {{ form.bulan || 'yang dipilih' }} {{ form.tahun }}
                            (tidak termasuk hari libur & weekend).
                        </p>
                    </div>

                    <div class="flex justify-end gap-2 mt-8 pt-4 border-t border-gray-100">
                        <button type="button" @click="closeModal"
                            class="btn bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl px-5 font-semibold" :disabled="form.processing">
                            Batal
                        </button>
                        <button type="submit" class="btn bg-blue-600 hover:bg-blue-700 text-white border-none rounded-xl px-6 font-semibold"
                            :disabled="form.processing">
                            <span v-if="form.processing">
                                <span class="loading loading-spinner loading-sm mr-1.5"></span>
                                Menyimpan...
                            </span>
                            <span v-else>Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-backdrop bg-slate-900/40 backdrop-blur-sm" @click="closeModal"></div>
        </dialog>

    </MainLayout>
</template>