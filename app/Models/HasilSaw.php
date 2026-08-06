<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasilSaw extends Model
{
    protected $table = 'hasil_saw';

    protected $fillable = [
        'nip',
        'periode_id',
        'nilai_akhir',
        'peringkat',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'nip', 'nip');
    }

    // Hapus where periode_id di sini — biarkan controller yang filter
    public function sanksi()
    {
        return $this->hasOne(Sanksi::class, 'nip', 'nip');
    }

    public function periode()
    {
        return $this->belongsTo(Periode::class, 'periode_id');
    }
}