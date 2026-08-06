<?php

namespace App\Http\Controllers\Pimpinan;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Models\Karyawan;
use App\Models\Penilaian;
use App\Models\HasilSaw;
use App\Models\Sanksi;
use App\Models\Kriteria;

class PimpinanKaryawanController extends Controller
{
    public function index()
    {
        $karyawan = Karyawan::all()->map(fn($k) => [
            'nip'     => $k->nip,
            'nama'    => $k->nama ?? $k->nama_karyawan ?? '-',
            'jabatan' => $k->jabatan ?? '-',
        ]);

        return Inertia::render('Pimpinan/Karyawan', [
            'karyawan' => $karyawan,
        ]);
    }

    public function show($nip)
    {
        $karyawan = Karyawan::where('nip', $nip)->firstOrFail();
        $kriteria = Kriteria::all();

        $penilaian = Penilaian::where('nip', $nip)
            ->with('periode')
            ->get()
            ->groupBy('periode_id')
            ->map(fn($items) => [
                'periode_id' => $items->first()->periode_id,
                'periode'    => $items->first()->periode,
                'nilai'      => $items->pluck('nilai', 'kode_kriteria'),
            ])->values();

        $hasilSaw = HasilSaw::with(['sanksi', 'periode'])
            ->where('nip', $nip)
            ->orderBy('periode_id', 'desc')
            ->get()
            ->map(function ($h, $i) use ($nip) {
                return [
                    'periode_id'  => $h->periode_id,
                    'periode'     => $h->periode,
                    'nilai_akhir' => $h->nilai_akhir,
                    'peringkat'   => $h->peringkat,
                    'tren'        => null,
                ];
            });

        // Hitung tren
        $hasilDenganTren = $hasilSaw->map(function ($h, $i) use ($hasilSaw) {
            $prev    = $hasilSaw->get($i + 1);
            $tren    = null;
            if ($prev) {
                $selisih = round($h['nilai_akhir'] - $prev['nilai_akhir'], 2);
                $tren    = $selisih > 0 ? "+{$selisih}" : (string)$selisih;
            }
            return [...$h, 'tren' => $tren];
        });

        $sanksi = Sanksi::where('nip', $nip)
            ->with('periode')
            ->orderBy('periode_id', 'desc')
            ->get()
            ->map(fn($s) => [
                'periode_id'      => $s->periode_id,
                'periode'         => $s->periode,
                'jumlah_alpa'     => $s->jumlah_alpa,
                'keterangan'      => $s->keterangan,
                'potongan_persen' => $s->potongan_persen,
            ]);

        return Inertia::render('Pimpinan/KaryawanDetail', [
            'karyawan'     => $karyawan,
            'penilaian'    => $penilaian,
            'hasilSaw'     => $hasilDenganTren,
            'sanksi'       => $sanksi,
            'kriteria'     => $kriteria,
            'rataRataSaw'  => round($hasilSaw->avg('nilai_akhir') ?? 0, 2),
            'totalSanksi'  => $sanksi->filter(fn($s) => $s['keterangan'])->count(),
            'totalPeriode' => $hasilSaw->count(),
        ]);
    }
}