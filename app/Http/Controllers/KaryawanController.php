<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use Illuminate\Http\Request;
use App\Models\Penilaian;
use App\Models\HasilSaw;
use App\Models\Sanksi;
use App\Models\Kriteria;
use Inertia\Inertia;

class KaryawanController extends Controller
{
    public function __construct()
    {
        // Pimpinan bisa melihat (index), tapi selain itu hanya boleh diakses Admin
        $this->middleware('admin')->only(['store', 'update', 'destroy']);
    }
    
    // 1. READ: Menampilkan halaman utama dan mengirim data karyawan
    public function index()
    {
        // Mengambil semua data karyawan, diurutkan dari yang terbaru
        $karyawan = Karyawan::latest()->get();

        // Ambil daftar divisi unik untuk filter dropdown
        $divisiList = Karyawan::whereNotNull('divisi')
            ->where('divisi', '!=', '')
            ->distinct()
            ->pluck('divisi')
            ->sort()
            ->values();
        
        return Inertia::render('Karyawan/Index', [
            'karyawan'   => $karyawan,
            'divisiList'  => $divisiList,
        ]);
    }

    // 2. CREATE: Menyimpan data karyawan baru
    public function store(Request $request)
    {
        // Mengubah integer menjadi numeric, dan menghapus spasi pada aturan 'in'
        $request->validate([
            'nip'    => 'required|numeric|unique:karyawan,nip',
            'nama'   => 'required|string|max:255',
            'divisi' => 'nullable|string|max:100',
            'status' => 'required|in:Aktif,Non-Aktif', 
        ]);

        Karyawan::create($request->all());

        return redirect()->back()->with('message', 'Data Karyawan berhasil ditambahkan!');
    }

    // 3. UPDATE: Menyimpan perubahan data karyawan
    public function update(Request $request, $id)
    {
        $karyawan = Karyawan::findOrFail($id);

        // Mengubah integer menjadi numeric, dan menghapus spasi pada aturan 'in'
        $request->validate([
            'nip'    => 'required|numeric|unique:karyawan,nip,' . $id, // Abaikan NIP milik sendiri
            'nama'   => 'required|string|max:255',
            'divisi' => 'nullable|string|max:100',
            'status' => 'required|in:Aktif,Non-Aktif', 
        ]);

        $karyawan->update($request->all());

        return redirect()->back()->with('message', 'Data Karyawan berhasil diperbarui!');
    }

    // 4. DELETE: Menghapus data karyawan
    public function destroy($id)
    {
        $karyawan = Karyawan::findOrFail($id);
        $karyawan->delete();

        return redirect()->back()->with('message', 'Data Karyawan berhasil dihapus!');
    }

    public function show($nip)
    {
        $karyawan = Karyawan::where('nip', $nip)->firstOrFail();

        // Riwayat penilaian per periode
        $penilaian = Penilaian::where('nip', $nip)
            ->with('periode')
            ->get()
            ->groupBy('periode_id')
            ->map(fn($items, $periodeId) => [
                'periode_id' => $periodeId,
                'periode'    => $items->first()->periode,
                'nilai'      => $items->pluck('nilai', 'kode_kriteria'),
            ])
            ->values();

        // Riwayat hasil SAW + sanksi
        $hasilSaw = HasilSaw::with('sanksi')
            ->where('nip', $nip)
            ->with('periode')
            ->orderBy('periode_id', 'desc')
            ->get()
            ->map(fn($h, $i) => [
                'periode_id'  => $h->periode_id,
                'periode'     => $h->periode,
                'nilai_akhir' => $h->nilai_akhir,
                'peringkat'   => $h->peringkat,
            ]);

        // Riwayat sanksi
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

        // Statistik
        $rataRata    = $hasilSaw->avg('nilai_akhir') ?? 0;
        $totalSanksi = $sanksi->filter(fn($s) => $s['keterangan'])->count();

        // Hitung tren nilai SAW
        $hasilDenganTren = $hasilSaw->map(function ($h, $i) use ($hasilSaw) {
            $prev = $hasilSaw->get($i + 1);
            $tren = null;
            if ($prev) {
                $selisih = round($h['nilai_akhir'] - $prev['nilai_akhir'], 2);
                $tren    = $selisih > 0 ? "+{$selisih}" : (string)$selisih;
            }
            return [...$h, 'tren' => $tren];
        });

        // Kriteria untuk header tabel
        $kriteria = Kriteria::all();

        return Inertia::render('Karyawan/Show', [
            'karyawan'        => $karyawan,
            'penilaian'       => $penilaian,
            'hasilSaw'        => $hasilDenganTren,
            'sanksi'          => $sanksi,
            'kriteria'        => $kriteria,
            'rataRataSaw'     => round($rataRata, 2),
            'totalSanksi'     => $totalSanksi,
            'totalPeriode'    => $hasilSaw->count(),
        ]);
    }
}