<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified', 'staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function (Request $request) {
        return $request->user()->isPresensi()
            ? redirect()->route('admin.scan-presensi')
            : redirect()->route('admin.dashboard');
    });

    // Accessible to every staff role (super_admin, panitia, presensi) — the
    // "presensi" role's entire job is scanning/showing QR codes at the venue.
    Volt::route('scan-presensi', 'pages.admin.scan-presensi')->name('scan-presensi');
    Volt::route('layar', 'pages.admin.layar')->name('layar');

    Route::middleware('admin')->group(function () {
        Volt::route('dashboard', 'pages.admin.dashboard')->name('dashboard');
        Volt::route('kegiatan', 'pages.admin.kegiatan')->name('kegiatan');
        Volt::route('monitoring', 'pages.admin.monitoring')->name('monitoring');
        Volt::route('mahasiswa', 'pages.admin.mahasiswa')->name('mahasiswa');
        Volt::route('laporan', 'pages.admin.laporan')->name('laporan');
    });

    Route::middleware('super_admin')->group(function () {
        Volt::route('pengguna', 'pages.admin.pengguna')->name('pengguna');
        Volt::route('aduan', 'pages.admin.aduan')->name('aduan');
    });
});
