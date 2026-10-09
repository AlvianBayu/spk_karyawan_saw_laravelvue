<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Kriteria;
use App\Models\HasilSaw;
use App\Models\Periode;
use App\Services\SawService;
use App\Exports\HasilSawExport;
use App\Models\Karyawan;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class SawController extends Controller
{
    public function index(Request $request)
    {
        $periodeId   = $request->periode_id;
        $periodeList = Periode::orderBy('id', 'desc')->get();
        $kcriteria   = Kriteria::all();

        $rawData      = collect();
        $normData     = collect();
        $weightedData = collect();
        $optimum      = [];
        
        $hasExistingData = false;
        $progress100     = false;
        $pendingCount    = 0;

        if ($periodeId) {
            $hasExistingData = HasilSaw::where('periode_id', $periodeId)->exists();
            $hariKerja       = Periode::where('id', $periodeId)->value('hari_kerja') ?? 20;

            // Pengecekan Progress 100%
            $groupAkumulasi   = Kriteria::where('kelompok', 'akumulasi')->pluck('kode')->toArray();
            $totalActive      = Karyawan::where('status', 'Aktif')->count();
            $penilaianPeriode = \App\Models\Penilaian::where('periode_id', $periodeId)->get()->groupBy('nip');
            
            $filledCount = 0;
            foreach ($penilaianPeriode as $nip => $rows) {
                $total = 0;
                foreach ($rows as $row) {
                    if (in_array($row->kode_kriteria, $groupAkumulasi)) {
                        $total += $row->nilai;
                    }
                }
                if ($total == $hariKerja) {
                    $filledCount++;
                }
            }

            $progress100  = ($filledCount >= $totalActive) && ($totalActive > 0);
            $pendingCount = max(0, $totalActive - $filledCount);

            if ($progress100) {
                $sawService = new SawService();
                $hasilCalc  = $sawService->calculate($periodeId);

                $rawData      = $hasilCalc['rawData'];
                $normData     = $hasilCalc['normData'];
                $weightedData = $hasilCalc['weightedData'];
                $optimum      = $hasilCalc['optimum'];
            }
        }

        return Inertia::render('Saw/Proses', [
            'periodeList'     => $periodeList,
            'periodeId'       => $periodeId,
            'kriteria'        => $kcriteria,
            'rawData'         => $rawData,
            'normData'        => $normData,
            'weightedData'    => $weightedData,
            'optimum'         => $optimum,
            'hasExistingData' => $hasExistingData,
            'progress100'     => $progress100,
            'pendingCount'    => $pendingCount,
        ]);
    }

    public function simpan(Request $request)
    {
        $request->validate([
            'periode_id'    => 'required|exists:periode,id',
            'weighted_data' => 'required|array',
        ]);

        $sawService = new SawService();
        $sawService->storeOrUpdate($request->periode_id, $request->weighted_data);

        return redirect("/saw/hasil?periode_id={$request->periode_id}")
            ->with('success', 'Perhitungan SAW berhasil diproses dan disimpan.');
    }

    public function recalculate(Request $request, $periodeId)
    {
        $hariKerja       = Periode::where('id', $periodeId)->value('hari_kerja') ?? 20;
        $groupAkumulasi  = Kriteria::where('kelompok', 'akumulasi')->pluck('kode')->toArray();
        $totalActive     = Karyawan::where('status', 'Aktif')->count();
        $penilaianPeriode = \App\Models\Penilaian::where('periode_id', $periodeId)->get()->groupBy('nip');
        
        $filledCount = 0;
        foreach ($penilaianPeriode as $nip => $rows) {
            $total = 0;
            foreach ($rows as $row) {
                if (in_array($row->kode_kriteria, $groupAkumulasi)) {
                    $total += $row->nilai;
                }
            }
            if ($total == $hariKerja) {
                $filledCount++;
            }
        }

        if ($filledCount < $totalActive) {
            return redirect()->back()->withErrors(['error' => 'Progres belum 100%. Tidak bisa hitung ulang.']);
        }

        $sawService = new SawService();
        $sawService->hitungUlang($periodeId);

        return redirect()->back()->with('success', 'Perhitungan ulang berhasil!');
    }

    public function hasil(Request $request)
    {
        $periodeId   = $request->periode_id;
        $periodeList = Periode::orderBy('id', 'desc')->get();

        $hasil = collect();
        $divisiList = collect();

        if ($periodeId) {
            $hasilRaw = HasilSaw::with([
                'karyawan' => function ($q) {
                    $q->withTrashed();
                }, 
                'sanksi' => function ($q) use ($periodeId) {
                    $q->where('periode_id', $periodeId);
                }
            ])
            ->where('periode_id', $periodeId)
            ->orderBy('peringkat')
            ->get();

            $hasil = $hasilRaw->map(fn($h) => [
                'nip'         => $h->nip,
                'nama'        => $h->karyawan?->nama ?? $h->karyawan?->nama_karyawan ?? 'Karyawan Resign',
                'divisi'      => $h->karyawan?->divisi ?? '-',
                'jabatan'     => $h->karyawan?->jabatan ?? '-',
                'nilai_akhir' => $h->nilai_akhir,
                'peringkat'   => $h->peringkat,
                'sanksi'      => $h->sanksi?->keterangan,
                'jumlah_alpa' => $h->sanksi?->jumlah_alpa ?? 0,
                'potongan'    => $h->sanksi?->potongan_persen ?? 0,
            ]);

            // Daftar divisi unik dari hasil
            $divisiList = $hasil->pluck('divisi')
                ->filter(fn($d) => $d && $d !== '-')
                ->unique()
                ->sort()
                ->values();
        }

        return Inertia::render('Saw/Hasil', [
            'hasil'       => $hasil,
            'periodeList' => $periodeList,
            'periodeId'   => $periodeId,
            'divisiList'  => $divisiList,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $periodeId = $request->periode_id;
        $divisi    = $request->divisi;
        $periode   = Periode::findOrFail($periodeId);

        $query = HasilSaw::with([
            'karyawan' => function ($q) {
                $q->withTrashed();
            },
            'sanksi' => function ($q) use ($periodeId) {
                $q->where('periode_id', $periodeId);
            }
        ])
        ->where('periode_id', $periodeId);
        
        if ($divisi) {
            $query->whereHas('karyawan', function($q) use ($divisi) {
                $q->withTrashed();
                if ($divisi === 'Tanpa Divisi') {
                    $q->whereNull('divisi')->orWhere('divisi', '');
                } else {
                    $q->where('divisi', $divisi);
                }
            });
        }

        $hasil = $query->orderBy('peringkat')->get();

        $pdf = Pdf::loadView('exports.hasil_saw_pdf', compact('hasil', 'periode', 'divisi'))
            ->setPaper('a4', 'landscape');

        return $pdf->download("hasil_saw_{$periode->bulan}_{$periode->tahun}.pdf");
    }

    public function exportExcel(Request $request)
    {
        $periodeId = $request->periode_id;
        $divisi    = $request->divisi;
        $periode   = Periode::findOrFail($periodeId);
        return Excel::download(new HasilSawExport($periodeId, $divisi), "hasil_saw_{$periode->bulan}_{$periode->tahun}.xlsx");
    }
}