<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.mahasiswa', ['title' => 'Profil'])] class extends Component
{
    #[Computed]
    public function mahasiswa()
    {
        return auth()->user()->mahasiswa()->with('programStudi.fakultas')->first();
    }
}; ?>

<div class="p-5 pb-2">
    <div class="flex flex-col items-center text-center py-4 mb-4">
        <div class="flex items-center justify-center rounded-full mb-3" style="width:64px;height:64px;background:#eef6f0;color:var(--umk-green);font-weight:700;font-size:20px;">
            {{ collect(explode(' ', $this->mahasiswa->nama))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
        </div>
        <p class="display font-bold text-base">{{ $this->mahasiswa->nama }}</p>
        <p class="text-xs" style="color:var(--ink-soft);">{{ $this->mahasiswa->nim }}</p>
    </div>

    <div class="card p-4 mb-4">
        <div class="flex justify-between py-2 text-sm" style="border-bottom:1px solid var(--line);">
            <span style="color:var(--ink-soft);">Program studi</span>
            <span class="font-medium text-right">{{ $this->mahasiswa->programStudi->name ?? '-' }}</span>
        </div>
        <div class="flex justify-between py-2 text-sm" style="border-bottom:1px solid var(--line);">
            <span style="color:var(--ink-soft);">Fakultas</span>
            <span class="font-medium text-right">{{ $this->mahasiswa->programStudi->fakultas->name ?? '-' }}</span>
        </div>
        <div class="flex justify-between py-2 text-sm">
            <span style="color:var(--ink-soft);">Jalur pendaftaran</span>
            <span class="font-medium text-right">{{ $this->mahasiswa->jalur ?: '-' }}</span>
        </div>
    </div>

    <livewire:mahasiswa.logout-button />
</div>
