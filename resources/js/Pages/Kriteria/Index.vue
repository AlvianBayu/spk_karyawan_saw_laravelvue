<template>
  <Head title="" />
  <MainLayout>
    <div class="p-6 bg-white rounded-lg shadow-md">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h2 class="text-2xl font-bold text-gray-900">Data Kriteria</h2>
        <button @click="openAddModal" class="btn btn-primary font-semibold">
          <i class="fa-solid fa-plus mr-2"></i> Tambah Kriteria
        </button>
      </div>

      <div class="overflow-x-auto w-full border border-gray-200 rounded-lg shadow-sm">
        <table class="table w-full text-gray-900 bg-white">
          <thead class="bg-slate-100 text-gray-900 font-bold">
            <tr>
              <th class="w-10 text-center">No</th>
              <th class="text-center">Kode</th>
              <th>Nama Kriteria</th>
              <th>Jenis (Atribut)</th>
              <th class="text-center">Bobot (%)</th>
              <th class="text-center">Kelompok</th>
              <th class="text-center w-40">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in kriteria" :key="item.id" class="hover:bg-gray-100 transition-colors duration-200 border-b border-gray-200">
              <td class="text-center">{{ index + 1 }}</td>
              <td class="text-center font-bold text-blue-700">{{ item.kode }}</td>
              <td class="font-semibold">{{ item.nama_kriteria }}</td>
              <td>
                <span 
                  class="badge badge-sm font-semibold text-white px-3 py-2 border-none" 
                  :class="item.jenis.toLowerCase() === 'benefit' ? 'bg-green-500' : 'bg-red-500'"
                >
                  {{ item.jenis }}
                </span>
              </td>
              
              <td class="text-center font-bold">{{ Math.round(item.bobot) }}</td>
              <td class="text-center font-semibold">{{ item.kelompok }}</td>
              <td class="flex justify-center gap-2">
                  <button @click="openEditModal(item)" class="btn btn-sm btn-warning text-black" title="Edit">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                  </button>
                  
                  <button @click="deleteData(item.id, item.nama_kriteria)" class="btn btn-sm btn-error text-black" title="Hapus">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                  </button>
              </td>
            </tr>
            <tr v-if="kriteria.length === 0">
              <td colspan="6" class="text-center py-6 text-gray-500 italic">Belum ada data kriteria.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <dialog id="modal_form_kriteria" class="modal">
      <div class="modal-box bg-white text-gray-900">
        <h3 class="font-bold text-lg mb-4 text-gray-800 border-b pb-2">
          {{ isEdit ? 'Edit Kriteria' : 'Tambah Kriteria Baru' }}
        </h3>
        
        <form @submit.prevent="submitForm">
          <div class="form-control w-full mb-3">
            <label class="label"><span class="label-text text-gray-900 font-bold">Kode Kriteria</span></label>
            <input 
              type="text" 
              v-model="form.kode" 
              class="input input-bordered w-full bg-white text-gray-900" 
              placeholder="Contoh: C1"
              required 
            />
          </div>

          <div class="form-control w-full mb-3">
            <label class="label"><span class="label-text text-gray-900 font-bold">Nama Kriteria</span></label>
            <input 
              type="text" 
              v-model="form.nama_kriteria" 
              class="input input-bordered w-full bg-white text-gray-900" 
              placeholder="Contoh: Keterlambatan"
              required 
            />
          </div>

          <div class="form-control w-full mb-3">
            <label class="label"><span class="label-text text-gray-900 font-bold">Jenis (Atribut)</span></label>
            <select v-model="form.jenis" class="select select-bordered w-full bg-white text-gray-900" required>
              <option disabled value="">-- Pilih Jenis Atribut --</option>
              <option value="Benefit">Benefit (Semakin besar / sering, semakin baik)</option>
              <option value="Cost">Cost (Semakin kecil / jarang, semakin baik)</option>
            </select>
          </div>

          <div class="form-control w-full mb-6">
            <label class="label"><span class="label-text text-gray-900 font-bold">Bobot (%)</span></label>
            <input 
              type="number" 
              v-model="form.bobot" 
              class="input input-bordered w-full bg-white text-gray-900" 
              placeholder="Contoh: 20"
              min="0" 
              max="100" 
              step="1"  
              required 
            />
          </div>
          <div class="form-control w-full mb-3">
              <label class="label">
                  <span class="label-text font-bold text-gray-700">Kelompok</span>
                  <span class="label-text-alt text-gray-400">
                      Akumulasi = masuk total hari kerja
                  </span>
              </label>
              <select v-model="form.kelompok"
                  class="select select-bordered w-full bg-white text-gray-900">
                  <option value="">-- Pilih Kelompok --</option>
                  <option value="akumulasi">
                      Akumulasi (total harus = hari kerja)
                  </option>
                  <option value="independen">
                      Independen (terpisah, tidak masuk total)
                  </option>
              </select>
              <p class="text-xs text-gray-400 mt-1">
                  Contoh akumulasi: Kehadiran, Alpa, Izin, Sakit.
                  Contoh independen: Keterlambatan.
              </p>
          </div>

          <div class="modal-action">
            <button type="button" class="btn btn-ghost text-gray-700 font-semibold" @click="closeModal">Batal</button>
            <button type="submit" class="btn btn-primary px-10 font-bold" :disabled="form.processing">
              {{ isEdit ? 'Update Data' : 'Simpan Data' }}
            </button>
          </div>
        </form>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click="closeModal">close</button>
      </form>
    </dialog>
  </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Swal from 'sweetalert2'; // ✅ Import SweetAlert2

const props = defineProps({
  kriteria: Array
});

const isEdit = ref(false);

const form = useForm({
  id: '',
  kode: '',
  nama_kriteria: '',
  jenis: '',
  bobot: '',
  kelompok: ''
});

const openAddModal = () => {
  isEdit.value = false;
  form.reset();
  form.clearErrors();
  document.getElementById('modal_form_kriteria').showModal();
};

const openEditModal = (item) => {
  isEdit.value = true;
  form.id = item.id;
  form.kode = item.kode;
  form.nama_kriteria = item.nama_kriteria;
  form.jenis = item.jenis;
  form.bobot = Math.round(item.bobot); 
  form.kelompok = item.kelompok;
  form.clearErrors();
  document.getElementById('modal_form_kriteria').showModal();
};

const closeModal = () => {
  document.getElementById('modal_form_kriteria').close();
  form.reset();
};

const submitForm = () => {
  if (isEdit.value) {
    form.put(`/kriteria/${form.id}`, {
      onSuccess: () => {
        closeModal();
        Swal.fire({
          title: 'Berhasil!',
          text: 'Data Kriteria berhasil diperbarui.',
          icon: 'success',
          timer: 2000,
          showConfirmButton: false
        });
      }
    });
  } else {
    form.post('/kriteria', {
      onSuccess: () => {
        closeModal();
        // Alert Sukses Tambah Data Baru
        Swal.fire({
          title: 'Berhasil!',
          text: 'Kriteria baru berhasil ditambahkan.',
          icon: 'success',
          timer: 2000,
          showConfirmButton: false
        });
      }
    });
  }
};

const deleteData = (id, nama) => {
  // Alert Konfirmasi Hapus Data
  Swal.fire({
    title: 'Apakah Anda yakin?',
    text: `Data Kriteria "${nama}" akan dihapus permanen!`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Ya, hapus!',
    cancelButtonText: 'Batal'
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(`/kriteria/${id}`, {
        onSuccess: () => {
          // Alert Sukses Hapus Data
          Swal.fire({
            title: 'Terhapus!',
            text: 'Data Kriteria berhasil dihapus.',
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