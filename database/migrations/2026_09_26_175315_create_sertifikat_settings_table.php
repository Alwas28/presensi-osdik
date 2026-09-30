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
        Schema::create('sertifikat_settings', function (Blueprint $table) {
            $table->id();
            // Supports {urut}, {bulan_romawi}, {tahun} placeholders — see SertifikatSetting::generateNomor().
            $table->string('format')->default('{urut}/SERTIFIKAT/OSDIK/UMK/{bulan_romawi}/{tahun}');
            $table->unsignedTinyInteger('digit_urut')->default(3);
            $table->unsignedInteger('nomor_urut_terakhir')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sertifikat_settings');
    }
};
