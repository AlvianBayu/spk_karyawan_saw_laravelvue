<?php 

namespace App\Http\Controllers; 

use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Auth; 
use Illuminate\Validation\ValidationException;
use Inertia\Inertia; // Wajib diimport

class LoginController extends Controller 
{ 
    // Menampilkan halaman form login
    public function Showlogin() 
    { 
        return Inertia::render('Auth/Login', [ 
            'judul' => 'Login', 
        ]); 
    } 

    // Memproses data login dan pengecekan role
    public function login(Request $request) 
    {
        // 1. Validasi inputan form
        $credentials = $request->validate([ 
            'email' => 'required|email', 
            'password' => 'required' 
        ]); 

        // 2. Coba proses autentikasi
        if (!Auth::attempt($credentials)) {
            // Jika gagal, kembalikan error ke Vue
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        // 3. Jika berhasil, regenerate session untuk keamanan
        $request->session()->regenerate();

        // 4. Redirect berdasarkan role
        // Pastikan kolom 'role' benar-benar ada di tabel users database kamu
        $role = Auth::user()->role;

        if ($role === 'pimpinan') {
            return redirect()->intended('/pimpinan/dashboard');
        } elseif ($role === 'admin') {
            return redirect()->intended('/dashboard');
        } else {
            // Default tujuan jika role karyawan/user biasa
            return redirect()->intended('/dashboard');
        }
    }

    // Memproses logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Sesuaikan dengan nama route login kamu di routes/web.php
        return redirect('/login'); 
    }
}