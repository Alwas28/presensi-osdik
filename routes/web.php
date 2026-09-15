<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    if ($request->user()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::get('dashboard', function (Request $request) {
    if ($request->user()->isPresensi()) {
        return redirect()->route('admin.scan-presensi');
    }

    if ($request->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    if ($request->user()->isMahasiswa()) {
        return redirect()->route('mahasiswa.beranda');
    }

    return view('dashboard');
})
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/mahasiswa.php';
