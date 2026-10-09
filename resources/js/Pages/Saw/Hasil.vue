<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { router, Head } from "@inertiajs/vue3";
import MainLayout from "@/Layouts/MainLayout.vue";
import Chart from "chart.js/auto";

const props = defineProps({
    hasil: Array,
    periodeList: Array,
    periodeId: [String, Number, null],
    divisiList: Array,
});

const periodeId = ref(props.periodeId ?? "");
const search = ref("");
const filterDivisi = ref("");
const activeTab = ref("all");
const currentPage = ref(1);
const perPage = 10;

// Ekstrak daftar divisi unik dari props.divisiList atau hasil
const listDivisi = computed(() => {
    if (props.divisiList && props.divisiList.length) return props.divisiList;
    if (!props.hasil) return [];
    const divisiSet = new Set(
        props.hasil
            .map(h => h.divisi)
            .filter(d => d && d !== '-')
    );
    return Array.from(divisiSet).sort();
});

// ── Watch periode ──
watch(periodeId, (val) => {
    router.get(
        "/saw/hasil",
        { periode_id: val },
        {
            preserveState: true,
            replace: true,
        },
    );
});

const periodeTerpilih = computed(() =>
    props.periodeList.find((p) => p.id == periodeId.value),
);

// ── Stat cards ──
const totalKaryawan = computed(() => props.hasil.length);

const nilaiTertinggi = computed(() => {
    if (!props.hasil.length) return { nilai: 0, nama: "-" };
    const top = props.hasil[0];
    return { nilai: top.nilai_akhir, nama: top.nama };
});

const nilaiRataRata = computed(() => {
    if (!props.hasil.length) return 0;
    const total = props.hasil.reduce((a, b) => a + Number(b.nilai_akhir), 0);
    return (total / props.hasil.length).toFixed(2);
});

const jumlahSanksi = computed(() => props.hasil.filter((h) => h.sanksi).length);

// ── Filter & search ──
const filteredHasil = computed(() => {
    return props.hasil.filter((h) => {
        const namaKaryawan = h.nama || "";
        const nipKaryawan = String(h.nip || "");

        const matchSearch =
            namaKaryawan.toLowerCase().includes(search.value.toLowerCase()) ||
            nipKaryawan.includes(search.value);
            
        const matchTab = activeTab.value === "all" || h.sanksi;
        
        const matchDivisi = 
            filterDivisi.value === "" || 
            h.divisi === filterDivisi.value;
        
        return matchSearch && matchTab && matchDivisi;
    });
});

// ── Pagination ──
const totalPages = computed(() =>
    Math.ceil(filteredHasil.value.length / perPage),
);

const pagedHasil = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    return filteredHasil.value.slice(start, start + perPage);
});

const pageInfo = computed(() => {
    const start = (currentPage.value - 1) * perPage + 1;
    const end = Math.min(
        currentPage.value * perPage,
        filteredHasil.value.length,
    );
    return `Menampilkan ${start}–${end} dari ${filteredHasil.value.length} data`;
});

watch([search, activeTab], () => {
    currentPage.value = 1;
});

// ── Rank style ──
function rankClass(r) {
    if (r === 1) return "bg-yellow-100 text-yellow-800";
    if (r === 2) return "bg-gray-100 text-gray-600";
    if (r === 3) return "bg-orange-100 text-orange-700";
    return "bg-slate-100 text-slate-500";
}

// ── Bar chart ──
let barChartInstance = null;

