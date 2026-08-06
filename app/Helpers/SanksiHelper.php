<?php

namespace App\Helpers;

class SanksiHelper
{
    const POTONGAN_PER_HARI = 3;
    const MAKS_POTONGAN     = 100;
    const MIN_ALPA_TEGURAN  = 3;

    public static function hitung(int $jumlahAlpa): array
    {
        $potongan   = min($jumlahAlpa * self::POTONGAN_PER_HARI, self::MAKS_POTONGAN);
        $keterangan = $jumlahAlpa >= self::MIN_ALPA_TEGURAN ? 'Teguran' : null;

        return [
            'jumlah_alpa'     => $jumlahAlpa,
            'potongan_persen' => $potongan,
            'keterangan'      => $keterangan,
        ];
    }
}