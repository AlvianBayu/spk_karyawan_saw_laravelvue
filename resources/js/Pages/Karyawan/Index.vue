<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    karyawan: Array,
    divisiList: Array,
    errors: Object
});

const form = useForm({
    id: null,
    nip: '',
    nama: '',
    divisi: '',
    status: 'Aktif' 
});

const isModalOpen = ref(false);
const isEditMode = ref(false);
const searchQuery = ref('');
const selectedDivisi = ref('');

const filteredKaryawan = computed(() => {
    let result = props.karyawan;

    if (selectedDivisi.value) {
        result = result.filter(k => k.divisi === selectedDivisi.value);
    }

    if (searchQuery.value) {
        const lowerCaseQuery = searchQuery.value.toLowerCase();
        result = result.filter(k => {
            const nipStr = String(k.nip || '').toLowerCase();
            const namaStr = String(k.nama || '').toLowerCase();
            const divisiStr = String(k.divisi || '').toLowerCase();
            const statusStr = String(k.status || '').toLowerCase(); 
            return nipStr.includes(lowerCaseQuery) ||
                   namaStr.includes(lowerCaseQuery) ||
                   divisiStr.includes(lowerCaseQuery) ||
                   statusStr.includes(lowerCaseQuery);
        });
    }
    
    return result;
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
    form.divisi = karyawanData.divisi || '';
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
    <Head title="Pegawai - SIKAP" />
    <MainLayout>
        <div class="p-6 sm:p-8 bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-gray-100/50">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-indigo-600 tracking-tight">Data Pegawai</h2>
                    <p class="text-sm text-gray-500 mt-1">Kelola data pegawai, divisi, dan status aktif.</p>
                </div>
                <button @click="openModalAdd" class="btn bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-md shadow-blue-500/30 border-0 rounded-xl transition-all duration-300 transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-plus mr-2"></i> Tambah Pegawai
                </button>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 mb-6">
                <div class="relative flex-1 max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari NIP, nama, divisi..."
                        class="input h-9 pl-9 text-sm bg-gray-50 border-gray-200 text-gray-800 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all duration-300 w-full"
                    />
                </div>
                
                <select 
                    v-model="selectedDivisi" 
                    class="select h-9 min-h-0 py-0 pl-3 pr-8 text-sm bg-gray-50 border-gray-200 text-gray-800 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all duration-300 sm:w-48"
                >
                    <option value="">Semua Divisi</option>
                    <option v-for="div in divisiList" :key="div" :value="div">{{ div }}</option>
                </select>
            </div>

            <div class="overflow-x-auto bg-white border border-gray-100 rounded-2xl shadow-sm">
                <table class="table w-full text-gray-900">
                    <thead class="bg-gray-50/80 text-gray-600 font-semibold text-sm border-b border-gray-100">
                        <tr>
                            <th class="w-16 text-center py-4 rounded-tl-2xl">No</th>
                            <th class="text-left py-4">NIP</th>
                            <th class="text-left py-4">Nama Lengkap</th>
                            <th class="text-left py-4">Divisi</th>
                            <th class="text-center py-4">Status</th>
                            <th class="text-center w-40 py-4 rounded-tr-2xl">Aksi</th>
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
                            class="hover:bg-blue-50/40 transition-colors duration-200 border-b border-gray-50 last:border-0"
                        >
                            <td class="text-center text-gray-500 font-medium">{{ showingStart + index }}</td>
                            <td class="font-semibold text-gray-700 tracking-wide">{{ item.nip }}</td>
                            <td class="text-gray-900 font-medium">{{ item.nama }}</td>
                            <td class="text-gray-600">
                                <span v-if="item.divisi" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    {{ item.divisi }}
                                </span>
                                <span v-else class="text-gray-400 italic text-xs">-</span>
                            </td>
                            
                            <td class="text-center">
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                     :class="(item.status || '').toLowerCase() === 'aktif' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-rose-50 text-rose-600 border border-rose-200'">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="(item.status || '').toLowerCase() === 'aktif' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                    {{ (item.status || '').toLowerCase() === 'aktif' ? 'Aktif' : 'Non-Aktif' }}
                                </div>
                            </td>

                            <td>
                                <div class="flex justify-center items-center gap-2">
                                    <a :href="`/karyawan/${item.nip}`"
                                       class="btn btn-sm btn-circle bg-blue-100 hover:bg-blue-600 text-blue-600 hover:text-white border-0 transition-colors duration-300"
                                       title="Detail">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>

                                    <button @click="openModalEdit(item)" class="btn btn-sm btn-circle bg-amber-100 hover:bg-amber-500 text-amber-600 hover:text-white border-0 transition-colors duration-300" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <button @click="deleteData(item.id)" class="btn btn-sm btn-circle bg-rose-100 hover:bg-rose-500 text-rose-600 hover:text-white border-0 transition-colors duration-300" title="Hapus">
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
            <div class="modal-box bg-white/95 backdrop-blur-xl text-gray-900 rounded-3xl shadow-2xl border border-white/50 p-8 max-w-lg">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-bold text-2xl bg-clip-text text-transparent bg-gradient-to-r from-blue-700 to-indigo-600">
                        {{ isEditMode ? 'Edit Data Pegawai' : 'Tambah Pegawai Baru' }}
                    </h3>
                    <button @click="closeModal" class="btn btn-sm btn-circle btn-ghost">✕</button>
                </div>

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

                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text font-semibold text-gray-700">Nama Lengkap</span>
                    </label>
                    <input
                        v-model="form.nama"
                        type="text"
                        placeholder="Masukkan nama lengkap"
                        class="input w-full bg-gray-50 border-gray-200 text-gray-900 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all"
                        :class="{ 'border-red-400 focus:border-red-500 focus:ring-red-500/10': form.errors.nama }"
                    />
                    <span v-if="form.errors.nama" class="text-red-500 text-xs mt-1.5 ml-1">
                        {{ form.errors.nama }}
                    </span>
                </div>

                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text font-semibold text-gray-700">Divisi</span>
                    </label>
                    <input
                        v-model="form.divisi"
                        type="text"
                        placeholder="Contoh: IT, HRD, Keuangan..."
                        class="input w-full bg-gray-50 border-gray-200 text-gray-900 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all"
                        :class="{ 'border-red-400 focus:border-red-500 focus:ring-red-500/10': form.errors.divisi }"
                        list="divisi-options"
                    />
                    <datalist id="divisi-options">
                        <option v-for="div in divisiList" :key="div" :value="div"></option>
                    </datalist>
                    <span v-if="form.errors.divisi" class="text-red-500 text-xs mt-1.5 ml-1">
                        {{ form.errors.divisi }}
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

                <div class="modal-action mt-8 flex gap-3">
                    <button @click="closeModal" class="btn bg-gray-100 hover:bg-gray-200 text-gray-700 border-0 rounded-xl flex-1" :disabled="form.processing">
                        Batal
                    </button>
                    <button
                        @click="saveData"
                        class="btn bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white border-0 rounded-xl flex-1 shadow-md shadow-blue-500/30"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="flex items-center justify-center">
                            <span class="loading loading-spinner loading-sm mr-2"></span>
                            Menyimpan...
                        </span>
                        <span v-else>Simpan Data</span>
                    </button>
                </div>
            </div>

            <div class="modal-backdrop bg-black/50" @click="closeModal"></div>
        </dialog>
    </MainLayout>
</template>