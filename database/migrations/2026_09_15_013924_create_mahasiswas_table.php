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
        Schema::create('mahasiswas', function (Blueprint $table) {
            $table->id();
            $table->string('no_registrasi')->nullable()->unique();
            $table->string('nim')->unique();
            $table->string('nama');
            $table->string('no_telp')->nullable();
            $table->string('status_pendaftar')->nullable();
            $table->string('jalur')->nullable();
            $table->foreignId('program_studi_id')->constrained('program_studis')->cascadeOnDelete();
            $table->unsignedSmallInteger('angkatan')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mahasiswas');
    }
};
