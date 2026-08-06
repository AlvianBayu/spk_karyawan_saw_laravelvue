<?php

namespace App\Http\Controllers;

use App\Models\Kriteria;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KriteriaController extends Controller
{
    public function __construct()
    {
        // Pimpinan bisa melihat (index), tapi selain itu hanya boleh diakses Admin
        $this->middleware('admin')->only(['store', 'update', 'destroy']);
    }
    // Menampilkan halaman utama (Read)
    public function index()
    {
        $kriteria = Kriteria::orderBy('id', 'asc')->get();
        return Inertia::render('Kriteria/Index', [
            'kriteria' => $kriteria
        ]);
    }

// Fungsi Tambah Data Baru (Create)
    public function store(Request $request)
    {
        $request->validate([
            'kode' => 'required|string|max:10|unique:kriteria,kode', // Validasi kode baru
            'nama_kriteria' => 'required|string|max:255',
            'jenis' => 'required|in:Benefit,Cost',
            'bobot' => 'required|numeric|min:0|max:100',
            'kelompok'      => 'required|in:akumulasi,independen',
        ]);

        Kriteria::create($request->all());

        return redirect()->back()->with('success', 'Kriteria baru berhasil ditambahkan.');
    }

    // Fungsi Update Data (Update)
    public function update(Request $request, $id)
    {
        $request->validate([
            // Validasi update: kode boleh sama asalkan milik ID yang sedang diedit
            'kode' => 'required|string|max:10|unique:kriteria,kode,' . $id, 
            'nama_kriteria' => 'required|string|max:255',
            'jenis' => 'required|in:Benefit,Cost',
            'bobot' => 'required|numeric|min:0|max:100',
            'kelompok' => 'required|in:akumulasi,independen',
        ]);

        $kriteria = Kriteria::findOrFail($id);
        $kriteria->update($request->all());

        return redirect()->back()->with('success', 'Data kriteria berhasil diperbarui.');
    }

    // Fungsi Hapus Data (Delete)
    public function destroy($id)
    {
        $kriteria = Kriteria::findOrFail($id);
        $kriteria->delete();

        return redirect()->back()->with('success', 'Kriteria berhasil dihapus.');
    }
}