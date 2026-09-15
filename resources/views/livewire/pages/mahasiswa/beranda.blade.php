<?php

use App\Models\Attendance;
use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.mahasiswa', ['title' => 'Beranda'])] class extends Component
{
    #[Computed]
    public function mahasiswa()
    {
        return auth()->user()->mahasiswa()->with('programStudi.fakultas')->first();
    }

    #[Computed]
    public function activeEvent(): ?Event
    {
        return Event::query()->where('status', 'aktif')->first()
            ?? Event::query()->orderBy('tanggal')->orderBy('jam_mulai')->first();
    }

    #[Computed]
    public function attendanceForActive(): ?Attendance
    {
        $event = $this->activeEvent;

        if (! $event) {
            return null;
        }

        return Attendance::query()
            ->where('mahasiswa_id', $this->mahasiswa->id)
            ->where('event_id', $event->id)
            ->first();
    }

    #[Computed]
    public function totalEvents(): int
    {
        return Event::query()->count();
    }

    #[Computed]
    public function attendedCount(): int
    {
        return Attendance::query()->where('mahasiswa_id', $this->mahasiswa->id)->count();
    }

    #[Computed]
    public function recentAttendances()
    {
        return Attendance::query()
            ->where('mahasiswa_id', $this->mahasiswa->id)
            ->with('event')
            ->latest('check_in')
            ->take(2)
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
    <div class="flex items-center justify-between mb-5">
        <div>
            <p class="text-xs" style="color:var(--ink-soft);">Selamat datang,</p>
            <p class="display font-bold text-base">{{ $this->mahasiswa->nama }}</p>
        </div>
        <div class="flex items-center justify-center rounded-full flex-shrink-0" style="width:38px;height:38px;background:#eef6f0;color:var(--umk-green);font-weight:700;font-size:13px;">
            {{ collect(explode(' ', $this->mahasiswa->nama))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
        </div>
    </div>

    @if ($this->activeEvent)
        <div class="card p-4 mb-4" style="background:linear-gradient(135deg, var(--umk-green), var(--umk-green-dark)); border:none;">
            <p class="text-xs mb-1" style="color:#bfe0c8;">Kegiatan saat ini</p>
            <p class="display font-bold text-white text-sm mb-1">{{ $this->activeEvent->nama }}</p>
            <p class="text-xs mb-3" style="color:#bfe0c8;">{{ \Illuminate\Support\Str::substr($this->activeEvent->jam_mulai, 0, 5) }}&ndash;{{ \Illuminate\Support\Str::substr($this->activeEvent->jam_selesai, 0, 5) }} &middot; {{ $this->activeEvent->lokasi }}</p>
            @if ($this->attendanceForActive)
                <div class="flex items-center gap-1.5 text-xs font-semibold" style="color:#fff;">
                    <i class="ti ti-circle-check"></i>Sudah presensi {{ $this->attendanceForActive->check_in->format('H:i') }}
                </div>
            @else
                <a href="{{ route('mahasiswa.presensi', ['event' => $this->activeEvent->id]) }}" wire:navigate class="btn btn-gold w-full">Presensi sekarang</a>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="card p-3"><p class="text-xs mb-0.5" style="color:var(--ink-soft);">Kegiatan diikuti</p><p class="display font-bold text-lg">{{ $this->attendedCount }} / {{ $this->totalEvents }}</p></div>
        <div class="card p-3"><p class="text-xs mb-0.5" style="color:var(--ink-soft);">Kehadiran</p><p class="display font-bold text-lg" style="color:var(--umk-green);">{{ $this->totalEvents ? round($this->attendedCount / $this->totalEvents * 100) : 0 }}%</p></div>
    </div>

    <div class="flex items-center justify-between mb-2">
        <h3 class="font-semibold text-sm">Riwayat terbaru</h3>
        <a href="{{ route('mahasiswa.riwayat') }}" wire:navigate class="text-xs font-semibold" style="color:var(--umk-green);">Lihat semua</a>
    </div>
    <div class="card overflow-hidden mb-5">
        @forelse ($this->recentAttendances as $attendance)
            <div class="flex items-center justify-between p-3" style="border-bottom:1px solid var(--line);">
                <div class="flex items-center gap-2.5 min-w-0">
                    <i class="ti ti-circle-check" style="color:var(--umk-green); font-size:18px; flex-shrink:0;"></i>
                    <div class="min-w-0"><p class="text-sm font-medium truncate">{{ $attendance->event->nama }}</p><p class="text-xs" style="color:var(--ink-soft);">{{ $attendance->event->tanggal->translatedFormat('d M Y') }}</p></div>
                </div>
                <span class="text-xs flex-shrink-0" style="color:var(--ink-soft);">{{ $attendance->check_in->format('H:i') }}</span>
            </div>
        @empty
            <p class="text-sm p-4" style="color:var(--ink-soft);">Belum ada riwayat presensi.</p>
        @endforelse
    </div>
</div>
