<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) { 
            $table->id(); 
            $table->string('nama'); 
            $table->string('email')->unique(); 
            $table->enum('role', ['admin', 'pimpinan'])->default('admin'); //  0 = Admin, 1 = SuperAdmin,
            $table->string('password'); 
            $table->boolean('status'); //  0 = Belum aktif, 1=Aktif 
            $table->timestamps(); 
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};
