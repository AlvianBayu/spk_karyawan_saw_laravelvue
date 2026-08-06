<?php

namespace App\Http\Controllers\Pimpinan;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Models\Karyawan;
use App\Models\Periode;
use App\Models\Penilaian;
use App\Models\HasilSaw;
use App\Models\Kriteria;

class PimpinanDashboardController extends Controller
{
    public function index()
    {
        $periode = Periode::orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->first();

        $periodeId = $periode?->id;

        $totalKaryawan  = Karyawan::where('status', 'Aktif')->count();
        $totalPeriode        = Periode::whereHas('penilaian')->count();
        $rataRataAktif       = round(HasilSaw::where('periode_id', $periodeId)->avg('nilai_akhir') ?? 0, 2);
        $rataRataKeseluruhan = round(HasilSaw::avg('nilai_akhir') ?? 0, 2);

        $hasil = collect();
        if ($periodeId) {
            $hasil = HasilSaw::with(['karyawan', 'sanksi'])
                ->where('periode_id', $periodeId)
                ->orderBy('peringkat')
                ->get()
                ->map(fn($h) => [
                    'nip'         => $h->nip,
                    'nama'        => $h->karyawan?->nama ?? $h->karyawan?->nama_karyawan ?? '-',
                    'jabatan'     => $h->karyawan?->jabatan ?? '-',
                    'nilai_akhir' => $h->nilai_akhir,
                    'peringkat'   => $h->peringkat,
                    'sanksi'      => $h->sanksi?->keterangan,
                    'potongan'    => $h->sanksi?->potongan_persen ?? 0,
                ]);
        }

        $rankingKeseluruhan = HasilSaw::selectRaw('nip, AVG(nilai_akhir) as avg_nilai, COUNT(periode_id) as total_periode')
            ->groupBy('nip')
            ->orderByDesc('avg_nilai')
            ->with('karyawan')
            ->get()
            ->map(fn($h) => [
                'nip'           => $h->nip,
                'nama'          => $h->karyawan?->nama ?? $h->karyawan?->nama_karyawan ?? '-',
                'jabatan'       => $h->karyawan?->jabatan ?? '-',
                'avg_nilai'     => round($h->avg_nilai, 2),
                'total_periode' => $h->total_periode,
            ])->values();

        $trenPeriode = Periode::orderBy('tahun')->orderBy('bulan')
            ->whereHas('hasilSaw')
            ->with(['hasilSaw' => fn($q) => $q->select('periode_id', 'nilai_akhir')])
            ->get()
            ->map(fn($p) => [
                'label' => "{$p->bulan} {$p->tahun}",
                'avg'   => round($p->hasilSaw->avg('nilai_akhir') ?? 0, 2),
            ]);

        $kriteria = Kriteria::all()->map(fn($k) => [
            'kode'          => $k->kode,
            'nama_kriteria' => $k->nama_kriteria,
            'jenis'         => $k->jenis,
            'bobot'         => $k->bobot > 1 ? $k->bobot : $k->bobot * 100,
        ]);

        return Inertia::render('Pimpinan/Dashboard', [
            'periode'             => $periode,
            'totalKaryawan'       => $totalKaryawan,
            'totalPeriode'        => $totalPeriode,
            'rataRataAktif'       => $rataRataAktif,
            'rataRataKeseluruhan' => $rataRataKeseluruhan,
            'hasil'               => $hasil,
            'top3'                => $hasil->take(3)->values(),
            'rankingKeseluruhan'  => $rankingKeseluruhan,
            'trenPeriode'         => $trenPeriode,
            'kriteria'            => $kriteria,
        ]);
    }
}