<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hasil_saw', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nip');
            $table->foreign('nip')->references('nip')->on('karyawan');
            $table->unsignedBigInteger('periode_id');
            $table->foreign('periode_id')->references('id')->on('periode');
            $table->decimal('nilai_akhir', 10, 4);  // misal 0.8750
            $table->integer('peringkat');
            $table->timestamps();

            // 1 karyawan hanya punya 1 hasil per periode
            $table->unique(['nip', 'periode_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_saw');
    }
};