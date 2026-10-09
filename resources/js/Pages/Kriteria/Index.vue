<template>
  <Head title="Data Kriteria - SIKAP" />
  <MainLayout>
    <div class="p-6 sm:p-8 bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-gray-100/50">
      
      <!-- Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4 border-b border-gray-100 pb-6">
          <div>
              <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-indigo-600 tracking-tight flex items-center gap-3">
                  Data Kriteria
              </h1>
              <p class="text-sm text-gray-500 mt-2 font-medium">Kelola kriteria, bobot, dan jenis atribut evaluasi pegawai</p>
          </div>
          <button @click="openAddModal" class="btn h-11 bg-blue-600 hover:bg-blue-700 text-white border-none rounded-xl font-semibold shadow-sm px-6 transition-all">
              <i class="fa-solid fa-plus mr-1.5"></i> Tambah Kriteria
          </button>
      </div>

      <div class="border border-gray-200 rounded-2xl overflow-hidden shadow-sm bg-white mb-4">
        <div class="overflow-x-auto">
          <table class="table w-full">
            <thead class="bg-slate-50/80 border-b border-gray-100">
              <tr>
                <th class="text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest w-16 text-center">No</th>
                <th class="text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest text-center">Kode</th>
                <th class="text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest text-left">Nama Kriteria</th>
                <th class="text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest text-left">Jenis (Atribut)</th>
                <th class="text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest text-center">Bobot (%)</th>
                <th class="text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest text-center">Kelompok</th>
                <th class="text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest text-center w-32">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-if="kriteria.length === 0">
                <td colspan="7" class="text-center py-12 bg-slate-50/50">
                    <div class="flex flex-col items-center justify-center text-gray-400">
                        <i class="fa-regular fa-folder-open text-4xl mb-3 text-gray-300"></i>
                        <p class="font-medium">Belum ada data kriteria yang ditambahkan.</p>
                    </div>
                </td>
              </tr>
              <tr v-for="(item, index) in kriteria" :key="item.id" class="hover:bg-blue-50/30 transition-colors">
                <td class="px-5 py-4 text-center text-sm font-semibold text-gray-500">{{ index + 1 }}</td>
                <td class="px-5 py-4 text-center">
                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-lg text-xs font-bold text-blue-700 bg-blue-50 border border-blue-100">
                        {{ item.kode }}
                    </span>
                </td>
                <td class="px-5 py-4 font-bold text-sm text-gray-900">{{ item.nama_kriteria }}</td>
                <td class="px-5 py-4">
                  <span 
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold border" 
                    :class="item.jenis.toLowerCase() === 'benefit' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                  >
                    <i :class="item.jenis.toLowerCase() === 'benefit' ? 'fa-solid fa-arrow-trend-up' : 'fa-solid fa-arrow-trend-down'"></i>
                    {{ item.jenis }}
                  </span>
                </td>
                
                <td class="px-5 py-4 text-center">
                    <div class="inline-flex items-center justify-center min-w-[40px] h-8 rounded-lg bg-gray-50 border border-gray-200 font-bold text-gray-800 shadow-inner">
                        {{ Math.round(item.bobot) }}
                    </div>
                </td>
                <td class="px-5 py-4 text-center">
                    <span class="text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider" 
                          :class="item.kelompok === 'akumulasi' ? 'bg-indigo-50 text-indigo-600' : 'bg-purple-50 text-purple-600'">
                        {{ item.kelompok }}
                    </span>
                </td>
                <td class="px-5 py-4 text-center">
                    <div class="flex justify-center gap-2">
                        <button @click="openEditModal(item)" class="w-8 h-8 rounded-lg flex items-center justify-center bg-amber-50 text-amber-600 hover:bg-amber-100 hover:text-amber-700 transition-colors border border-amber-200" title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        
                        <button @click="deleteData(item.id, item.nama_kriteria)" class="w-8 h-8 rounded-lg flex items-center justify-center bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 transition-colors border border-rose-200" title="Hapus">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <dialog id="modal_form_kriteria" class="modal">
      <div class="modal-box bg-white text-gray-900 max-w-md p-0 overflow-hidden rounded-2xl">
        <div class="px-6 py-4 bg-slate-50/80 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-lg text-gray-800">
                {{ isEdit ? 'Edit' : 'Tambah' }} Kriteria
            </h3>
            <button @click="closeModal" class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-200 transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        
        <form @submit.prevent="submitForm" class="p-6">
          <div class="form-control w-full mb-3">
            <label class="label"><span class="label-text text-gray-700 font-bold">Kode Kriteria</span></label>
            <input 
              type="text" 
              v-model="form.kode" 
              class="input input-bordered w-full bg-white text-gray-900 focus:border-blue-500" 
              placeholder="Contoh: C1"
              required 
            />
          </div>

          <div class="form-control w-full mb-3">
            <label class="label"><span class="label-text text-gray-700 font-bold">Nama Kriteria</span></label>
            <input 
              type="text" 
              v-model="form.nama_kriteria" 
              class="input input-bordered w-full bg-white text-gray-900 focus:border-blue-500" 
              placeholder="Contoh: Keterlambatan"
              required 
            />
          </div>

          <div class="form-control w-full mb-3">
            <label class="label"><span class="label-text text-gray-700 font-bold">Jenis (Atribut)</span></label>
            <select v-model="form.jenis" class="select select-bordered w-full bg-white text-gray-900 focus:border-blue-500 font-medium" required>
              <option disabled value="">-- Pilih Jenis Atribut --</option>
              <option value="Benefit">Benefit (Semakin besar / sering, semakin baik)</option>
              <option value="Cost">Cost (Semakin kecil / jarang, semakin baik)</option>
            </select>
          </div>

          <div class="form-control w-full mb-6">
            <label class="label"><span class="label-text text-gray-700 font-bold">Bobot (%)</span></label>
            <div class="flex items-center gap-3">
                <input 
                  type="number" 
                  v-model="form.bobot" 
                  class="input input-bordered w-32 bg-white text-gray-900 focus:border-blue-500 font-bold text-center" 
                  placeholder="20"
                  min="0" 
                  max="100" 
                  step="1"  
                  required 
                />
                <span class="text-sm text-gray-500 font-semibold">%</span>
            </div>
          </div>
          <div class="form-control w-full mb-3">
              <label class="label">
                  <span class="label-text font-bold text-gray-700">Kelompok</span>
              </label>
              <select v-model="form.kelompok"
                  class="select select-bordered w-full bg-white text-gray-900 focus:border-blue-500 font-medium">
                  <option value="">-- Pilih Kelompok --</option>
                  <option value="akumulasi">
                      Akumulasi (masuk ke perhitungan Hari Kerja)
                  </option>
                  <option value="independen">
                      Independen (terpisah dari total Hari Kerja)
                  </option>
              </select>
              <p class="text-[11px] text-gray-400 mt-2 bg-slate-50 p-3 rounded-lg border border-gray-100">
                  <strong class="text-gray-600 block mb-1"><i class="fa-solid fa-circle-info mr-1 text-blue-500"></i> Info:</strong>
                  <b>Akumulasi:</b> Kehadiran, Alpa, Izin, Sakit.<br>
                  <b>Independen:</b> Keterlambatan.
              </p>
          </div>

          <div class="flex justify-end gap-2 mt-8 pt-4 border-t border-gray-100">
            <button type="button" class="btn bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl px-5 font-semibold" @click="closeModal">Batal</button>
            <button type="submit" class="btn bg-blue-600 hover:bg-blue-700 text-white border-none rounded-xl px-6 font-semibold" :disabled="form.processing">
              {{ isEdit ? 'Update Data' : 'Simpan Data' }}
            </button>
          </div>
        </form>
      </div>
      <form method="dialog" class="modal-backdrop bg-slate-900/40 backdrop-blur-sm">
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