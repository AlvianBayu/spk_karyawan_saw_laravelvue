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
    <Head title="" />
    <MainLayout>
        <div class="p-6 bg-white rounded-lg shadow-md">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start
                        sm:items-center mb-6 gap-4">
                <h2 class="text-2xl font-bold text-gray-900">Data Periode</h2>
                <button @click="openModalAdd" class="btn btn-primary font-semibold">
                    <i class="fa-solid fa-plus mr-2"></i> Tambah Periode
                </button>
            </div>

            <!-- Flash success -->
            <div v-if="$page.props.flash?.success"
                class="alert alert-success shadow-sm mb-6 text-white">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ $page.props.flash.success }}</span>
            </div>

            <!-- Search -->
            <div class="mb-4">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari bulan atau tahun..."
                    class="input input-bordered input-sm pl-8 w-60 bg-white
                           border-gray-300 text-gray-800 shadow-sm focus:border-blue-500"
                />
            </div>

            <!-- Tabel -->
            <div class="overflow-x-auto bg-white rounded-t-lg shadow">
                <table class="table w-full text-gray-900">
                    <thead class="bg-slate-100 text-gray-900 font-bold">
                        <tr>
                            <th class="w-16">No</th>
                            <th>Bulan</th>
                            <th>Tahun</th>
                            <th class="text-center">Hari Kerja</th>
                            <th class="text-center w-40">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="paginatedPeriodes.length === 0">
                            <td colspan="5" class="text-center py-6 text-gray-500">
                                <span v-if="searchQuery">
                                    Tidak ada periode yang cocok dengan "{{ searchQuery }}"
                                </span>
                                <span v-else>Belum ada data periode.</span>
                            </td>
                        </tr>

                        <tr
                            v-for="(item, index) in paginatedPeriodes"
                            :key="item.id"
                            class="hover:bg-gray-100 transition-colors duration-200
                                   border-b border-gray-100"
                        >
                            <td>{{ showingStart + index }}</td>
                            <td class="font-semibold">{{ item.bulan }}</td>
                            <td>{{ item.tahun }}</td>
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 text-sm
                                             font-medium text-blue-700 bg-blue-50
                                             px-2.5 py-0.5 rounded-full border border-blue-200">
                                    {{ item.hari_kerja ?? 20 }} hari
                                </span>
                            </td>
                            <td class="flex justify-center gap-2">
                                <button
                                    @click="openModalEdit(item)"
                                    class="btn btn-sm btn-warning text-black"
                                    title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button
                                    @click="deleteData(item.id)"
                                    class="btn btn-sm btn-error text-white"
                                    title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="filteredPeriodes.length > 0"
                class="flex flex-col sm:flex-row justify-between items-center
                       px-4 py-4 bg-white border-t border-gray-200 shadow rounded-b-lg">
                <div class="text-sm text-gray-500 mb-4 sm:mb-0">
                    Showing
                    <span class="font-semibold text-gray-900">{{ showingStart }}</span> to
                    <span class="font-semibold text-gray-900">{{ showingEnd }}</span> of
                    <span class="font-semibold text-gray-900">{{ filteredPeriodes.length }}</span>
                    results
                </div>
                <div class="flex gap-1">
                    <button
                        @click="changePage(currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="px-3 py-1 border border-gray-300 rounded-md text-sm
                               font-medium text-gray-700 bg-white hover:bg-gray-50
                               disabled:opacity-50 disabled:cursor-not-allowed transition">
                        Previous
                    </button>
                    <button
                        v-for="page in totalPages" :key="page"
                        @click="changePage(page)"
                        :class="[
                            'px-3 py-1 border rounded-md text-sm font-medium transition',
                            currentPage === page
                                ? 'bg-indigo-700 text-white border-indigo-700 hover:bg-indigo-800'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                        ]">
                        {{ page }}
                    </button>
                    <button
                        @click="changePage(currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        class="px-3 py-1 border border-gray-300 rounded-md text-sm
                               font-medium text-gray-700 bg-white hover:bg-gray-50
                               disabled:opacity-50 disabled:cursor-not-allowed transition">
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Tambah/Edit -->
        <dialog class="modal" :class="{ 'modal-open': isModalOpen }">
            <div class="modal-box bg-white text-gray-900">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">
                    {{ isEditMode ? 'Edit' : 'Tambah' }} Periode Penilaian
                </h3>

                <form @submit.prevent="saveData">

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

                    <div class="modal-action">
                        <button type="button" @click="closeModal"
                            class="btn btn-ghost" :disabled="form.processing">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary"
                            :disabled="form.processing">
                            <span v-if="form.processing">
                                <span class="loading loading-spinner loading-sm mr-1"></span>
                                Menyimpan...
                            </span>
                            <span v-else>Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-backdrop bg-black/50" @click="closeModal"></div>
        </dialog>

    </MainLayout>
</template>