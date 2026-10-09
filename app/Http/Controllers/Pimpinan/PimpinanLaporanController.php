<?php

namespace App\Http\Controllers\Pimpinan;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\HasilSaw;
use App\Models\Periode;
use App\Exports\HasilSawExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class PimpinanLaporanController extends Controller
{
    public function index(Request $request)
    {
        $periodeId   = $request->periode_id;
        $periodeList = Periode::orderBy('tahun')->orderBy('bulan')->get();

        $hasil = collect();
        $divisiList = collect();

        if ($periodeId) {
            // PERBAIKAN: Menghapus filter status 'Aktif' agar karyawan resign bulan lalu tetap muncul
            $hasilRaw = HasilSaw::with(['karyawan', 'sanksi'])
                ->where('periode_id', $periodeId)
                ->orderBy('peringkat')
                ->get();

            $hasil = $hasilRaw->map(fn($h) => [
                'nip'         => $h->nip,
                'nama'        => $h->karyawan?->nama ?? $h->karyawan?->nama_karyawan ?? '-',
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


        return Inertia::render('Pimpinan/Laporan', [
            'hasil'       => $hasil,
            'periodeList' => $periodeList,
            'periodeId'   => $periodeId,
            'divisiList'  => $divisiList,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $periodeId = $request->periode_id;
        $periode   = Periode::findOrFail($periodeId);

        // PERBAIKAN: Menghapus filter status 'Aktif' agar data PDF sinkron dengan halaman hasil
        $hasil = HasilSaw::with(['karyawan', 'sanksi'])
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