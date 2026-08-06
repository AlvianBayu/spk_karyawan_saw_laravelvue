<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kriteria;

class KriteriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kriteria = [
            [
                'kode' => 'C1',
                'nama_kriteria' => 'Kehadiran',
                'jenis' => 'benefit',
                'bobot' => 40.00,
                'kelompok' => 'Akumulasi'
            ],
            [
                'kode' => 'C2',
                'nama_kriteria' => 'Alpa',
                'jenis' => 'cost',
                'bobot' => 30.00,
                'kelompok' => 'Akumulasi'
            ],
            [
                'kode' => 'C3',
                'nama_kriteria' => 'Izin',
                'jenis' => 'cost',
                'bobot' => 10.00,
                'kelompok' => 'Akumulasi'
            ],
            [
                'kode' => 'C4',
                'nama_kriteria' => 'Sakit',
                'jenis' => 'cost',
                'bobot' => 5.00,
                'kelompok' => 'Akumulasi'
            ],
            [
                'kode' => 'C5',
                'nama_kriteria' => 'Keterlambatan',
                'jenis' => 'cost',
                'bobot' => 15.00,
                'kelompok' => 'Independen'
            ],
        ];

        foreach ($kriteria as $item) {
            Kriteria::create($item);
        }
    }
}