<?php

use App\Models\Aduan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.mahasiswa', ['title' => 'Form Aduan'])] class extends Component
{
    #[Validate('required|string|max:255')]
    public string $judul = '';

    #[Validate('required|string|max:2000')]
    public string $pesan = '';

    public ?string $success = null;

    #[Computed]
    public function mahasiswa()
    {
        return auth()->user()->mahasiswa;
    }

    #[Computed]
    public function riwayatAduan()
    {
        return Aduan::query()
            ->where('mahasiswa_id', $this->mahasiswa->id)
            ->latest()
            ->get();
    }

    public function kirim(): void
    {
        $this->validate();

        Aduan::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'judul' => $this->judul,
            'pesan' => $this->pesan,
        ]);

        $this->reset(['judul', 'pesan']);
        $this->success = 'Aduan kamu sudah dikirim dan akan ditinjau oleh admin.';

        unset($this->riwayatAduan);
    }
}; ?>

<div class="p-5 pb-2">
    <h2 class="display font-bold text-base mb-1">Form Aduan</h2>
    <p class="text-xs mb-4" style="color:var(--ink-soft);">Sampaikan keluhan atau kendala selama Osdik. Aduan ini hanya bisa dilihat oleh admin.</p>

    @if ($success)
        <div class="p-3 rounded-lg text-xs font-medium mb-4" style="background:#e7f3ea; color:var(--umk-green);">{{ $success }}</div>
    @endif

    <div class="card p-4 mb-4">
        <div class="space-y-3">
            <div>
                <label class="text-xs font-semibold block mb-1">Judul</label>
                <input class="field-input" wire:model="judul" placeholder="Contoh: Kendala akses presensi">
                @error('judul') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold block mb-1">Isi aduan</label>
                <textarea class="field-input" rows="5" wire:model="pesan" placeholder="Jelaskan keluhan atau kendala kamu..."></textarea>
                @error('pesan') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
            </div>
        </div>
        <button class="btn btn-primary w-full justify-center mt-4" wire:click="kirim">Kirim aduan</button>
    </div>

    <p class="text-xs font-semibold mb-2" style="color:var(--ink-soft);">Riwayat aduan kamu</p>
    <div class="space-y-2">
        @forelse ($this->riwayatAduan as $aduan)
            <div class="card p-3" wire:key="aduan-{{ $aduan->id }}">
                <div class="flex items-start justify-between gap-2">
                    <p class="font-semibold text-sm">{{ $aduan->judul }}</p>
                    <span class="pill {{ $aduan->read_at ? 'pill-done' : 'pill-notyet' }}">{{ $aduan->read_at ? 'Sudah dibaca' : 'Menunggu' }}</span>
                </div>
                <p class="text-xs mt-1" style="color:var(--ink-soft);">{{ $aduan->pesan }}</p>
                <p class="text-xs mt-2" style="color:var(--ink-soft);">{{ $aduan->created_at->translatedFormat('d M Y, H:i') }}</p>
            </div>
        @empty
            <p class="text-xs" style="color:var(--ink-soft);">Belum ada aduan yang dikirim.</p>
        @endforelse
    </div>
</div>
