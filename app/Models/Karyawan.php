<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Karyawan extends Model
{
    use HasFactory, SoftDeletes; // <--- 2. TAMBAHKAN TRAIT INI

    protected $table = 'karyawan';
    
    protected $fillable = ['nip', 'nama', 'status'];

    public function penilaian()
    {
        return $this->hasMany(Penilaian::class, 'nip', 'nip');
    }
}