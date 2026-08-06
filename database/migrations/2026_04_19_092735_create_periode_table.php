<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode', function (Blueprint $table) {
            $table->id();
            $table->string('bulan'); 
            $table->year('tahun'); 
            $table->unsignedTinyInteger('hari_kerja')->default(20);
            $table->timestamps();

            // Tetap pertahankan ini untuk mencegah input bulan & tahun yang sama
            $table->unique(['bulan', 'tahun'], 'periode_unique_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode');
    }
};