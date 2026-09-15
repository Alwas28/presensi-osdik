<?php

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Fakultas;
use App\Models\Mahasiswa;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin', ['title' => 'Dashboard'])] class extends Component
{
    #[Computed]
    public function activeEvent(): ?Event
    {
        return Event::query()->where('status', 'aktif')->first()
            ?? Event::query()->orderBy('tanggal')->orderBy('jam_mulai')->first();
    }

    #[Computed]
    public function totalMahasiswa(): int
    {
        return Mahasiswa::query()->count();
    }

    #[Computed]
    public function totalHadir(): int
    {
        $event = $this->activeEvent;

        return $event ? $event->attendances()->count() : 0;
    }

    #[Computed]
    public function fakultasBreakdown(): array
    {
        $event = $this->activeEvent;

        return Fakultas::query()->orderBy('name')->get()->map(function (Fakultas $fakultas) use ($event) {
            $total = Mahasiswa::query()
                ->whereHas('programStudi', fn ($q) => $q->where('fakultas_id', $fakultas->id))
                ->count();

            $hadir = $event
                ? Attendance::query()
                    ->where('event_id', $event->id)
                    ->whereHas('mahasiswa.programStudi', fn ($q) => $q->where('fakultas_id', $fakultas->id))
                    ->count()
                : 0;

            return ['code' => $fakultas->code, 'total' => $total, 'hadir' => $hadir, 'belum' => $total - $hadir];
        })->all();
    }

    #[Computed]
    public function recentAttendances()
    {
        $event = $this->activeEvent;

        if (! $event) {
            return collect();
        }

        return $event->attendances()->with('mahasiswa.programStudi')->latest('check_in')->take(6)->get();
    }

    public function openQr(int $eventId): void
    {
        $this->dispatch('qr-open', eventId: $eventId);
    }

    public function refreshChart(): void
    {
        $breakdown = collect($this->fakultasBreakdown);

        $this->dispatch(
            'dashboard-chart-updated',
            fakLabels: $breakdown->pluck('code')->all(),
            fakHadir: $breakdown->pluck('hadir')->all(),
            fakBelum: $breakdown->pluck('belum')->all(),
            hadir: $this->totalHadir,
            belum: $this->totalMahasiswa - $this->totalHadir,
        );
    }
}; ?>

