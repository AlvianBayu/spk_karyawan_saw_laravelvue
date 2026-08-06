<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SawService;
use App\Models\Penilaian;
use App\Models\Karyawan;
use App\Models\Kriteria;
use App\Models\Periode;
use App\Models\HasilSaw;
use App\Models\Sanksi;

class PenilaianController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin')->only(['store', 'update', 'destroy', 'destroyByPeriode']);
    }

    // ── Helper: ambil hari kerja dari periode ──
    private function getHariKerja(int $periodeId): int
    {
        return (int) Periode::where('id', $periodeId)->value('hari_kerja') ?? 20;
    }

    public function create()
    {
        $periodeList         = $periodeList = Periode::orderBy('id', 'desc')->get();
        $karyawan            = Karyawan::where('status', 'Aktif')->get(); 
        $kriteria            = Kriteria::all();
        $periodeSudahDiinput = Penilaian::select('periode_id')
                                ->distinct()
                                ->pluck('periode_id')
                                ->toArray();

        return Inertia::render('Penilaian/Create', [
            'periodeList'         => $periodeList,
            'kriteria'            => $kriteria,
            'karyawan'            => $karyawan,
            'periodeSudahDiinput' => $periodeSudahDiinput,
        ]);
    }

    public function store(Request $request)
    {
        // Cek periode sudah pernah diinput
        $sudahAda = Penilaian::where('periode_id', $request->periode_id)->exists();
        if ($sudahAda) {
            return back()->withErrors([
                'periode_id' => 'Periode ini sudah pernah diinput. Gunakan halaman Edit.'
            ]);
        }

        // Ambil hari kerja dinamis dari periode
        $hariKerja = $this->getHariKerja((int) $request->periode_id);

        $request->validate([
            'periode_id'          => 'required|exists:periode,id',
            'penilaian'           => 'required|array',
            'penilaian.*.nip'     => 'required|exists:karyawan,nip',
            'penilaian.*.nilai'   => 'required|array',
            'penilaian.*.nilai.*' => "required|integer|min:0",
        ]);

        // AMBIL GROUP DINAMIS DARI DATABASE
        $groupAkumulasi = Kriteria::where('kelompok', 'akumulasi')->pluck('kode')->toArray();
        $groupIndependen = Kriteria::where('kelompok', 'independen')->pluck('kode')->toArray();

        // Validasi Dinamis Group Akumulasi (Total harus = hari_kerja)
        foreach ($request->penilaian as $row) {
            $totalAkumulasi = collect($groupAkumulasi)
                ->sum(fn($k) => $row['nilai'][$k] ?? 0);

            if ($totalAkumulasi !== $hariKerja) {
                return back()->withErrors([
                    'penilaian' => "Total hari absensi (kriteria akumulasi) untuk NIP {$row['nip']} harus tepat {$hariKerja} hari. Saat ini: {$totalAkumulasi} hari."
                ]);
            }
        }

        // Validasi Dinamis Group Independen (Maksimal = hari_kerja)
        foreach ($request->penilaian as $row) {
            foreach ($groupIndependen as $kodeIndependen) {
                $nilaiIndependen = $row['nilai'][$kodeIndependen] ?? 0;
                if ($nilaiIndependen > $hariKerja) {
                    return back()->withErrors([
                        'penilaian' => "Nilai kriteria independen ({$kodeIndependen}) untuk NIP {$row['nip']} tidak boleh melebihi {$hariKerja}."
                    ]);
                }
            }
        }

        // Simpan data
        foreach ($request->penilaian as $row) {
            foreach ($row['nilai'] as $kode => $nilai) {
                Penilaian::create([
                    'nip'           => $row['nip'],
                    'kode_kriteria' => $kode,
                    'periode_id'    => $request->periode_id,
                    'nilai'         => $nilai,
                ]);
            }
        }

        return redirect('/penilaian')
            ->with('success', 'Data penilaian berhasil disimpan.');
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

        if ($totalAkumulasi !== $hariKerja) {
            return back()->withErrors([
                'nilai' => "Total absensi akumulasi harus tepat {$hariKerja} hari. Saat ini: {$totalAkumulasi} hari."
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