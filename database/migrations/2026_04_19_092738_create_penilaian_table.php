<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penilaian', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nip'); 
            $table->foreign('nip')->references('nip')->on('karyawan')->onDelete('cascade');
            

            $table->string('kode_kriteria'); 
            $table->foreign('kode_kriteria')->references('kode')->on('kriteria')->onDelete('cascade');
            
            $table->unsignedBigInteger('periode_id');
            $table->foreign('periode_id')->references('id')->on('periode')->onDelete('cascade');
            
            $table->decimal('nilai', 8, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian');
    }
};