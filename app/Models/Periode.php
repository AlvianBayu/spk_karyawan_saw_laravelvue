<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Periode extends Model
{
    protected $table = 'periode';
    
    protected $fillable = [
        'bulan',
        'tahun',
        'hari_kerja', // ← pastikan ini ada
    ];

    // List bulan yang dipakai controller
    public static $bulanList = [
        'Januari', 'Februari', 'Maret', 'April',
        'Mei', 'Juni', 'Juli', 'Agustus',
        'September', 'Oktober', 'November', 'Desember'
    ];

    // Default hari kerja
    public static $hariKerjaDefault = 20;

    public function penilaian()
    {
        return $this->hasMany(Penilaian::class, 'periode_id', 'id');
    }

    public function hasilSaw()
    {
        return $this->hasMany(HasilSaw::class, 'periode_id', 'id');
    }
}