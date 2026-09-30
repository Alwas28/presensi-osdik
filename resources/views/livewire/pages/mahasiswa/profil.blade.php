<?php

use App\Models\Sertifikat;
use App\Models\SertifikatSetting;
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

    public ?string $sertifikatError = null;

    #[Computed]
    public function sertifikat(): ?Sertifikat
    {
        return $this->mahasiswa->sertifikat;
    }

    public function generateSertifikat(): void
    {
        if ($this->sertifikat) {
            return;
        }

        $this->sertifikatError = null;

        // Only students who actually attended at least one kegiatan may claim
        // a certificate of participation.
        if (! $this->mahasiswa->attendances()->exists()) {
            $this->sertifikatError = 'Kamu tidak diizinkan generate sertifikat karena tidak mengikuti kegiatan Osdik.';

            return;
        }

        Sertifikat::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'nomor' => SertifikatSetting::current()->generateNomor(),
        ]);

        unset($this->sertifikat);
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

    <a href="{{ route('mahasiswa.aduan') }}" wire:navigate class="card p-4 mb-4 flex items-center justify-between">
        <span class="flex items-center gap-2.5 text-sm font-medium">
            <i class="ti ti-message-report" style="font-size:18px; color:var(--umk-green);"></i>
            Form Aduan
        </span>
        <i class="ti ti-chevron-right" style="color:var(--ink-soft);"></i>
    </a>

    <div class="card p-4 mb-4" x-data="{ open: false }">
        <div class="flex items-center gap-2.5 mb-1">
            <i class="ti ti-mail" style="font-size:18px; color:var(--umk-green);"></i>
            <span class="text-sm font-semibold">Email Kampus</span>
        </div>
        @if ($this->mahasiswa->email && $this->mahasiswa->email_password)
            <p class="text-xs mb-3" style="color:var(--ink-soft);">Lihat akun email resmi yang diberikan kampus untuk kamu.</p>
            <button type="button" class="btn btn-primary w-full justify-center" @click="open = true">
                <i class="ti ti-mail"></i>Lihat Akun Email Kampus
            </button>

            <div x-show="open" x-cloak class="modal-backdrop" @click.self="open = false">
                <div class="card p-6" style="width:380px; max-width:100%;">
                    <h3 class="display font-bold text-base mb-1">Akun Email Kampus</h3>
                    <p class="text-xs mb-4" style="color:var(--ink-soft);">Ini akun email resmi yang diberikan kampus untuk kamu.</p>
                    <div class="space-y-3 mb-4">
                        <div>
                            <label class="text-xs font-semibold block mb-1">Email</label>
                            <div class="field-input" style="background:var(--paper); user-select:all;">{{ $this->mahasiswa->email }}</div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-1">Password</label>
                            <div class="field-input" style="background:var(--paper); user-select:all;">{{ $this->mahasiswa->email_password }}</div>
                        </div>
                    </div>
                    <div class="p-3 rounded-lg text-xs font-medium mb-4" style="background:#fdf3d8; color:#8a6a00;">
                        <i class="ti ti-camera"></i> Screenshot halaman ini sekarang untuk menyimpan akun email kamu. Password ini tidak ditampilkan ulang di tempat lain.
                    </div>
                    <button type="button" class="btn btn-ghost w-full justify-center" @click="open = false">Tutup</button>
                </div>
            </div>
        @else
            <p class="text-xs" style="color:var(--ink-soft);">Akun email kampus belum tersedia untuk NIM kamu.</p>
        @endif
    </div>

    <div class="card p-4 mb-4">
        <div class="flex items-center gap-2.5 mb-1">
            <i class="ti ti-certificate" style="font-size:18px; color:var(--umk-green);"></i>
            <span class="text-sm font-semibold">Sertifikat Osdik</span>
        </div>
        @if ($this->sertifikat)
            <p class="text-xs mb-3" style="color:var(--ink-soft);">Nomor: {{ $this->sertifikat->nomor }}</p>
            <a href="{{ route('mahasiswa.sertifikat.download') }}" class="btn btn-primary w-full justify-center">
                <i class="ti ti-download"></i>Download Sertifikat
            </a>
        @else
            <p class="text-xs mb-3" style="color:var(--ink-soft);">Buat sertifikat kepesertaan Osdik kamu.</p>
            @if ($sertifikatError)
                <div class="p-3 rounded-lg text-xs font-medium mb-3" style="background:#fdeaea; color:var(--umk-red);">{{ $sertifikatError }}</div>
            @endif
            <button type="button" class="btn btn-primary w-full justify-center" wire:click="generateSertifikat">
                <i class="ti ti-certificate"></i>Generate Sertifikat
            </button>
        @endif
    </div>

    <livewire:mahasiswa.logout-button />
</div>
