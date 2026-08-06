<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; //
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([ 
            'nama' => 'Administrator', 
            'email' => 'admin@gmail.com', 
            'password' => bcrypt('admin'),
            'role' => 'admin',
            'status' => 1,
        ]); 
        // 2. Akun Pimpinan (Hanya Lihat)
        User::create([
            'nama' => 'Pimpinan Perusahaan',
            'email' => 'pimpinan@gmail.com',
            'password' => Hash::make('pimpinan'),
            'role' => 'pimpinan',
            'status' => 1,
        ]);

    }
}