function renderBarChart() {
    const canvas = document.getElementById("barChart");
    if (!canvas || !props.hasil.length) return;

    if (barChartInstance) barChartInstance.destroy();

    const colors = props.hasil.map((_, i) => {
        const palette = [
            "#378ADD",
            "#639922",
            "#E24B4A",
            "#EF9F27",
            "#7F77DD",
            "#1D9E75",
        ];
        return palette[i % palette.length];
    });

    barChartInstance = new Chart(canvas, {
        type: "bar",
        data: {
            labels: filteredHasil.value.map((h) => h.nama),
            datasets: [
                {
                    label: "Nilai Akhir",
                    data: filteredHasil.value.map((h) =>
                        Number(h.nilai_akhir).toFixed(2),
                    ),
                    backgroundColor: colors,
                    borderRadius: 6,
                    borderSkipped: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ` Nilai: ${ctx.raw}`,
                    },
                },
            },
            scales: {
                y: {
                    min: 0,
                    max: 1,
                    ticks: { stepSize: 0.25, font: { size: 11 } },
                    grid: { color: "#f1f5f9" },
                },
                x: {
                    ticks: { font: { size: 11 } },
                    grid: { display: false },
                },
            },
        },
    });
}

onMounted(() => {
    if (props.hasil.length) renderBarChart();
});
watch(
    () => [props.hasil, search.value, filterDivisi.value, activeTab.value],
    () => {
        currentPage.value = 1;
        setTimeout(renderBarChart, 100);
    },
    { deep: true }
);

// ── Export ──
function exportPDF() {
    let url = `/saw/export/pdf?periode_id=${periodeId.value}`;
    if (filterDivisi.value) url += `&divisi=${encodeURIComponent(filterDivisi.value)}`;
    window.open(url, "_blank");
}

function exportExcel() {
    let url = `/saw/export/excel?periode_id=${periodeId.value}`;
    if (filterDivisi.value) url += `&divisi=${encodeURIComponent(filterDivisi.value)}`;
    window.open(url, "_blank");
}
</script>

