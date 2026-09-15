<?php

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.admin', ['title' => 'Kelola kegiatan'])] class extends Component
{
    public bool $showModal = false;

    public ?int $editingEventId = null;

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

    #[Validate('nullable')]
    public string $jamMulaiPresensi = '';

    #[Validate('nullable')]
    public string $jamSelesaiPresensi = '';

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
        $this->editingEventId = null;
        $this->reset(['nama', 'tanggal', 'jamMulai', 'jamSelesai', 'lokasi', 'deskripsi', 'jamMulaiPresensi', 'jamSelesaiPresensi']);
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function openEditModal(int $eventId): void
    {
        $event = Event::findOrFail($eventId);

        $this->editingEventId = $event->id;
        $this->nama = $event->nama;
        $this->tanggal = $event->tanggal->toDateString();
        $this->jamMulai = substr($event->jam_mulai, 0, 5);
        $this->jamSelesai = substr($event->jam_selesai, 0, 5);
        $this->lokasi = $event->lokasi ?? '';
        $this->deskripsi = $event->deskripsi ?? '';
        $this->jamMulaiPresensi = $event->jam_mulai_presensi ? substr($event->jam_mulai_presensi, 0, 5) : '';
        $this->jamSelesaiPresensi = $event->jam_selesai_presensi ? substr($event->jam_selesai_presensi, 0, 5) : '';
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

        if ($this->jamMulaiPresensi !== '' && $this->jamSelesaiPresensi !== '' && $this->jamSelesaiPresensi <= $this->jamMulaiPresensi) {
            $this->addError('jamSelesaiPresensi', 'Jam selesai presensi harus setelah jam mulai presensi.');

            return;
        }

        $data = [
            'nama' => $this->nama,
            'tanggal' => $this->tanggal,
            'jam_mulai' => $this->jamMulai,
            'jam_selesai' => $this->jamSelesai,
            'lokasi' => $this->lokasi,
            'deskripsi' => $this->deskripsi,
            'jam_mulai_presensi' => $this->jamMulaiPresensi ?: null,
            'jam_selesai_presensi' => $this->jamSelesaiPresensi ?: null,
        ];

        if ($this->editingEventId) {
            Event::whereKey($this->editingEventId)->update($data);
        } else {
            Event::create($data + ['status' => 'draft', 'metode_presensi' => 'qr']);
        }

        $this->showModal = false;
        unset($this->events);
    }

    public function toggleStatus(int $eventId): void
    {
        $event = Event::findOrFail($eventId);

        if ($event->status === 'aktif') {
            $event->status = 'ditutup';
            $event->save();

            return;
        }

        // Only one kegiatan is meant to be open for presensi at a time — the
        // mahasiswa app shows a single "currently open" session, not a picker.
        Event::query()->where('status', 'aktif')->where('id', '!=', $event->id)->update(['status' => 'ditutup']);

        $event->status = 'aktif';
        $event->save();
    }

    public function openQr(int $eventId): void
    {
        $this->dispatch('qr-open', eventId: $eventId);
    }

    public function toggleManualCheckin(int $eventId): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $event = Event::findOrFail($eventId);
        $event->izinkan_presensi_manual = ! $event->izinkan_presensi_manual;
        $event->save();
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
                @if ($event->jam_mulai_presensi && $event->jam_selesai_presensi)
                    <p class="text-xs mb-3" style="color:var(--ink-soft);"><i class="ti ti-clock" style="font-size:13px; vertical-align:-1px;"></i> Presensi dibuka pukul {{ substr($event->jam_mulai_presensi, 0, 5) }}&ndash;{{ substr($event->jam_selesai_presensi, 0, 5) }} WIT</p>
                @endif
                <div class="flex flex-wrap gap-2">
                    <button class="btn btn-outline" wire:click="openQr({{ $event->id }})"><i class="ti ti-qrcode"></i>Lihat QR</button>
                    <a href="{{ route('admin.layar', ['event' => $event->id]) }}" target="_blank" class="btn btn-outline"><i class="ti ti-device-tv"></i>Tampilkan layar</a>
                    <button class="btn btn-outline" wire:click="toggleStatus({{ $event->id }})"><i class="ti {{ $toggleIcon }}"></i>{{ $toggleLabel }}</button>
                    <button class="btn btn-ghost" wire:click="openEditModal({{ $event->id }})"><i class="ti ti-edit"></i>Edit</button>
                    <a href="{{ route('admin.monitoring', ['event' => $event->id]) }}" wire:navigate class="btn btn-ghost"><i class="ti ti-activity"></i>Monitoring</a>
                </div>
                <div class="flex items-center justify-between mt-3 pt-3" style="border-top:1px solid var(--line);">
                    <span class="text-xs" style="color:var(--ink-soft);">
                        Presensi manual (jika QR gagal):
                        <span class="font-semibold" style="color:{{ $event->izinkan_presensi_manual ? 'var(--umk-green)' : 'var(--ink-soft)' }};">{{ $event->izinkan_presensi_manual ? 'Aktif' : 'Nonaktif' }}</span>
                    </span>
                    @if (auth()->user()->isSuperAdmin())
                        <button class="btn btn-ghost" wire:click="toggleManualCheckin({{ $event->id }})">
                            {{ $event->izinkan_presensi_manual ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if ($showModal)
        <div class="modal-backdrop">
            <div class="card p-6" style="width:420px; max-width:100%;">
                <h3 class="display font-bold text-base mb-4">{{ $editingEventId ? 'Edit kegiatan' : 'Tambah kegiatan' }}</h3>
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
                    <div>
                        <label class="text-xs font-semibold block mb-1">Waktu aktif presensi (opsional, WIT)</label>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <input type="time" class="field-input" wire:model="jamMulaiPresensi">
                                @error('jamMulaiPresensi') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <input type="time" class="field-input" wire:model="jamSelesaiPresensi">
                                @error('jamSelesaiPresensi') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <p class="text-xs mt-1" style="color:var(--ink-soft);">Kosongkan jika presensi tidak dibatasi jam tertentu selama kegiatan dibuka.</p>
                    </div>
                </div>
                <div class="flex gap-2 mt-5">
                    <button class="btn btn-ghost flex-1 justify-center" wire:click="closeModal">Batal</button>
                    <button class="btn btn-primary flex-1 justify-center" wire:click="save">{{ $editingEventId ? 'Simpan perubahan' : 'Simpan sebagai draf' }}</button>
                </div>
            </div>
        </div>
    @endif

    <livewire:admin.qr-modal />
</div>
