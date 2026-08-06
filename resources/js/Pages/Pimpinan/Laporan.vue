<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import PimpinanLayout from '@/Layouts/PimpinanLayout.vue';
import Chart from "chart.js/auto";

const props = defineProps({
    hasil: Array,
    periodeList: Array,
    periodeId: [String, Number, null],
});

const periodeId = ref(props.periodeId ?? "");
const search = ref("");
const activeTab = ref("all");
const currentPage = ref(1);
const perPage = 5;

// ── Watch periode ──
// ── Watch periode ──
watch(periodeId, (val) => {
    router.get(
        "/pimpinan/laporan", 
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
        // 1. Amankan nama (jika null/kosong, jadikan string kosong)
        // Jika nama ada di dalam relasi karyawan, kita pakai h.karyawan?.nama
        const namaKaryawan = h.nama || h.karyawan?.nama || "";
        
        // 2. Paksa NIP menjadi String agar fungsi .includes() bisa berjalan
        const nipKaryawan = String(h.nip || "");

        // 3. Pencarian yang sudah aman dari error
        const matchSearch =
            namaKaryawan.toLowerCase().includes(search.value.toLowerCase()) ||
            nipKaryawan.includes(search.value);
            
        const matchTab = activeTab.value === "all" || h.sanksi;
        
        return matchSearch && matchTab;
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
            labels: props.hasil.map((h) => h.nama),
            datasets: [
                {
                    label: "Nilai Akhir",
                    data: props.hasil.map((h) =>
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
    () => props.hasil,
    () => {
        currentPage.value = 1;
        setTimeout(renderBarChart, 100);
    },
);

// ── Export ──
function exportPDF() {
    window.open(`/pimpinan/laporan/export/pdf?periode_id=${periodeId.value}`, '_blank')
}
function exportExcel() {
    window.open(`/pimpinan/laporan/export/excel?periode_id=${periodeId.value}`, '_blank')
}
</script>

<template>
    <Head title="SIKAP" />
    <PimpinanLayout>
        <div class="p-6 bg-white rounded-lg shadow-md">
            <!-- Header -->
            <div class="flex items-start justify-between mb-6 flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">
                        Hasil & Ranking SAW
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        <span v-if="periodeTerpilih">
                            Menampilkan hasil penilaian ·
                            <strong class="text-gray-700">
                                {{ periodeTerpilih.bulan }}
                                {{ periodeTerpilih.tahun }}
                            </strong>
                        </span>
                        <span v-else
                            >Pilih periode untuk melihat hasil ranking</span
                        >
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Dropdown Periode -->
                    <div class="relative">
                        <svg
                            class="absolute left-3 top-2.5 w-3.5 h-3.5 text-gray-400 pointer-events-none"
                            viewBox="0 0 16 16"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                        >
                            <rect x="1" y="3" width="14" height="12" rx="2" />
                            <path d="M1 7h14M5 1v4M11 1v4" />
                        </svg>
                        <select
                            v-model="periodeId"
                            class="select select-bordered select-sm pl-8 min-w-[180px] bg-white text-gray-800 border-gray-300 shadow-sm"
                        >
                            <option value="">-- Pilih Periode --</option>
                            <option
                                v-for="p in periodeList"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ p.bulan }} / {{ p.tahun }}
                            </option>
                        </select>
                    </div>

                    <!-- Export buttons — hanya tampil jika ada data -->
                    <template v-if="hasil.length">
                        <button
                            @click="exportPDF"
                            class="btn btn-sm border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 gap-1.5"
                        >
                            <svg
                                width="13"
                                height="13"
                                viewBox="0 0 12 12"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path d="M2 2h5l3 3v7H2z" />
                                <path d="M7 2v3h3" />
                            </svg>
                            PDF
                        </button>
                        <button
                            @click="exportExcel"
                            class="btn btn-sm border border-green-200 bg-green-50 text-green-700 hover:bg-green-100 gap-1.5"
                        >
                            <svg
                                width="13"
                                height="13"
                                viewBox="0 0 12 12"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path d="M2 2h5l3 3v7H2z" />
                                <path d="M7 2v3h3" />
                            </svg>
                            Excel
                        </button>
                    </template>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="!periodeId"
                class="flex flex-col items-center justify-center py-24 text-center"
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
                <div class="grid grid-cols-4 gap-3 mb-6">
                    <div
                        class="bg-slate-50 border border-gray-200 rounded-xl px-4 py-3"
                    >
                        <div
                            class="text-xs text-gray-400 uppercase tracking-wide font-medium"
                        >
                            Total Karyawan
                        </div>
                        <div class="text-2xl font-semibold text-gray-900 mt-1">
                            {{ totalKaryawan }}
                        </div>
                        <div class="text-xs text-gray-400 mt-0.5">
                            {{ periodeTerpilih?.bulan }}
                            {{ periodeTerpilih?.tahun }}
                        </div>
                    </div>
                    <div
                        class="bg-slate-50 border border-gray-200 rounded-xl px-4 py-3"
                    >
                        <div
                            class="text-xs text-gray-400 uppercase tracking-wide font-medium"
                        >
                            Nilai Tertinggi
                        </div>
                        <div class="text-2xl font-semibold text-green-600 mt-1">
                            {{ Number(nilaiTertinggi.nilai).toFixed(2) }}
                        </div>
                        <div class="text-xs text-gray-400 mt-0.5">
                            {{ nilaiTertinggi.nama }}
                        </div>
                    </div>
                    <div
                        class="bg-slate-50 border border-gray-200 rounded-xl px-4 py-3"
                    >
                        <div
                            class="text-xs text-gray-400 uppercase tracking-wide font-medium"
                        >
                            Nilai Rata-rata
                        </div>
                        <div class="text-2xl font-semibold text-blue-600 mt-1">
                            {{ nilaiRataRata }}
                        </div>
                        <div class="text-xs text-gray-400 mt-0.5">
                            Dari {{ totalKaryawan }} karyawan
                        </div>
                    </div>
                    <div
                        class="bg-slate-50 border border-gray-200 rounded-xl px-4 py-3"
                    >
                        <div
                            class="text-xs text-gray-400 uppercase tracking-wide font-medium"
                        >
                            Bersanksi
                        </div>
                        <div
                            class="text-2xl font-semibold mt-1"
                            :class="
                                jumlahSanksi > 0
                                    ? 'text-red-600'
                                    : 'text-gray-400'
                            "
                        >
                            {{ jumlahSanksi }}
                        </div>
                        <div class="text-xs text-gray-400 mt-0.5">
                            {{
                                jumlahSanksi > 0
                                    ? "Teguran · potongan tunkin"
                                    : "Tidak ada sanksi"
                            }}
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <div
                    class="flex items-center justify-between mb-3 flex-wrap gap-3"
                >
                    <div class="relative">
                        <svg
                            class="absolute left-2.5 top-2 w-4 h-4 text-gray-400"
                            viewBox="0 0 16 16"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                        >
                            <circle cx="6.5" cy="6.5" r="4.5" />
                            <path d="M10 10l3 3" />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari karyawan..."
                            class="input input-bordered input-sm pl-8 w-56 bg-white border-gray-300 text-gray-800 shadow-sm"
                        />
                    </div>
                    <div class="flex gap-1.5 p-1 bg-gray-200/60 rounded-lg">
                        <button
                            v-for="tab in [
                                {
                                    key: 'all',
                                    label: 'Semua',
                                    count: hasil.length,
                                },
                                {
                                    key: 'sanksi',
                                    label: 'Bersanksi',
                                    count: jumlahSanksi,
                                },
                            ]"
                            :key="tab.key"
                            @click="activeTab = tab.key"
                            class="btn btn-xs rounded-md px-4 border-none transition-all"
                            :class="
                                activeTab === tab.key
                                    ? 'bg-white text-gray-900 shadow-sm font-semibold hover:bg-white'
                                    : 'bg-transparent text-gray-500 font-normal shadow-none'
                            "
                        >
                            {{ tab.label }} ({{ tab.count }})
                        </button>
                    </div>
                </div>

                <!-- Tabel Ranking -->
                <div
                    class="border border-gray-200 rounded-xl overflow-hidden shadow-sm mb-3"
                >
                    <div class="overflow-x-auto">
                        <table class="table table-sm w-full">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th
                                        class="text-gray-700 font-semibold py-3 px-4 text-xs uppercase tracking-wide w-14"
                                    >
                                        No
                                    </th>
                                    <th
                                        class="text-gray-700 font-semibold py-3 px-4 text-xs uppercase tracking-wide"
                                    >
                                        Karyawan
                                    </th>
                                    <th
                                        class="text-center text-gray-700 font-semibold py-3 text-xs uppercase tracking-wide"
                                    >
                                        Nilai Akhir
                                    </th>
                                    <th
                                        class="text-center text-gray-700 font-semibold py-3 text-xs uppercase tracking-wide"
                                    >
                                        Sanksi
                                    </th>
                                    <th
                                        class="text-center text-gray-700 font-semibold py-3 text-xs uppercase tracking-wide"
                                    >
                                        Potongan Tunkin
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="h in pagedHasil"
                                    :key="h.nip"
                                    class="border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors"
                                >
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-medium"
                                            :class="rankClass(h.peringkat)"
                                        >
                                            {{ h.peringkat }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            class="font-medium text-sm text-gray-900"
                                        >
                                            {{ h.nama }}
                                        </div>
                                        <div
                                            class="text-xs text-gray-400 font-mono mt-0.5"
                                        >
                                            {{ h.jabatan }} · #{{ h.nip }}
                                        </div>
                                    </td>
                                    <td
                                        class="text-center py-3 font-semibold text-gray-800"
                                    >
                                        {{ Number(h.nilai_akhir).toFixed(2) }}
                                    </td>
                                    <td class="text-center py-3">
                                        <span
                                            v-if="h.sanksi"
                                            class="text-xs px-2.5 py-1 rounded-full font-medium bg-amber-100 text-amber-700 border border-amber-200"
                                        >
                                            {{ h.sanksi }}
                                        </span>
                                        <span
                                            v-else
                                            class="text-gray-300 text-sm"
                                            >—</span
                                        >
                                    </td>
                                    <td class="text-center py-3">
                                        <span
                                            v-if="h.potongan > 0"
                                            class="text-xs px-2.5 py-1 rounded-full font-medium bg-red-100 text-red-600 border border-red-200"
                                        >
                                            {{ h.potongan }}%
                                        </span>
                                        <span
                                            v-else
                                            class="text-xs px-2.5 py-1 rounded-full font-medium bg-gray-100 text-gray-400"
                                        >
                                            0%
                                        </span>
                                    </td>
                                </tr>

                                <tr v-if="pagedHasil.length === 0">
                                    <td
                                        colspan="5"
                                        class="text-center py-10 text-gray-400 text-sm"
                                    >
                                        Tidak ada data yang cocok.
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
                                    ? 'bg-gray-900 text-white border-gray-900'
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
    </PimpinanLayout>
</template>
