<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/dashboard');

    Volt::route('dashboard', 'pages.admin.dashboard')->name('dashboard');
    Volt::route('kegiatan', 'pages.admin.kegiatan')->name('kegiatan');
    Volt::route('monitoring', 'pages.admin.monitoring')->name('monitoring');
    Volt::route('mahasiswa', 'pages.admin.mahasiswa')->name('mahasiswa');
    Volt::route('laporan', 'pages.admin.laporan')->name('laporan');
    Volt::route('scan-presensi', 'pages.admin.scan-presensi')->name('scan-presensi');
});
