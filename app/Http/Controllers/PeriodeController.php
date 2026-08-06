<?php

namespace App\Http\Controllers;

use App\Models\Periode;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PeriodeController extends Controller
{
    public function __construct()
    {
        // Pimpinan bisa melihat (index), tapi selain itu hanya boleh diakses Admin
        $this->middleware('admin')->only(['store', 'update', 'destroy']);
    }
    
    public function index()
{
    // Menggunakan get(), BUKAN paginate()
    $periodes = Periode::orderBy('tahun', 'desc')
        ->orderByRaw("FIELD(bulan, 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember') DESC")
        ->get();

    return Inertia::render('Periode/Index', [
        'periodes' => $periodes,
        'bulanList' => Periode::$bulanList,
        'hariKerjaDefault' => Periode::$hariKerjaDefault,
    ]);
}

    public function store(Request $request)
    {
        $request->validate([
            'bulan' => 'required|string|in:' . implode(',', Periode::$bulanList),
            'tahun' => 'required|digits:4|integer|min:2000|max:2099',
            'hari_kerja' => 'required|integer|min:1|max:31',
        ], [
            'bulan.required' => 'Bulan wajib dipilih.',
            'bulan.in' => 'Bulan tidak valid.',
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.digits' => 'Tahun harus 4 digit.',
        ]);

        $exists = Periode::where('bulan', $request->bulan)
            ->where('tahun', $request->tahun)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'bulan' => 'Periode ' . $request->bulan . ' ' . $request->tahun . ' sudah ada.',
            ]);
        }

        Periode::create($request->only(['bulan', 'tahun', 'hari_kerja']));

        return back()->with('success', 'Periode berhasil ditambahkan.');
    }

    public function update(Request $request, Periode $periode)
    {
        $request->validate([
            'bulan'      => 'required|string|in:' . implode(',', Periode::$bulanList),
            'tahun'      => 'required|digits:4|integer|min:2000|max:2099',
            'hari_kerja' => 'required|integer|min:1|max:31', // ← tambahkan ini
        ]);

        $exists = Periode::where('bulan', $request->bulan)
            ->where('tahun', $request->tahun)
            ->where('id', '!=', $periode->id)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'bulan' => 'Periode ' . $request->bulan . ' ' . $request->tahun . ' sudah ada.',
            ]);
        }

        $periode->update($request->only(['bulan', 'tahun', 'hari_kerja']));

        return back()->with('success', 'Periode berhasil diperbarui.');
    }

    public function destroy(Periode $periode)
    {
        $periode->delete();
        return back()->with('success', 'Periode berhasil dihapus.');
    }
}