<template>
    <Head title="Hasil SAW - SIKAP" />
    <MainLayout>
        <div class="p-6 sm:p-8 bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-gray-100/50">
            <!-- Header -->
            <div class="flex flex-col md:flex-row items-start justify-between mb-8 pb-6 border-b border-gray-100 gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-indigo-600 tracking-tight flex items-center gap-3">
                        Hasil & Ranking SAW
                    </h1>
                    <p class="text-sm text-gray-500 mt-2 font-medium">
                        <span v-if="periodeTerpilih">
                            Menampilkan hasil akhir evaluasi pegawai periode
                            <strong class="text-gray-800 bg-gray-100 px-2 py-0.5 rounded ml-0.5">
                                {{ periodeTerpilih.bulan }}
                                {{ periodeTerpilih.tahun }}
                            </strong>
                        </span>
                        <span v-else
                            >Pilih periode untuk melihat hasil ranking</span
                        >
                    </p>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <!-- Dropdown Periode -->
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-regular fa-calendar-days text-gray-400 group-hover:text-blue-500 transition-colors"></i>
                        </div>
                        <select
                            v-model="periodeId"
                            class="select bg-white text-gray-800 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 pl-10 min-w-[200px] h-11 rounded-xl font-medium"
                        >
                            <option value="">-- Pilih Periode --</option>
                            <option
                                v-for="p in periodeList"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ p.bulan }} {{ p.tahun }}
                            </option>
                        </select>
                    </div>

                    <!-- Export buttons — hanya tampil jika ada data -->
                    <template v-if="hasil.length">
                        <button
                            @click="exportPDF"
                            class="btn h-11 px-5 border border-rose-200 bg-white hover:bg-rose-50 text-rose-600 shadow-sm rounded-xl font-semibold transition-all hover:border-rose-300"
                        >
                            <i class="fa-regular fa-file-pdf mr-1.5 text-lg"></i>
                            PDF
                        </button>
                        <button
                            @click="exportExcel"
                            class="btn h-11 px-5 border border-emerald-200 bg-white hover:bg-emerald-50 text-emerald-600 shadow-sm rounded-xl font-semibold transition-all hover:border-emerald-300"
                        >
                            <i class="fa-regular fa-file-excel mr-1.5 text-lg"></i>
                            Excel
                        </button>
                    </template>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="!periodeId"
                class="flex flex-col items-center justify-center py-20 text-center bg-slate-50/50 rounded-3xl border border-dashed border-gray-200"
            >
                <div
                    class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center mb-4"
                >
                    <svg
                        width="28"
                        height="28"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="#4F46E5"
                        stroke-width="1.5"
                    >
                        <path
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2z"
                        />
                        <path
                            d="M15 5v14a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2h-2a2 2 0 00-2 2z"
                        />
                        <path d="M9 10V8a2 2 0 012-2h2a2 2 0 012 2v2" />
                    </svg>
                </div>
                <h2 class="text-base font-semibold text-gray-800 mb-2">
                    Belum Ada Periode Dipilih
                </h2>
                <p class="text-sm text-gray-500 max-w-xs leading-relaxed mb-4">
                    Pilih periode dari dropdown di atas untuk melihat hasil
                    ranking SAW.
                </p>
                <div class="flex items-center gap-1.5 text-xs text-blue-500">
                    <svg
                        width="12"
                        height="12"
                        viewBox="0 0 12 12"
                        fill="#3B82F6"
                    >
                        <circle cx="6" cy="6" r="6" />
                        <path
                            d="M6 5v4M6 3.5h.01"
                            stroke="#fff"
                            stroke-width="1.2"
                            stroke-linecap="round"
                        />
                    </svg>
                    Pastikan perhitungan SAW sudah dijalankan terlebih dahulu
                </div>
            </div>

            <!-- Belum ada hasil SAW -->
            <div
                v-else-if="periodeId && hasil.length === 0"
                class="flex flex-col items-center justify-center py-20 text-center"
            >
                <div
                    class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center mb-3"
                >
                    <svg
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="#D97706"
                        stroke-width="1.5"
                    >
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 8v4M12 16h.01" stroke-linecap="round" />
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-700 mb-1">
                    Belum ada hasil untuk periode ini
                </p>
                <p class="text-xs text-gray-400 mb-4">
                    Jalankan perhitungan SAW terlebih dahulu
                </p>
                <a
                    href="/saw/proses"
                    class="btn btn-sm bg-blue-600 hover:bg-blue-700 text-white border-none"
                >
                    Ke halaman perhitungan →
                </a>
            </div>

            <!-- Data Section -->
            <template v-else>
                <!-- Stat Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="bg-gradient-to-br from-white to-slate-50 border border-gray-100 rounded-2xl p-5 relative overflow-hidden shadow-sm hover:shadow-md transition-shadow group">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-50 rounded-full blur-xl group-hover:bg-blue-100 transition-colors"></div>
                        <div class="text-[11px] text-gray-500 uppercase tracking-widest font-bold mb-1 relative z-10">Total Evaluasi</div>
                        <div class="text-3xl font-extrabold text-gray-900 mb-1 relative z-10">{{ totalKaryawan }}</div>
                        <div class="text-xs text-gray-400 font-medium relative z-10">Pegawai dinilai</div>
                        <div class="absolute right-4 top-4 w-10 h-10 rounded-xl bg-blue-100/50 flex items-center justify-center text-blue-600 shadow-sm">
                            <i class="fa-solid fa-users"></i>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-white to-emerald-50/30 border border-gray-100 rounded-2xl p-5 relative overflow-hidden shadow-sm hover:shadow-md transition-shadow group">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-50 rounded-full blur-xl group-hover:bg-emerald-100 transition-colors"></div>
                        <div class="text-[11px] text-gray-500 uppercase tracking-widest font-bold mb-1 relative z-10">Nilai Tertinggi</div>
                        <div class="text-3xl font-extrabold text-emerald-600 mb-1 relative z-10">{{ Number(nilaiTertinggi.nilai).toFixed(2) }}</div>
                        <div class="text-xs text-gray-400 font-medium relative z-10">{{ nilaiTertinggi.nama }}</div>
                        <div class="absolute right-4 top-4 w-10 h-10 rounded-xl bg-emerald-100/50 flex items-center justify-center text-emerald-600 shadow-sm">
                            <i class="fa-solid fa-trophy"></i>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-white to-indigo-50/30 border border-gray-100 rounded-2xl p-5 relative overflow-hidden shadow-sm hover:shadow-md transition-shadow group">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-50 rounded-full blur-xl group-hover:bg-indigo-100 transition-colors"></div>
                        <div class="text-[11px] text-gray-500 uppercase tracking-widest font-bold mb-1 relative z-10">Rata-Rata Nilai</div>
                        <div class="text-3xl font-extrabold text-indigo-600 mb-1 relative z-10">{{ nilaiRataRata }}</div>
                        <div class="text-xs text-gray-400 font-medium relative z-10">Keseluruhan pegawai</div>
                        <div class="absolute right-4 top-4 w-10 h-10 rounded-xl bg-indigo-100/50 flex items-center justify-center text-indigo-600 shadow-sm">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-white to-rose-50/30 border border-gray-100 rounded-2xl p-5 relative overflow-hidden shadow-sm hover:shadow-md transition-shadow group">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-rose-50 rounded-full blur-xl group-hover:bg-rose-100 transition-colors"></div>
                        <div class="text-[11px] text-gray-500 uppercase tracking-widest font-bold mb-1 relative z-10">Pegawai Bersanksi</div>
                        <div class="text-3xl font-extrabold mb-1 relative z-10" :class="jumlahSanksi > 0 ? 'text-rose-600' : 'text-gray-400'">{{ jumlahSanksi }}</div>
                        <div class="text-xs text-gray-400 font-medium relative z-10">
                            {{ jumlahSanksi > 0 ? "Teguran atau potongan tunkin" : "Disiplin sangat baik" }}
                        </div>
                        <div class="absolute right-4 top-4 w-10 h-10 rounded-xl flex items-center justify-center shadow-sm" :class="jumlahSanksi > 0 ? 'bg-rose-100/80 text-rose-600' : 'bg-gray-100 text-gray-400'">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                    </div>
                </div>

                <!-- Toolbar & Filters -->
                <div class="flex flex-col md:flex-row items-center justify-between mb-4 flex-wrap gap-4 bg-slate-50 border border-gray-100 p-2 pl-3 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <!-- Search -->
                        <div class="relative w-full md:w-56">
                            <i class="fa-solid fa-search absolute left-3 top-2.5 text-gray-400"></i>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari pegawai / NIP..."
                                class="input input-sm h-9 pl-9 w-full bg-white border-gray-200 text-gray-800 rounded-xl shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                        </div>
                        
                        <!-- Divisi Filter -->
                        <div class="relative w-full md:w-48">
                            <i class="fa-solid fa-layer-group absolute left-3 top-2.5 text-gray-400"></i>
                            <select
                                v-model="filterDivisi"
                                class="select select-sm h-9 pl-9 w-full bg-white border-gray-200 text-gray-800 rounded-xl shadow-sm focus:border-blue-500 font-medium"
                            >
                                <option value="">Semua Divisi</option>
                                <option v-for="d in listDivisi" :key="d" :value="d">{{ d }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tabs Sanksi -->
                    <div class="flex gap-1.5 p-1 bg-white border border-gray-200 rounded-xl shadow-sm w-full md:w-auto">
                        <button
                            v-for="tab in [
                                { key: 'all', label: 'Semua Karyawan', count: filteredHasil.length + (activeTab === 'sanksi' ? (hasil.length - filteredHasil.length) : 0) },
                                { key: 'sanksi', label: 'Bersanksi Saja', count: jumlahSanksi }
                            ]"
                            :key="tab.key"
                            @click="activeTab = tab.key"
                            class="btn btn-sm h-8 rounded-lg px-4 border-none transition-all font-semibold text-xs"
                            :class="
                                activeTab === tab.key
                                    ? 'bg-blue-50 text-blue-700 hover:bg-blue-100'
                                    : 'bg-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                            "
                        >
                            <i :class="tab.key === 'sanksi' ? 'fa-solid fa-triangle-exclamation mr-1.5' : 'fa-solid fa-list mr-1.5'"></i>
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <!-- Tabel Ranking -->
                <div class="border border-gray-200 rounded-2xl overflow-hidden shadow-sm mb-4 bg-white">
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead class="bg-slate-50 border-b border-gray-100">
                                <tr>
                                    <th class="text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest w-16 text-center">Global Rank</th>
                                    <th class="text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest text-left">Pegawai</th>
                                    <th class="text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest text-left">Divisi</th>
                                    <th class="text-center text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest">Nilai Akhir</th>
                                    <th class="text-center text-gray-500 font-bold py-4 px-5 text-[10px] uppercase tracking-widest">Sanksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr
                                    v-for="(h, index) in pagedHasil"
                                    :key="h.nip"
                                    class="hover:bg-blue-50/40 transition-colors"
                                >
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-extrabold shadow-sm" :class="rankClass(h.peringkat)">
                                                {{ h.peringkat }}
                                            </span>
                                            <!-- Peringkat Relatif Filter -->
                                            <span v-if="filterDivisi" class="text-[10px] text-gray-400 font-medium">Rank #{{ (currentPage - 1) * perPage + index + 1 }} di filter</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-sm text-gray-900">{{ h.nama }}</div>
                                        <div class="text-xs text-gray-400 font-mono mt-0.5">
                                            {{ h.jabatan }} <span class="mx-1 text-gray-300">|</span> NIP: {{ h.nip }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span v-if="h.divisi && h.divisi !== '-'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100/50">
                                            <i class="fa-solid fa-layer-group text-[10px] opacity-70"></i>
                                            {{ h.divisi }}
                                        </span>
                                    </td>
                                    <td class="text-center py-4">
                                        <div class="inline-flex items-center justify-center px-4 py-1.5 rounded-xl bg-gray-50 border border-gray-200 shadow-inner font-extrabold text-blue-700">
                                            {{ Number(h.nilai_akhir).toFixed(4) }}
                                        </div>
                                    </td>
                                    <td class="text-center py-4">
                                        <div class="flex flex-col items-center gap-1">
                                            <span v-if="h.sanksi" class="text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wide bg-amber-100 text-amber-800">
                                                {{ h.sanksi }}
                                            </span>
                                            <span v-if="h.potongan > 0" class="text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wide bg-rose-100 text-rose-700">
                                                -{{ h.potongan }}% Tunkin
                                            </span>
                                            <span v-if="!h.sanksi && !(h.potongan > 0)" class="text-gray-300 text-lg">
                                                <i class="fa-solid fa-minus"></i>
                                            </span>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="pagedHasil.length === 0">
                                    <td colspan="5" class="text-center py-12 bg-slate-50/50">
                                        <div class="flex flex-col items-center justify-center text-gray-400">
                                            <i class="fa-solid fa-inbox text-4xl mb-3 text-gray-300"></i>
                                            <p class="font-medium">Tidak ada data pegawai yang cocok dengan pencarian atau filter.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    class="flex items-center justify-between mb-6 flex-wrap gap-3"
                >
                    <span class="text-xs text-gray-400">{{ pageInfo }}</span>
                    <div v-if="totalPages > 1" class="flex gap-1">
                        <button
                            @click="currentPage--"
                            :disabled="currentPage === 1"
                            class="w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-600 text-sm flex items-center justify-center hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed"
                        >
                            ‹
                        </button>
                        <button
                            v-for="p in totalPages"
                            :key="p"
                            @click="currentPage = p"
                            class="w-8 h-8 rounded-lg border text-sm flex items-center justify-center transition-colors"
                            :class="
                                currentPage === p
                                    ? 'bg-blue-600 text-white border-blue-800'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'
                            "
                        >
                            {{ p }}
                        </button>
                        <button
                            @click="currentPage++"
                            :disabled="currentPage === totalPages"
                            class="w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-600 text-sm flex items-center justify-center hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed"
                        >
                            ›
                        </button>
                    </div>
                </div>

                <!-- Bar Chart -->
                <div class="border border-gray-200 rounded-xl p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-gray-700 mb-4">
                        Perbandingan Nilai Akhir
                    </h2>
                    <div style="height: 220px">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
            </template>
        </div>
    </MainLayout>
</template>
