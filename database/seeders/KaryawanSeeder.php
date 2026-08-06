<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KaryawanSeeder extends Seeder
{
    public function run(): void
    {
        $data = [

            ['nip' => '12002', 'nama' => 'SYAFITRI', 'status' => 'Aktif'],
            ['nip' => '12001', 'nama' => 'RODIYAH', 'status' => 'Aktif'],
            ['nip' => '32001', 'nama' => 'KUMARA', 'status' => 'Aktif'],
            ['nip' => '42001', 'nama' => 'PONTO', 'status' => 'Aktif'],
            ['nip' => '12003', 'nama' => 'HERAWATI', 'status' => 'Aktif'],
        ];

        DB::table('karyawan')->insert($data);
    }
}