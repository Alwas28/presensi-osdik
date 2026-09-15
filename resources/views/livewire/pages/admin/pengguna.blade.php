<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.admin', ['title' => 'Pengguna'])] class extends Component
{
    public bool $showModal = false;

    #[Validate('required|string|max:255')]
    public string $nama = '';

    #[Validate('required|email|max:255|unique:users,email')]
    public string $email = '';

    #[Validate('required|string|min:8')]
    public string $password = '';

    #[Validate('required|in:panitia,presensi')]
    public string $role = 'presensi';

    #[Computed]
    public function pengguna()
    {
        return User::query()
            ->whereIn('role', ['super_admin', 'panitia', 'presensi'])
            ->get()
            ->sortBy(fn (User $user) => [
                array_search($user->role, ['super_admin', 'panitia', 'presensi'], true),
                $user->name,
            ])
            ->values();
    }

    public function openModal(): void
    {
        $this->reset(['nama', 'email', 'password']);
        $this->role = 'presensi';
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function save(): void
    {
        $this->validate();

        User::create([
            'name' => $this->nama,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => $this->role,
            'email_verified_at' => now(),
        ]);

        $this->showModal = false;
        unset($this->pengguna);
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm" style="color:var(--ink-soft);">Kelola akun panitia dan petugas presensi.</p>
        <button class="btn btn-primary" wire:click="openModal"><i class="ti ti-plus"></i>Tambah akun</button>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table>
                <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Dibuat</th></tr></thead>
                <tbody>
                    @foreach ($this->pengguna as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="pill {{ match ($user->role) { 'super_admin' => 'pill-active', 'panitia' => 'pill-done', default => 'pill-notyet' } }}">
                                    {{ match ($user->role) { 'super_admin' => 'Super Admin', 'panitia' => 'Panitia', default => 'Petugas Presensi' } }}
                                </span>
                            </td>
                            <td>{{ $user->created_at->translatedFormat('d M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($showModal)
        <div class="modal-backdrop">
            <div class="card p-6" style="width:400px; max-width:100%;">
                <h3 class="display font-bold text-base mb-4">Tambah akun</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-semibold block mb-1">Nama</label>
                        <input class="field-input" wire:model="nama" placeholder="Nama lengkap">
                        @error('nama') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold block mb-1">Email</label>
                        <input type="email" class="field-input" wire:model="email" placeholder="nama@umkendari.ac.id">
                        @error('email') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold block mb-1">Password</label>
                        <input type="password" class="field-input" wire:model="password" placeholder="Minimal 8 karakter">
                        @error('password') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold block mb-1">Role</label>
                        <select class="field-input" wire:model="role">
                            <option value="presensi">Petugas Presensi</option>
                            <option value="panitia">Panitia</option>
                        </select>
                        @error('role') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex gap-2 mt-5">
                    <button class="btn btn-ghost flex-1 justify-center" wire:click="closeModal">Batal</button>
                    <button class="btn btn-primary flex-1 justify-center" wire:click="save">Simpan</button>
                </div>
            </div>
        </div>
    @endif
</div>
