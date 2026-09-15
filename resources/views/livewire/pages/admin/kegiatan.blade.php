<?php

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.admin', ['title' => 'Kelola kegiatan'])] class extends Component
{
    public bool $showModal = false;

    #[Validate('required|string|max:255')]
    public string $nama = '';

    #[Validate('required|date')]
    public string $tanggal = '';

    #[Validate('required')]
    public string $jamMulai = '';

    #[Validate('required')]
    public string $jamSelesai = '';

    #[Validate('nullable|string|max:255')]
    public string $lokasi = '';

    #[Validate('nullable|string')]
    public string $deskripsi = '';

    #[Computed]
    public function events()
    {
        return Event::query()
            ->withCount('attendances')
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get();
    }

    #[Computed]
    public function totalMahasiswa(): int
    {
        return \App\Models\Mahasiswa::query()->count();
    }

    public function openModal(): void
    {
        $this->reset(['nama', 'tanggal', 'jamMulai', 'jamSelesai', 'lokasi', 'deskripsi']);
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

        Event::create([
            'nama' => $this->nama,
            'tanggal' => $this->tanggal,
            'jam_mulai' => $this->jamMulai,
            'jam_selesai' => $this->jamSelesai,
            'lokasi' => $this->lokasi,
            'deskripsi' => $this->deskripsi,
            'status' => 'draft',
            'metode_presensi' => 'qr',
        ]);

        $this->showModal = false;
    }

    public function toggleStatus(int $eventId): void
    {
        $event = Event::findOrFail($eventId);
        $event->status = $event->status === 'aktif' ? 'ditutup' : 'aktif';
        $event->save();
    }

    public function openQr(int $eventId): void
    {
        $this->dispatch('qr-open', eventId: $eventId);
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm" style="color:var(--ink-soft);">Kelola jadwal dan status presensi tiap sesi Osdik 2026.</p>
        <button class="btn btn-primary" wire:click="openModal"><i class="ti ti-plus"></i>Tambah kegiatan</button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($this->events as $event)
            @php
                $pillClass = match ($event->status) {
                    'aktif' => 'pill-active',
                    'ditutup' => 'pill-closed',
                    'belum_dibuka' => 'pill-notyet',
                    'selesai' => 'pill-done',
                    default => 'pill-draft',
                };
                $statusLabel = str($event->status)->replace('_', ' ')->title();
                $toggleLabel = $event->status === 'aktif' ? 'Tutup presensi' : 'Buka presensi';
                $toggleIcon = $event->status === 'aktif' ? 'ti-lock' : 'ti-lock-open';
            @endphp
            <div class="card p-5" wire:key="event-{{ $event->id }}">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <p class="font-semibold text-sm mb-0.5">{{ $event->nama }}</p>
                        <p class="text-xs" style="color:var(--ink-soft);">{{ $event->tanggal->translatedFormat('d M Y') }} &middot; {{ \Illuminate\Support\Str::substr($event->jam_mulai, 0, 5) }}&ndash;{{ \Illuminate\Support\Str::substr($event->jam_selesai, 0, 5) }}</p>
                    </div>
                    <span class="pill {{ $pillClass }}">{{ $statusLabel }}</span>
                </div>
                <p class="text-xs mb-3" style="color:var(--ink-soft);"><i class="ti ti-map-pin" style="font-size:13px; vertical-align:-1px;"></i> {{ $event->lokasi ?: '-' }}</p>
                <p class="text-xs mb-4" style="color:var(--ink-soft);">{{ number_format($event->attendances_count, 0, ',', '.') }} dari {{ number_format($this->totalMahasiswa, 0, ',', '.') }} mahasiswa sudah presensi</p>
                <div class="flex flex-wrap gap-2">
                    <button class="btn btn-outline" wire:click="openQr({{ $event->id }})"><i class="ti ti-qrcode"></i>Lihat QR</button>
                    <a href="{{ route('admin.layar', ['event' => $event->id]) }}" target="_blank" class="btn btn-outline"><i class="ti ti-device-tv"></i>Tampilkan layar</a>
                    <button class="btn btn-outline" wire:click="toggleStatus({{ $event->id }})"><i class="ti {{ $toggleIcon }}"></i>{{ $toggleLabel }}</button>
                    <a href="{{ route('admin.monitoring', ['event' => $event->id]) }}" wire:navigate class="btn btn-ghost"><i class="ti ti-activity"></i>Monitoring</a>
                </div>
            </div>
        @endforeach
    </div>

    @if ($showModal)
        <div class="modal-backdrop">
            <div class="card p-6" style="width:420px; max-width:100%;">
                <h3 class="display font-bold text-base mb-4">Tambah kegiatan</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-semibold block mb-1">Nama kegiatan</label>
                        <input class="field-input" wire:model="nama" placeholder="Contoh: Pengenalan Fakultas">
                        @error('nama') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold block mb-1">Tanggal</label>
                            <input type="date" class="field-input" wire:model="tanggal">
                            @error('tanggal') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-1">Lokasi</label>
                            <input class="field-input" wire:model="lokasi" placeholder="Aula KH. Ahmad Dahlan">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold block mb-1">Jam mulai</label>
                            <input type="time" class="field-input" wire:model="jamMulai">
                            @error('jamMulai') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-1">Jam selesai</label>
                            <input type="time" class="field-input" wire:model="jamSelesai">
                            @error('jamSelesai') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-semibold block mb-1">Deskripsi (opsional)</label>
                        <input class="field-input" wire:model="deskripsi" placeholder="Deskripsi singkat kegiatan">
                    </div>
                </div>
                <div class="flex gap-2 mt-5">
                    <button class="btn btn-ghost flex-1 justify-center" wire:click="closeModal">Batal</button>
                    <button class="btn btn-primary flex-1 justify-center" wire:click="save">Simpan sebagai draf</button>
                </div>
            </div>
        </div>
    @endif

    <livewire:admin.qr-modal />
</div>
