<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Kriteria;
use App\Models\HasilSaw;
use App\Models\Periode;
use App\Services\SawService;
use App\Exports\HasilSawExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

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

        if ($periodeId) {
            $hasExistingData = HasilSaw::where('periode_id', $periodeId)->exists();

            $sawService = new SawService();
            $hasilCalc  = $sawService->calculate($periodeId);

            $rawData      = $hasilCalc['rawData'];
            $normData     = $hasilCalc['normData'];
            $weightedData = $hasilCalc['weightedData'];
            $optimum      = $hasilCalc['optimum'];
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
        $sawService = new SawService();
        $sawService->hitungUlang($periodeId);

        return redirect()->back()->with('success', 'Perhitungan ulang berhasil!');
    }

    public function hasil(Request $request)
    {
        $periodeId   = $request->periode_id;
        $periodeList = Periode::orderBy('id', 'desc')->get();

        $hasil = collect();

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
                'nama'        => $h->karyawan?->nama ?? $h->karyawan?->nama_karyawan ?? 'Karyawan Resign', // Opsional: Ubah '-' menjadi teks yang lebih jelas
                'jabatan'     => $h->karyawan?->jabatan ?? '-',
                'nilai_akhir' => $h->nilai_akhir,
                'peringkat'   => $h->peringkat,
                'sanksi'      => $h->sanksi?->keterangan,
                'jumlah_alpa' => $h->sanksi?->jumlah_alpa ?? 0,
                'potongan'    => $h->sanksi?->potongan_persen ?? 0,
            ]);
        }

        return Inertia::render('Saw/Hasil', [
            'hasil'       => $hasil,
            'periodeList' => $periodeList,
            'periodeId'   => $periodeId,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $periodeId = $request->periode_id;
        $periode   = Periode::findOrFail($periodeId);

        $hasil = HasilSaw::with([
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

        $pdf = Pdf::loadView('exports.hasil_saw_pdf', compact('hasil', 'periode'))
            ->setPaper('a4', 'landscape');

        return $pdf->download("hasil_saw_{$periode->bulan}_{$periode->tahun}.pdf");
    }

    public function exportExcel(Request $request)
    {
        $periodeId = $request->periode_id;
        $periode   = Periode::findOrFail($periodeId);
        return Excel::download(new HasilSawExport($periodeId), "hasil_saw_{$periode->bulan}_{$periode->tahun}.xlsx");
    }
}