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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('lokasi')->nullable();
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['draft', 'belum_dibuka', 'aktif', 'selesai', 'ditutup'])->default('draft');
            $table->string('metode_presensi')->default('qr');
            $table->string('current_token')->nullable();
            $table->timestamp('token_generated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
