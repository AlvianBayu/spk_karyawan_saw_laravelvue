<?php

namespace App\Services;

use App\Models\Penilaian;
use App\Models\Kriteria;
use App\Models\Karyawan;
use App\Models\HasilSaw;
use App\Models\Sanksi;
use App\Models\Periode;
use App\Helpers\SanksiHelper;
use Illuminate\Support\Facades\DB;

class SawService
{
    public function calculate($periodeId)
    {
        $kriteria = Kriteria::all();
        
        // PERBAIKAN: Gunakan whereHas agar karyawan yang sudah tidak aktif 
        // tapi punya data di bulan lalu tetap terhitung.
        $karyawan = Karyawan::whereHas('penilaian', fn($q) => $q->where('periode_id', $periodeId))
            ->with(['penilaian' => fn($q) => $q->where('periode_id', $periodeId)])
            ->get();

        $rawData      = collect();
        $normData     = collect();
        $weightedData = collect();
        $optimum      = [];

        $matriks = [];
        foreach ($karyawan as $kar) {
            foreach ($kriteria as $k) {
                $p = $kar->penilaian->firstWhere('kode_kriteria', $k->kode);
                $matriks[$kar->nip][$k->kode] = $p ? (int)$p->nilai : 0;
            }
            $rawData->push([
                'nip'     => $kar->nip,
                'nama'    => $kar->nama ?? $kar->nama_karyawan,
                'jabatan' => $kar->jabatan,
                'nilai'   => $matriks[$kar->nip],
            ]);
        }

        foreach ($kriteria as $k) {
            $vals = array_map(fn($m) => $m[$k->kode] ?? 0, $matriks);
            $optimum[$k->kode] = [
                'max'   => max($vals),
                'min'   => min($vals),
                'jenis' => $k->jenis,
            ];
        }

        $hariKerja = Periode::where('id', $periodeId)->value('hari_kerja') ?? 20;

        foreach ($rawData as $row) {
            $norm = [];
            foreach ($kriteria as $k) {
                $v = $row['nilai'][$k->kode];

                if (strtolower($k->jenis) === 'benefit') {
                    $norm[$k->kode] = $hariKerja > 0 ? round($v / $hariKerja, 4) : 0;
                } else {
                    $norm[$k->kode] = $hariKerja > 0 ? round(1 - ($v / $hariKerja), 4) : 1;
                }
            }
            $normData->push([...$row, 'norm' => $norm]);
        }

        $temp = [];
        foreach ($normData as $row) {
            $nilaiAkhir = 0;
            $weighted   = [];
            foreach ($kriteria as $k) {
                $bobot = $k->bobot > 1 ? $k->bobot / 100 : $k->bobot;
                $w     = round($row['norm'][$k->kode] * $bobot, 4);
                $weighted[$k->kode] = $w;
                $nilaiAkhir += $w;
            }
            $temp[] = [
                ...$row,
                'weighted'    => $weighted,
                'nilai_akhir' => round($nilaiAkhir, 4),
            ];
        }

        usort($temp, fn($a, $b) => $b['nilai_akhir'] <=> $a['nilai_akhir']);
        foreach ($temp as $i => $row) {
            $weightedData->push([...$row, 'peringkat' => $i + 1]);
        }

        return [
            'rawData'      => $rawData,
            'normData'     => $normData,
            'weightedData' => $weightedData,
            'optimum'      => $optimum,
        ];
    }

    public function storeOrUpdate($periodeId, $weightedData)
    {
        return DB::transaction(function () use ($periodeId, $weightedData) {
            HasilSaw::where('periode_id', $periodeId)->delete();
            Sanksi::where('periode_id', $periodeId)->delete();

            // CATATAN PENTING: Pastikan kode 'C2' ini benar-benar mewakili kriteria ALPA di databasemu.
            $kodeAlpa = 'C2';

            foreach ($weightedData as $row) {
                HasilSaw::create([
                    'nip'         => $row['nip'],
                    'periode_id'  => $periodeId,
                    'nilai_akhir' => $row['nilai_akhir'],
                    'peringkat'   => $row['peringkat'],
                ]);

                // PERBAIKAN: Ambil langsung dari array memory, tidak perlu select DB lagi.
                $jumlahAlpa = $row['nilai'][$kodeAlpa] ?? 0;

                $dataSanksi = SanksiHelper::hitung($jumlahAlpa);

                Sanksi::create([
                    'nip'             => $row['nip'],
                    'periode_id'      => $periodeId,
                    'jumlah_alpa'     => $dataSanksi['jumlah_alpa'],
                    'potongan_persen' => $dataSanksi['potongan_persen'],
                    'keterangan'      => $dataSanksi['keterangan'],
                ]);
            }
            return true;
        });
    }

    public function hitungUlang($periodeId)
    {
        $result = $this->calculate($periodeId);
        $this->storeOrUpdate($periodeId, $result['weightedData']);
    }
}