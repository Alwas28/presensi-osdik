<?php

use App\Models\Attendance;
use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.mahasiswa', ['title' => 'Riwayat'])] class extends Component
{
    #[Computed]
    public function totalEvents(): int
    {
        return Event::query()->count();
    }

    #[Computed]
    public function attendances()
    {
        return Attendance::query()
            ->where('mahasiswa_id', auth()->user()->mahasiswa_id)
            ->with('event')
            ->latest('check_in')
            ->get();
    }
}; ?>

<div class="p-5 pb-2">
    <h2 class="display font-bold text-base mb-1">Riwayat presensi</h2>
    <p class="text-xs mb-4" style="color:var(--ink-soft);">{{ $this->attendances->count() }} dari {{ $this->totalEvents }} kegiatan sudah kamu ikuti.</p>

    <div class="card overflow-hidden mb-4">
        @forelse ($this->attendances as $attendance)
            <div class="flex items-center justify-between p-4" style="border-bottom:1px solid var(--line);" wire:key="riwayat-{{ $attendance->id }}">
                <div class="flex items-center gap-3 min-w-0">
                    <i class="ti ti-circle-check" style="color:var(--umk-green); font-size:20px; flex-shrink:0;"></i>
                    <div class="min-w-0"><p class="text-sm font-medium truncate">{{ $attendance->event->nama }}</p><p class="text-xs" style="color:var(--ink-soft);">{{ $attendance->event->tanggal->translatedFormat('d M Y') }}</p></div>
                </div>
                <span class="text-xs font-semibold flex-shrink-0" style="color:var(--ink-soft);">{{ $attendance->check_in->format('H:i') }}</span>
            </div>
        @empty
            <p class="text-sm p-4" style="color:var(--ink-soft);">Belum ada riwayat.</p>
        @endforelse
    </div>
</div>
