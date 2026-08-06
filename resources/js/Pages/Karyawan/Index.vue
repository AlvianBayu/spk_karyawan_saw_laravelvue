<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    karyawan: Array,
    errors: Object
});

const form = useForm({
    id: null, // Tetap gunakan variabel ini untuk menyimpan NIP asli sebagai parameter URL
    nip: '',
    nama: '',
    status: 'Aktif' 
});

const isModalOpen = ref(false);
const isEditMode = ref(false);
const searchQuery = ref('');

const filteredKaryawan = computed(() => {
    if (!searchQuery.value) {
        return props.karyawan;
    }
    const lowerCaseQuery = searchQuery.value.toLowerCase();
    return props.karyawan.filter(k => {
        const nipStr = String(k.nip || '').toLowerCase();
        const namaStr = String(k.nama || '').toLowerCase();
        const statusStr = String(k.status || '').toLowerCase(); 
        return nipStr.includes(lowerCaseQuery) ||
               namaStr.includes(lowerCaseQuery) ||
               statusStr.includes(lowerCaseQuery);
    });
});

// --- PAGINATION ---
const itemsPerPage = ref(20);
const currentPage = ref(1);

watch(searchQuery, () => {
    currentPage.value = 1;
});

const totalPages = computed(() => {
    return Math.ceil(filteredKaryawan.value.length / itemsPerPage.value);
});

const paginatedKaryawan = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredKaryawan.value.slice(start, end);
});

const showingStart = computed(() => {
    return filteredKaryawan.value.length === 0 ? 0 : ((currentPage.value - 1) * itemsPerPage.value) + 1;
});

const showingEnd = computed(() => {
    return Math.min(currentPage.value * itemsPerPage.value, filteredKaryawan.value.length);
});

const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};
// ------------------

const openModalAdd = () => {
    isEditMode.value = false;
    form.reset();
    form.clearErrors();
    form.status = 'Aktif'; 
    isModalOpen.value = true;
};

