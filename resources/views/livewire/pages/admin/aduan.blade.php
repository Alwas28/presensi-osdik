<?php

use App\Models\Aduan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin', ['title' => 'Aduan Mahasiswa'])] class extends Component
{
    #[Computed]
    public function aduans()
    {
        return Aduan::query()
            ->with('mahasiswa.programStudi')
            ->latest()
            ->get();
    }

    public function tandaiDibaca(int $aduanId): void
    {
        Aduan::query()->whereKey($aduanId)->update(['read_at' => now()]);

        unset($this->aduans);
    }
}; ?>

<div>
    <p class="text-sm mb-4" style="color:var(--ink-soft);">Aduan yang dikirim mahasiswa lewat aplikasi. Halaman ini hanya bisa diakses super admin.</p>

    <div class="space-y-3">
        @forelse ($this->aduans as $aduan)
            <div class="card p-4" wire:key="aduan-{{ $aduan->id }}">
                <div class="flex items-start justify-between gap-3 mb-1">
                    <div>
                        <p class="font-semibold text-sm">{{ $aduan->judul }}</p>
                        <p class="text-xs" style="color:var(--ink-soft);">{{ $aduan->mahasiswa->nama }} &middot; {{ $aduan->mahasiswa->nim }} &middot; {{ $aduan->mahasiswa->programStudi->name ?? '-' }}</p>
                    </div>
                    <span class="pill {{ $aduan->read_at ? 'pill-done' : 'pill-notyet' }}">{{ $aduan->read_at ? 'Sudah dibaca' : 'Baru' }}</span>
                </div>
                <p class="text-sm mt-2">{{ $aduan->pesan }}</p>
                <div class="flex items-center justify-between mt-3">
                    <p class="text-xs" style="color:var(--ink-soft);">{{ $aduan->created_at->translatedFormat('d M Y, H:i') }}</p>
                    @unless ($aduan->read_at)
                        <button class="btn btn-ghost" wire:click="tandaiDibaca({{ $aduan->id }})"><i class="ti ti-check"></i>Tandai sudah dibaca</button>
                    @endunless
                </div>
            </div>
        @empty
            <div class="card p-6 text-center">
                <p class="text-sm" style="color:var(--ink-soft);">Belum ada aduan dari mahasiswa.</p>
            </div>
        @endforelse
    </div>
</div>
