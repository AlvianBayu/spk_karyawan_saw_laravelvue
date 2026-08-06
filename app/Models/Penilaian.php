<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penilaian extends Model
{
    protected $table = 'penilaian';

    protected $fillable = [
        'nip',
        'kode_kriteria',
        'periode_id',
        'nilai',
    ];

    // Relasi ke Karyawan
        public function karyawan()
    {
        // Tambahkan ->withTrashed() sebelum semicolon (titik koma)
        return $this->belongsTo(Karyawan::class, 'nip', 'nip')->withTrashed();
    }


    // Relasi ke Kriteria
    public function kriteria()
    {
        return $this->belongsTo(Kriteria::class, 'kode_kriteria', 'kode');
    }

    // Relasi ke Periode
    public function periode()
    {
        return $this->belongsTo(Periode::class, 'periode_id', 'id');
    }


}