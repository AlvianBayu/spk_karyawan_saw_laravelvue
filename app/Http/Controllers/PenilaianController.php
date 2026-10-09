<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\SawService;
use App\Models\Penilaian;
use App\Models\Karyawan;
use App\Models\Kriteria;
use App\Models\Periode;
use App\Models\HasilSaw;
use App\Models\Sanksi;
use Maatwebsite\Excel\Facades\Excel;

class PenilaianController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin')->only(['store', 'update', 'destroy', 'destroyByPeriode', 'import']);
    }

    // ── Helper: ambil hari kerja dari periode ──
    private function getHariKerja(int $periodeId): int
    {
        return (int) Periode::where('id', $periodeId)->value('hari_kerja') ?? 20;
    }

    /**
     * Dapatkan daftar NIP karyawan yang sah (eligible) untuk setiap periode.
     * Karyawan baru yang baru ada pada periode baru TIDAK AKAN muncul pada periode sebelumnya.
     */
    private function getEligibleNipsByPeriode($periodeList, $activeKaryawan)
    {
        $bulanIndex = [
            'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
            'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
            'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12
        ];

        // Mapping periode ke urutan kronologis
        $periodeWithOrder = $periodeList->map(function ($p) use ($bulanIndex) {
            $b = $bulanIndex[$p->bulan] ?? 1;
            $order = ((int)$p->tahun * 12) + $b;
            return [
                'id'         => $p->id,
                'order'      => $order,
                'created_at' => $p->created_at,
            ];
        })->sortBy('order')->values();

        // Riwayat penilaian per karyawan
        $penilaianPerNip = Penilaian::select('nip', 'periode_id')
            ->distinct()
            ->get()
            ->groupBy('nip');

        // Periode yang sudah ada penilaian
        $assessedPeriodeIds = Penilaian::distinct()->pluck('periode_id')->toArray();

        // Cari periode terlama yang BELUM memiliki data penilaian sama sekali
        $firstUnassessedPeriode = $periodeWithOrder->first(function ($p) use ($assessedPeriodeIds) {
            return !in_array($p['id'], $assessedPeriodeIds);
        }) ?? $periodeWithOrder->last();

        $minOrderForNewKaryawan = $firstUnassessedPeriode ? $firstUnassessedPeriode['order'] : 0;

        $eligibleNipsByPeriode = [];

        foreach ($periodeWithOrder as $p) {
            $eligibleNips = [];
            foreach ($activeKaryawan as $kar) {
                $nip = (string) $kar->nip;
                if ($penilaianPerNip->has($nip)) {
                    // Karyawan lama: hanya eligible jika periode >= periode pertama dia dinilai
                    $empPeriodeIds = $penilaianPerNip->get($nip)->pluck('periode_id')->toArray();
                    $minEmpOrder = $periodeWithOrder->whereIn('id', $empPeriodeIds)->min('order');
                    if ($p['order'] >= $minEmpOrder) {
                        $eligibleNips[] = $nip;
                    }
                } else {
                    // Karyawan baru: hanya eligible di periode baru (periode pertama yang belum dinilai ke depan)
                    if ($p['order'] >= $minOrderForNewKaryawan) {
                        $eligibleNips[] = $nip;
                    }
                }
            }
            $eligibleNipsByPeriode[$p['id']] = $eligibleNips;
        }

        return $eligibleNipsByPeriode;
    }

    public function create()
    {
        $periodeList           = Periode::orderBy('id', 'desc')->get();
        $karyawan              = Karyawan::where('status', 'Aktif')->get(); 
        $kriteria              = Kriteria::all();
        
        $existingNipsByPeriode = Penilaian::select('periode_id', 'nip')
                                    ->distinct()
                                    ->get()
                                    ->groupBy('periode_id')
                                    ->map(function ($items) {
                                        return $items->pluck('nip')->map(fn($n) => (string)$n)->values()->toArray();
                                    });

        $eligibleNipsByPeriode = $this->getEligibleNipsByPeriode($periodeList, $karyawan);

        return Inertia::render('Penilaian/Create', [
            'periodeList'           => $periodeList,
            'kriteria'              => $kriteria,
            'karyawan'              => $karyawan,
            'existingNipsByPeriode' => $existingNipsByPeriode,
            'eligibleNipsByPeriode' => $eligibleNipsByPeriode,
        ]);
    }

    // ── Import penilaian dari file Excel/CSV ──
    public function import(Request $request)
    {
        $request->validate([
            'periode_id' => 'required|exists:periode,id',
            'file'       => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        // Cek periode sudah pernah diinput
        $sudahAda = Penilaian::where('periode_id', $request->periode_id)->exists();
        if ($sudahAda) {
            return back()->withErrors([
                'file' => 'Periode ini sudah pernah diinput. Gunakan halaman Edit untuk mengubah data.'
            ]);
        }

        $hariKerja  = $this->getHariKerja((int) $request->periode_id);
        $kriteria   = Kriteria::all();
        $kodeList   = $kriteria->pluck('kode')->toArray();

        $groupAkumulasi  = $kriteria->where('kelompok', 'akumulasi')->pluck('kode')->toArray();
        $groupIndependen = $kriteria->where('kelompok', 'independen')->pluck('kode')->toArray();

        // Baca file Excel
        try {
            $data = Excel::toArray(null, $request->file('file'));
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'File tidak dapat dibaca. Pastikan format file benar.']);
        }

        if (empty($data) || empty($data[0])) {
            return back()->withErrors(['file' => 'File kosong atau tidak memiliki data.']);
        }

        $rows    = $data[0];
        $header  = array_map('strtolower', array_map('trim', $rows[0]));
        $dataRows = array_slice($rows, 1);

        // Validasi header harus mengandung NIP dan semua kode kriteria
        if (!in_array('nip', $header)) {
            return back()->withErrors(['file' => 'Kolom NIP tidak ditemukan di file. Pastikan baris pertama berisi header: NIP, ' . implode(', ', $kodeList)]);
        }

        $missingKriteria = [];
        foreach ($kodeList as $kode) {
            if (!in_array(strtolower($kode), $header)) {
                $missingKriteria[] = $kode;
            }
        }
        if (!empty($missingKriteria)) {
            return back()->withErrors(['file' => 'Kolom kriteria berikut tidak ditemukan di file: ' . implode(', ', $missingKriteria)]);
        }

        $nipIndex    = array_search('nip', $header);
        $kriteriaIdx = [];
        foreach ($kodeList as $kode) {
            $kriteriaIdx[$kode] = array_search(strtolower($kode), $header);
        }

        $errors     = [];
        $penilaian  = [];
        $rowNumber  = 2; // Mulai dari baris 2 (setelah header)

        foreach ($dataRows as $row) {
            // Skip baris kosong
            if (empty(array_filter($row))) {
                $rowNumber++;
                continue;
            }

            $nip = trim($row[$nipIndex] ?? '');

            // Validasi NIP ada di database
            $karyawan = Karyawan::where('nip', $nip)->where('status', 'Aktif')->first();
            if (!$karyawan) {
                $errors[] = "Baris {$rowNumber}: NIP {$nip} tidak ditemukan atau tidak aktif.";
                $rowNumber++;
                continue;
            }

            $nilai = [];
            foreach ($kodeList as $kode) {
                $val = (int) ($row[$kriteriaIdx[$kode]] ?? 0);
                $nilai[$kode] = $val;
            }

            // Validasi akumulasi
            $totalAkumulasi = collect($groupAkumulasi)->sum(fn($k) => $nilai[$k] ?? 0);
            if ($totalAkumulasi !== $hariKerja) {
                $errors[] = "Baris {$rowNumber}: NIP {$nip} — total akumulasi ({$totalAkumulasi}) harus tepat {$hariKerja} hari.";
                $rowNumber++;
                continue;
            }

            // Validasi independen
            foreach ($groupIndependen as $kodeIndependen) {
                if (($nilai[$kodeIndependen] ?? 0) > $hariKerja) {
                    $errors[] = "Baris {$rowNumber}: NIP {$nip} — nilai {$kodeIndependen} melebihi {$hariKerja}.";
                }
            }

            $penilaian[] = ['nip' => $nip, 'nilai' => $nilai];
            $rowNumber++;
        }

        if (!empty($errors)) {
            return back()->withErrors(['file' => implode(' | ', array_slice($errors, 0, 5))])
                         ->with('importErrors', $errors);
        }

        if (empty($penilaian)) {
            return back()->withErrors(['file' => 'Tidak ada data valid yang ditemukan di file.']);
        }

        // Simpan data
        DB::transaction(function () use ($penilaian, $request) {
            foreach ($penilaian as $row) {
                foreach ($row['nilai'] as $kode => $nilai) {
                    Penilaian::create([
                        'nip'           => $row['nip'],
                        'kode_kriteria' => $kode,
                        'periode_id'    => $request->periode_id,
                        'nilai'         => $nilai,
                    ]);
                }
            }
        });

        $total = count($penilaian);
        return redirect('/penilaian')
            ->with('success', "Berhasil mengimpor data penilaian untuk {$total} pegawai.");
    }

    // ── Download template Excel ──
    public function downloadTemplate(Request $request)
    {
        $kriteria = Kriteria::all();
        $karyawan = Karyawan::where('status', 'Aktif')->get();

        if ($request->filled('periode_id')) {
            $periodeList = Periode::orderBy('id', 'desc')->get();
            $eligibleNipsMap = $this->getEligibleNipsByPeriode($periodeList, $karyawan);
            $eligibleNips = $eligibleNipsMap[$request->periode_id] ?? [];
            if (!empty($eligibleNips)) {
                $karyawan = $karyawan->filter(fn($k) => in_array((string)$k->nip, $eligibleNips))->values();
            }
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Penilaian');

        // Header
        $col = 'A';
        $sheet->setCellValue($col . '1', 'NIP');
        $col++;
        $sheet->setCellValue($col . '1', 'Nama (info)');
        $col++;
        foreach ($kriteria as $k) {
            $sheet->setCellValue($col . '1', $k->kode);
            $col++;
        }

        // Data karyawan sebagai contoh
        $row = 2;
        foreach ($karyawan as $kar) {
            $sheet->setCellValue('A' . $row, $kar->nip);
            $sheet->setCellValue('B' . $row, $kar->nama);
            // Kolom kriteria diisi 0
            $col = 'C';
            foreach ($kriteria as $k) {
                $sheet->setCellValue($col . $row, 0);
                $col++;
            }
            $row++;
        }

        // Style header
        $lastCol = chr(ord('A') + 1 + $kriteria->count());
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'],
            ],
        ]);

        // Auto width
        foreach (range('A', $lastCol) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = 'template_penilaian.xlsx';
        $tempFile = storage_path('app/' . $fileName);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'periode_id'          => 'required|exists:periode,id',
            'penilaian'           => 'required|array',
            'penilaian.*.nip'     => 'required|exists:karyawan,nip',
            'penilaian.*.nilai'   => 'required|array',
            'penilaian.*.nilai.*' => "required|integer|min:0",
        ]);

        $hariKerja = $this->getHariKerja((int) $request->periode_id);

        // AMBIL GROUP DINAMIS DARI DATABASE
        $groupAkumulasi  = Kriteria::where('kelompok', 'akumulasi')->pluck('kode')->toArray();
        $groupIndependen = Kriteria::where('kelompok', 'independen')->pluck('kode')->toArray();

        $validPenilaianToSave = [];

        foreach ($request->penilaian as $row) {
            $totalAkumulasi = collect($groupAkumulasi)
                ->sum(fn($k) => (int)($row['nilai'][$k] ?? 0));

            // Error jika total akumulasi MELEBIHI hari_kerja
            if ($totalAkumulasi > $hariKerja) {
                return back()->withErrors([
                    'penilaian' => "Total hari absensi (kriteria akumulasi) untuk NIP {$row['nip']} tidak boleh melebihi {$hariKerja} hari. Saat ini: {$totalAkumulasi} hari."
                ]);
            }

            // Error jika independen MELEBIHI hari_kerja
            foreach ($groupIndependen as $kodeIndependen) {
                $nilaiIndependen = (int)($row['nilai'][$kodeIndependen] ?? 0);
                if ($nilaiIndependen > $hariKerja) {
                    return back()->withErrors([
                        'penilaian' => "Nilai kriteria independen ({$kodeIndependen}) untuk NIP {$row['nip']} tidak boleh melebihi {$hariKerja}."
                    ]);
                }
            }

            // HANYA data yang total akumulasinya TEPAT sama dengan hariKerja yang akan disimpan
            if ($totalAkumulasi === $hariKerja) {
                $validPenilaianToSave[] = $row;
            }
        }

        if (empty($validPenilaianToSave)) {
            return back()->withErrors([
                'penilaian' => "Tidak ada data pegawai yang memenuhi syarat kriteria untuk disimpan (total absensi kriteria akumulasi harus tepat {$hariKerja} hari). Data 0 atau belum lengkap tidak akan disimpan."
            ]);
        }

        // Simpan data ke database
        DB::transaction(function () use ($validPenilaianToSave, $request) {
            foreach ($validPenilaianToSave as $row) {
                Penilaian::where('nip', $row['nip'])
                    ->where('periode_id', $request->periode_id)
                    ->delete();

                foreach ($row['nilai'] as $kode => $nilai) {
                    Penilaian::create([
                        'nip'           => $row['nip'],
                        'kode_kriteria' => $kode,
                        'periode_id'    => $request->periode_id,
                        'nilai'         => (int)$nilai,
                    ]);
                }
            }
        });

        $countSaved = count($validPenilaianToSave);

        return redirect()->back()->with('success', "Berhasil menyimpan penilaian untuk {$countSaved} pegawai. Data yang belum diisi / belum lengkap tetap berada pada halaman penginputan.");
    }

    public function index(Request $request)
    {
        $periodeId   = $request->periode_id;
        $kriteria    = Kriteria::all();
        $periodeList = Periode::orderBy('id', 'desc')->get();
        $karyawan    = collect();

        if ($periodeId) {
            // PERBAIKAN: Menggunakan whereHas untuk mengambil data historis periode tersebut
            $karyawan = Karyawan::whereHas('penilaian', function ($q) use ($periodeId) {
                    $q->where('periode_id', $periodeId);
                })
                ->with(['penilaian' => function ($q) use ($periodeId) {
                    $q->where('periode_id', $periodeId);
                }])->get();
        }

        return Inertia::render('Penilaian/Index', [
            'karyawan'    => $karyawan,
            'kriteria'    => $kriteria,
            'periodeList' => $periodeList,
            'periodeId'   => $periodeId,
        ]);
    }

    public function update(Request $request, $nip)
    {
        $hariKerja = $this->getHariKerja((int) $request->periode_id);

        $request->validate([
            'periode_id' => 'required|exists:periode,id',
            'nilai'      => 'required|array',
            'nilai.*'    => "required|integer|min:0",
        ]);

        // AMBIL GROUP DINAMIS DARI DATABASE
        $groupAkumulasi = Kriteria::where('kelompok', 'akumulasi')->pluck('kode')->toArray();
        $groupIndependen = Kriteria::where('kelompok', 'independen')->pluck('kode')->toArray();

        // Validasi Dinamis Group Akumulasi
        $totalAkumulasi = collect($groupAkumulasi)->sum(fn($k) => $request->nilai[$k] ?? 0);

        if ($totalAkumulasi > $hariKerja) {
            return back()->withErrors([
                'nilai' => "Total absensi akumulasi tidak boleh melebihi {$hariKerja} hari. Saat ini: {$totalAkumulasi} hari."
            ]);
        }

        // Validasi Dinamis Group Independen
        foreach ($groupIndependen as $kodeIndependen) {
            $nilaiIndependen = $request->nilai[$kodeIndependen] ?? 0;
            if ($nilaiIndependen > $hariKerja) {
                return back()->withErrors([
                    'nilai' => "Nilai kriteria {$kodeIndependen} tidak boleh melebihi {$hariKerja}."
                ]);
            }
        }

        DB::transaction(function () use ($request, $nip) {
            // Update data mentah penilaian
            foreach ($request->nilai as $kode => $nilai) {
                Penilaian::where('nip', $nip)
                    ->where('kode_kriteria', $kode)
                    ->where('periode_id', $request->periode_id)
                    ->update(['nilai' => $nilai]);
            }

            // Panggil Service untuk hitung ulang hasil SAW
            $sawService = new SawService();
            $sawService->hitungUlang($request->periode_id);
        });

        return redirect("/penilaian?periode_id={$request->periode_id}")
            ->with('success', 'Data berhasil diperbarui dan skor SAW otomatis disesuaikan.');
    }

    public function destroy(Request $request, $nip)
    {
        DB::transaction(function () use ($request, $nip) {
            // Hapus data penilaian mentah
            Penilaian::where('nip', $nip)
                ->where('periode_id', $request->periode_id)
                ->delete();

            // Panggil hitung ulang (KARENA JIKA 1 DATA HILANG, MAX/MIN BERUBAH)
            $sawService = new SawService();
            $sawService->hitungUlang($request->periode_id);
        });

        return redirect("/penilaian?periode_id={$request->periode_id}")
            ->with('success', 'Data penilaian berhasil dihapus dan skor SAW telah disesuaikan ulang.');
    }

    public function destroyByPeriode($periodeId)
    {
        DB::transaction(function () use ($periodeId) {
            // Hapus semua data mentah, hasil saw, dan sanksi pada periode tersebut
            Penilaian::where('periode_id', $periodeId)->delete();
            HasilSaw::where('periode_id', $periodeId)->delete();
            Sanksi::where('periode_id', $periodeId)->delete();
        });

        return redirect()->back()->with('success', 'Seluruh data penilaian dan hasil perhitungan pada periode ini berhasil dibersihkan.');
    }
}