<div wire:poll.10s="refreshChart">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-6">
        <div class="card p-4">
            <p class="text-xs mb-1" style="color:var(--ink-soft);">Total mahasiswa</p>
            <p class="display font-extrabold text-2xl">{{ number_format($this->totalMahasiswa, 0, ',', '.') }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs mb-1" style="color:var(--ink-soft);">Sudah hadir</p>
            <p class="display font-extrabold text-2xl" style="color:var(--umk-green);">{{ number_format($this->totalHadir, 0, ',', '.') }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs mb-1" style="color:var(--ink-soft);">Belum hadir</p>
            <p class="display font-extrabold text-2xl" style="color:var(--umk-red);">{{ number_format($this->totalMahasiswa - $this->totalHadir, 0, ',', '.') }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs mb-1" style="color:var(--ink-soft);">Persentase</p>
            <p class="display font-extrabold text-2xl">{{ $this->totalMahasiswa ? number_format($this->totalHadir / $this->totalMahasiswa * 100, 1) : 0 }}%</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="card p-5 lg:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-sm">Kehadiran per fakultas</h3>
                <span class="text-xs" style="color:var(--ink-soft);">{{ $this->activeEvent?->nama ?? '-' }}</span>
            </div>
            <div style="height:220px;" wire:ignore
                 x-data
                 x-init="
                    let chart = new Chart($refs.chartFakultas, {
                        type: 'bar',
                        data: {
                            labels: @js(collect($this->fakultasBreakdown)->pluck('code')),
                            datasets: [
                                { label: 'Hadir', data: @js(collect($this->fakultasBreakdown)->pluck('hadir')), backgroundColor: '#006636', borderRadius: 4 },
                                { label: 'Belum', data: @js(collect($this->fakultasBreakdown)->pluck('belum')), backgroundColor: '#e2e8e1', borderRadius: 4 },
                            ],
                        },
                        options: { responsive: true, maintainAspectRatio: false, scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, grid: { color: '#eef1ee' } } }, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } } },
                    });
                    $wire.on('dashboard-chart-updated', (e) => {
                        chart.data.labels = e.fakLabels;
                        chart.data.datasets[0].data = e.fakHadir;
                        chart.data.datasets[1].data = e.fakBelum;
                        chart.update();
                    });
                 ">
                <canvas x-ref="chartFakultas"></canvas>
            </div>
        </div>
        <div class="card p-5">
            <h3 class="font-semibold text-sm mb-3">Hadir vs belum</h3>
            <div style="height:180px;" class="flex items-center justify-center" wire:ignore
                 x-data
                 x-init="
                    let donut = new Chart($refs.chartDonut, {
                        type: 'doughnut',
                        data: { labels: ['Hadir', 'Belum hadir'], datasets: [{ data: [{{ $this->totalHadir }}, {{ $this->totalMahasiswa - $this->totalHadir }}], backgroundColor: ['#006636', '#f0c6c6'] }] },
                        options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } } },
                    });
                    $wire.on('dashboard-chart-updated', (e) => {
                        donut.data.datasets[0].data = [e.hadir, e.belum];
                        donut.update();
                    });
                 ">
                <canvas x-ref="chartDonut"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="card p-5 lg:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-sm">Presensi terbaru</h3>
                <a href="{{ route('admin.monitoring') }}" wire:navigate class="text-xs" style="color:var(--umk-green); font-weight:600;">Lihat semua</a>
            </div>
            <div>
                @forelse ($this->recentAttendances as $attendance)
                    <div class="flex items-center justify-between py-2 border-b" style="border-color:var(--line);">
                        <div class="min-w-0">
                            <p class="text-sm font-medium truncate">{{ $attendance->mahasiswa->nama }}</p>
                            <p class="text-xs truncate" style="color:var(--ink-soft);">{{ $attendance->mahasiswa->programStudi->name }}</p>
                        </div>
                        <span class="text-xs flex-shrink-0" style="color:var(--ink-soft);">{{ $attendance->check_in?->format('H:i:s') }}</span>
                    </div>
                @empty
                    <p class="text-sm p-2" style="color:var(--ink-soft);">Belum ada presensi.</p>
                @endforelse
            </div>
        </div>
        <div class="card p-5">
            <h3 class="font-semibold text-sm mb-3">Kegiatan aktif</h3>
            @if ($this->activeEvent)
                <p class="font-semibold text-sm mb-1">{{ $this->activeEvent->nama }}</p>
                <p class="text-xs mb-3" style="color:var(--ink-soft);">
                    {{ $this->activeEvent->tanggal->translatedFormat('d M Y') }} &middot; {{ \Illuminate\Support\Str::substr($this->activeEvent->jam_mulai, 0, 5) }}&ndash;{{ \Illuminate\Support\Str::substr($this->activeEvent->jam_selesai, 0, 5) }}<br>
                    {{ $this->activeEvent->lokasi }}
                </p>
                <span class="pill {{ match($this->activeEvent->status) { 'aktif' => 'pill-active', 'ditutup' => 'pill-closed', 'belum_dibuka' => 'pill-notyet', 'selesai' => 'pill-done', default => 'pill-draft' } }}">
                    {{ str($this->activeEvent->status)->replace('_', ' ')->title() }}
                </span>
                <button class="btn btn-outline w-full justify-center mt-4" wire:click="openQr({{ $this->activeEvent->id }})"><i class="ti ti-qrcode"></i>Lihat QR</button>
            @else
                <p class="text-sm" style="color:var(--ink-soft);">Belum ada kegiatan.</p>
            @endif
        </div>
    </div>

    <livewire:admin.qr-modal />
</div>
