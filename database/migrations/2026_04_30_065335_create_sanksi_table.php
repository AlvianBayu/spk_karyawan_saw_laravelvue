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
        Schema::create('sanksi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nip');
            $table->foreign('nip')->references('nip')->on('karyawan');
            $table->unsignedBigInteger('periode_id');
            $table->foreign('periode_id')->references('id')->on('periode');
            $table->integer('jumlah_alpa');
            $table->decimal('potongan_persen', 5, 2); // misal 15.00
            $table->enum('keterangan', ['Teguran'])->nullable(); // null = tidak ada sanksi
            $table->timestamps();

            $table->unique(['nip', 'periode_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sanksi');
    }
};
