<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('mahasiswa/login', 'pages.mahasiswa.login')->name('mahasiswa.login');
});

Route::middleware(['auth', 'mahasiswa'])->prefix('mahasiswa')->name('mahasiswa.')->group(function () {
    Route::redirect('/', '/mahasiswa/beranda');

    Volt::route('beranda', 'pages.mahasiswa.beranda')->name('beranda');
    Volt::route('kegiatan', 'pages.mahasiswa.kegiatan')->name('kegiatan');
    Volt::route('presensi', 'pages.mahasiswa.presensi')->name('presensi');
    Volt::route('riwayat', 'pages.mahasiswa.riwayat')->name('riwayat');
    Volt::route('profil', 'pages.mahasiswa.profil')->name('profil');
});
