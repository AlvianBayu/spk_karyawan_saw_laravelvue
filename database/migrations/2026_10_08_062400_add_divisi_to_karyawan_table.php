<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('karyawan', 'divisi')) {
            Schema::table('karyawan', function (Blueprint $table) {
                $table->string('divisi')->nullable()->after('nama');
            });
        }
    }

    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->dropColumn('divisi');
        });
    }
};
