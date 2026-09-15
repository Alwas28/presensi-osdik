<?php

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.mahasiswa', ['title' => 'Kegiatan'])] class extends Component
{
    #[Computed]
    public function mahasiswaId(): int
    {
        return auth()->user()->mahasiswa_id;
    }

    #[Computed]
    public function events()
    {
        return Event::query()
            ->with(['attendances' => fn ($q) => $q->where('mahasiswa_id', $this->mahasiswaId)])
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get();
    }

    public function statusPillClass(string $status): string
    {
        return match ($status) {
            'aktif' => 'pill-active',
            'selesai' => 'pill-done',
            'belum_dibuka' => 'pill-notyet',
            default => 'pill-closed',
        };
    }
}; ?>

<div class="p-5 pb-2">
    <h2 class="display font-bold text-base mb-1">Kegiatan PKKMB 2026</h2>
    <p class="text-xs mb-4" style="color:var(--ink-soft);">Daftar rangkaian kegiatan penyambutan mahasiswa baru.</p>

    <div class="space-y-3 pb-4">
        @foreach ($this->events as $event)
            @php $attendance = $event->attendances->first(); @endphp
            <div class="card p-4" wire:key="event-{{ $event->id }}">
                <div class="flex items-start justify-between mb-1.5">
                    <p class="font-semibold text-sm pr-2">{{ $event->nama }}</p>
                    <span class="pill {{ $this->statusPillClass($event->status) }}">{{ str($event->status)->replace('_', ' ')->title() }}</span>
                </div>
                <p class="text-xs mb-3" style="color:var(--ink-soft);">{{ $event->tanggal->translatedFormat('d M Y') }} &middot; {{ \Illuminate\Support\Str::substr($event->jam_mulai, 0, 5) }}&ndash;{{ \Illuminate\Support\Str::substr($event->jam_selesai, 0, 5) }}<br>{{ $event->lokasi }}</p>
                @if ($attendance)
                    <div class="flex items-center gap-1.5 text-xs font-semibold" style="color:var(--umk-green);">
                        <i class="ti ti-circle-check"></i>Sudah presensi {{ $attendance->check_in->format('H:i') }}
                    </div>
                @elseif ($event->status === 'aktif')
                    <a href="{{ route('mahasiswa.presensi', ['event' => $event->id]) }}" wire:navigate class="btn btn-primary w-full">Presensi sekarang</a>
                @else
                    <p class="text-xs" style="color:var(--ink-soft);">Presensi belum dapat dilakukan.</p>
                @endif
            </div>
        @endforeach
    </div>
</div>