const openModalEdit = (karyawanData) => {
    isEditMode.value = true;
    form.clearErrors();
    form.id = karyawanData.id; // Pastikan ini mengambil ID dari database
    form.nip = karyawanData.nip;
    form.nama = karyawanData.nama;
    form.status = karyawanData.status || 'Aktif'; 
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const saveData = () => {
    if (isEditMode.value) {
        form.put(`/karyawan/${form.id}`, {
            onSuccess: () => {
                closeModal();
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Data berhasil diperbarui.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            },
        });
    } else {
        form.post('/karyawan', {
            onSuccess: () => {
                closeModal();
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Data baru berhasil ditambahkan.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            },
        });
    }
};

const deleteData = (idIdentifier) => {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data yang dihapus akan disembunyikan dari daftar utama!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/karyawan/${idIdentifier}`, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Terhapus!',
                        text: 'Data berhasil dihapus.',
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
        <div class="p-6 bg-white rounded-lg shadow-md border border-gray-100">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <h2 class="text-2xl font-bold text-gray-900">Data Pegawai</h2>
                <button @click="openModalAdd" class="btn btn-primary font-semibold">
                    <i class="fa-solid fa-plus mr-2"></i> Tambah Pegawai
                </button>
            </div>

            <div class="mb-4">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari pegawai..."
                    class="input input-bordered input-sm pl-4 w-full sm:w-60 bg-white border-gray-300 text-gray-800 shadow-sm focus:border-blue-500"
                />
            </div>

            <div class="overflow-x-auto bg-white border border-gray-200 rounded-t-lg">
                <table class="table w-full text-gray-900">
                    <thead class="bg-slate-100 text-gray-800 font-bold border-b border-gray-200">
                        <tr>
                            <th class="w-16 text-center">No</th>
                            <th class="text-left">NIP</th>
                            <th class="text-left">Nama Lengkap</th>
                            <th class="text-center">Status</th>
                            <th class="text-center w-40">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="paginatedKaryawan.length === 0">
                            <td colspan="5" class="text-center py-8 text-gray-500">
                                <span v-if="searchQuery">Tidak ada pegawai yang cocok dengan pencarian "{{ searchQuery }}"</span>
                                <span v-else>Belum ada data Pegawai.</span>
                            </td>
                        </tr>

                        <tr
                            v-for="(item, index) in paginatedKaryawan"
                            :key="item.nip" 
                            class="hover:bg-gray-50 transition-colors duration-200"
                        >
                            <td class="text-center text-gray-700">{{ showingStart + index }}</td>
                            <td class="font-semibold text-gray-900">{{ item.nip }}</td>
                            <td class="text-gray-800">{{ item.nama }}</td>
                            
                            <td class="text-center">
                                <span 
                                    class="badge badge-sm text-white font-medium px-3 py-3"
                                    :class="(item.status || '').toLowerCase() === 'aktif' ? 'badge-success' : 'badge-error'"
                                >
                                    {{ (item.status || '').toLowerCase() === 'aktif' ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                            </td>

                            <td>
                                <div class="flex justify-center items-center gap-2">
                                    <a :href="`/karyawan/${item.nip}`"
                                       class="btn btn-sm bg-blue-600 hover:bg-blue-700 text-white border-none"
                                       title="Detail">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 12 12" stroke="currentColor" stroke-width="1.5">
                                            <circle cx="6" cy="6" r="5"/>
                                            <path d="M6 5v4M6 3.5h.01" stroke-linecap="round"/>
                                        </svg>
                                    </a>

                                    <button @click="openModalEdit(item)" class="btn btn-sm btn-warning text-gray-900 border-none" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <button @click="deleteData(item.id)" class="btn btn-sm btn-error text-white border-none" title="Hapus">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="filteredKaryawan.length > 0"
                class="flex flex-col sm:flex-row justify-between items-center px-4 py-4 bg-white border border-t-0 border-gray-200 rounded-b-lg"
            >
                <div class="text-sm text-gray-500 mb-4 sm:mb-0">
                    Showing <span class="font-semibold text-gray-900">{{ showingStart }}</span> to
                    <span class="font-semibold text-gray-900">{{ showingEnd }}</span> of
                    <span class="font-semibold text-gray-900">{{ filteredKaryawan.length }}</span> results
                </div>

                <div class="flex gap-1">
                    <button
                        @click="changePage(currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                        Previous
                    </button>

                    <button
                        v-for="page in totalPages"
                        :key="page"
                        @click="changePage(page)"
                        :class="[
                            'px-3 py-1 border rounded-md text-sm font-medium transition',
                            currentPage === page
                                ? 'bg-blue-600 text-white border-blue-600 hover:bg-blue-700'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                        ]"
                    >
                        {{ page }}
                    </button>

                    <button
                        @click="changePage(currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <dialog class="modal" :class="{ 'modal-open': isModalOpen }">
            <div class="modal-box bg-white text-gray-900">
                <h3 class="font-bold text-lg mb-4">
                    {{ isEditMode ? 'Edit Data Pegawai' : 'Tambah Pegawai Baru' }}
                </h3>

                <div class="form-control mb-3">
                    <label class="label">
                        <span class="label-text font-semibold text-gray-700">NIP</span>
                    </label>
                    <input
                        v-model="form.nip"
                        type="text"
                        placeholder="Masukkan NIP"
                        class="input input-bordered bg-white text-gray-900"
                        :class="{ 'input-error': form.errors.nip }"
                    />
                    <span v-if="form.errors.nip" class="text-error text-sm mt-1">
                        {{ form.errors.nip }}
                    </span>
                </div>

                <div class="form-control mb-3">
                    <label class="label">
                        <span class="label-text font-semibold text-gray-700">Nama Lengkap</span>
                    </label>
                    <input
                        v-model="form.nama"
                        type="text"
                        placeholder="Masukkan nama lengkap"
                        class="input input-bordered bg-white text-gray-900"
                        :class="{ 'input-error': form.errors.nama }"
                    />
                    <span v-if="form.errors.nama" class="text-error text-sm mt-1">
                        {{ form.errors.nama }}
                    </span>
                </div>

                <div class="form-control mb-5">
                    <label class="label">
                        <span class="label-text font-semibold text-gray-700">Status Pegawai</span>
                    </label>
                    <select 
                        v-model="form.status" 
                        class="select select-bordered w-full bg-white text-gray-900"
                        :class="{ 'select-error': form.errors.status }"
                    >
                        <option value="Aktif">Aktif</option>
                        <option value="Non-Aktif">Non-Aktif</option>
                    </select>
                    <span v-if="form.errors.status" class="text-error text-sm mt-1">
                        {{ form.errors.status }}
                    </span>
                </div>

                <div class="modal-action">
                    <button @click="closeModal" class="btn btn-ghost" :disabled="form.processing">
                        Batal
                    </button>
                    <button
                        @click="saveData"
                        class="btn btn-primary"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">
                            <span class="loading loading-spinner loading-sm mr-1"></span>
                            Menyimpan...
                        </span>
                        <span v-else>Simpan</span>
                    </button>
                </div>
            </div>

            <div class="modal-backdrop bg-black/50" @click="closeModal"></div>
        </dialog>
    </MainLayout>
</template>