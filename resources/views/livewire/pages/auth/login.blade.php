<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.login', ['title' => 'Masuk'])] class extends Component
{
    public string $identifier = '';

    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request. Mahasiswa sign in with their
     * NIM, panitia/admin with their email — both share this one form.
     */
    public function login(): void
    {
        $this->validate([
            'identifier' => 'required|string',
            'password' => 'required|string',
        ]);

        $this->ensureIsNotRateLimited();

        $email = str_contains($this->identifier, '@')
            ? trim($this->identifier)
            : trim($this->identifier).'@mahasiswa.osdik.local';

        if (! Auth::attempt(['email' => $email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'identifier' => 'NIM/Email atau password salah.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'identifier' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->identifier).'|'.request()->ip());
    }
}; ?>

<div>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <h2 class="display font-bold text-base mb-1 text-center">Masuk</h2>
    <p class="text-xs text-center mb-5" style="color:var(--ink-soft);">Mahasiswa pakai NIM, panitia/admin pakai email.</p>

    <form wire:submit="login" class="space-y-3">
        <div>
            <label class="text-xs font-semibold block mb-1">NIM / Email</label>
            <input type="text" class="field-input" wire:model="identifier" autofocus autocomplete="username" placeholder="NIM atau email">
            @error('identifier') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold block mb-1">Password</label>
            <input type="password" class="field-input" wire:model="password" autocomplete="current-password" placeholder="Password">
            @error('password') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-xs" style="color:var(--ink-soft);">
                <input type="checkbox" wire:model="remember">
                Ingat saya
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" wire:navigate class="text-xs font-semibold" style="color:var(--umk-green);">Lupa password?</a>
            @endif
        </div>
        <button type="submit" class="btn btn-primary w-full justify-center mt-2">Masuk</button>
    </form>
</div>
