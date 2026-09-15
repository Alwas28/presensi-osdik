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
        Schema::table('events', function (Blueprint $table) {
            // Optional window (e.g. 13:00-15:00) restricting when presensi is
            // accepted for this event, on top of it being "aktif". Null means
            // no restriction beyond the aktif/ditutup status, as before.
            $table->time('jam_mulai_presensi')->nullable()->after('izinkan_presensi_manual');
            $table->time('jam_selesai_presensi')->nullable()->after('jam_mulai_presensi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['jam_mulai_presensi', 'jam_selesai_presensi']);
        });
    }
};
