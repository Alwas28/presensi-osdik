<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.mahasiswa-guest', ['title' => 'Masuk'])] class extends Component
{
    public string $nim = '';

    public string $password = '';

    public function login(): void
    {
        $this->validate([
            'nim' => 'required|string',
            'password' => 'required|string',
        ]);

        $this->ensureIsNotRateLimited();

        $email = trim($this->nim).'@mahasiswa.pkkmb.local';

        if (! Auth::attempt(['email' => $email, 'password' => $this->password])) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'nim' => 'NIM atau password salah.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $this->redirect(route('mahasiswa.beranda'), navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'nim' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->nim).'|'.request()->ip());
    }
}; ?>

<div>
    <h2 class="display font-bold text-base mb-1 text-center">Masuk sebagai Mahasiswa</h2>
    <p class="text-xs text-center mb-5" style="color:var(--ink-soft);">Gunakan NIM dan password yang diberikan panitia.</p>

    <form wire:submit="login" class="space-y-3">
        <div>
            <label class="text-xs font-semibold block mb-1">NIM</label>
            <input type="text" class="field-input" wire:model="nim" autofocus placeholder="Contoh: 22661016" inputmode="numeric">
            @error('nim') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold block mb-1">Password</label>
            <input type="password" class="field-input" wire:model="password" placeholder="Default: sama dengan NIM">
            @error('password') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn btn-primary w-full justify-center mt-2">Masuk</button>
    </form>

    <p class="text-xs text-center mt-5" style="color:var(--ink-soft);">
        Panitia? <a href="{{ route('login') }}" wire:navigate class="font-semibold" style="color:var(--umk-green);">Masuk di sini</a>
    </p>
</div>
