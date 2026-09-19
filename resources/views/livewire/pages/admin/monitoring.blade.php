<?php

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.admin', ['title' => 'Monitoring real-time'])] class extends Component
{
    #[Url(as: 'event')]
    public ?int $eventId = null;

    public string $fakultas = '';

    public string $prodi = '';

    public string $status = '';

    public string $search = '';

    public function mount(): void
    {
        if (! $this->eventId) {
            $this->eventId = $this->events->first()?->id;
        }
    }

    public function updatingFakultas(): void
    {
        $this->prodi = '';
    }

    #[Computed]
    public function events()
    {
        return Event::query()->orderBy('tanggal')->orderBy('jam_mulai')->get();
    }

    #[Computed]
    public function fakultasList()
    {
        return Fakultas::query()->orderBy('name')->get();
    }

    #[Computed]
    public function prodiOptions()
    {
        if (! $this->fakultas) {
            return collect();
        }

        return ProgramStudi::query()->where('fakultas_id', $this->fakultas)->orderBy('name')->get();
    }

    #[Computed]
    public function filteredMahasiswaQuery()
    {
        return Mahasiswa::query()
            ->when($this->fakultas, fn ($q) => $q->whereHas('programStudi', fn ($qq) => $qq->where('fakultas_id', $this->fakultas)))
            ->when($this->prodi, fn ($q) => $q->where('program_studi_id', $this->prodi))
            ->when($this->search, fn ($q) => $q->where(fn ($qq) => $qq
                ->where('nama', 'like', "%{$this->search}%")
                ->orWhere('nim', 'like', "%{$this->search}%")));
    }

    #[Computed]
    public function totalCount(): int
    {
        return (clone $this->filteredMahasiswaQuery)->count();
    }

    #[Computed]
    public function hadirCount(): int
    {
        if (! $this->eventId) {
            return 0;
        }

        return (clone $this->filteredMahasiswaQuery)
            ->whereHas('attendances', fn ($q) => $q->where('event_id', $this->eventId))
            ->count();
    }

    #[Computed]
    public function log()
    {
        if (! $this->eventId || $this->status === 'belum') {
            return collect();
        }

        return Attendance::query()
            ->where('event_id', $this->eventId)
            ->whereHas('mahasiswa', function ($q) {
                $q->when($this->fakultas, fn ($qq) => $qq->whereHas('programStudi', fn ($qqq) => $qqq->where('fakultas_id', $this->fakultas)))
                    ->when($this->prodi, fn ($qq) => $qq->where('program_studi_id', $this->prodi))
                    ->when($this->search, fn ($qq) => $qq->where(fn ($qqq) => $qqq
                        ->where('nama', 'like', "%{$this->search}%")
                        ->orWhere('nim', 'like', "%{$this->search}%")));
            })
            ->with('mahasiswa.programStudi')
            ->latest('check_in')
            ->take(30)
            ->get();
    }
}; ?>

<div wire:poll.5s>
    <div class="card p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div>
                <label class="text-xs font-semibold block mb-1">Kegiatan</label>
                <select class="field-input" wire:model.live="eventId">
                    @foreach ($this->events as $event)
                        <option value="{{ $event->id }}">{{ $event->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold block mb-1">Fakultas</label>
                <select class="field-input" wire:model.live="fakultas">
                    <option value="">Semua fakultas</option>
                    @foreach ($this->fakultasList as $fakultas)
                        <option value="{{ $fakultas->id }}">{{ $fakultas->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold block mb-1">Program studi</label>
                <select class="field-input" wire:model.live="prodi">
                    <option value="">Semua prodi</option>
                    @foreach ($this->prodiOptions as $prodi)
                        <option value="{{ $prodi->id }}">{{ $prodi->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold block mb-1">Status</label>
                <select class="field-input" wire:model.live="status">
                    <option value="">Semua status</option>
                    <option value="hadir">Hadir</option>
                    <option value="belum">Belum hadir</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold block mb-1">Cari nama / NIM</label>
                <input class="field-input" wire:model.live.debounce.400ms="search" placeholder="Ketik untuk mencari">
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="card p-4"><p class="text-xs mb-1" style="color:var(--ink-soft);">Total peserta</p><p class="display font-bold text-xl">{{ number_format($this->totalCount, 0, ',', '.') }}</p></div>
        <div class="card p-4"><p class="text-xs mb-1" style="color:var(--ink-soft);">Sudah hadir</p><p class="display font-bold text-xl" style="color:var(--umk-green);">{{ number_format($this->hadirCount, 0, ',', '.') }}</p></div>
        <div class="card p-4"><p class="text-xs mb-1" style="color:var(--ink-soft);">Belum hadir</p><p class="display font-bold text-xl" style="color:var(--umk-red);">{{ number_format($this->totalCount - $this->hadirCount, 0, ',', '.') }}</p></div>
        @php $pct = $this->totalCount ? $this->hadirCount / $this->totalCount * 100 : 0; @endphp
        <div class="card p-4">
            <p class="text-xs mb-1" style="color:var(--ink-soft);">Persentase</p>
            <p class="display font-bold text-xl mb-1">{{ number_format($pct, 1) }}%</p>
            <div class="progress-track"><div class="progress-fill" style="width:{{ $pct }}%;"></div></div>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="flex items-center justify-between p-4 pb-0">
            <h3 class="font-semibold text-sm">Log kehadiran <span class="inline-block" style="width:7px;height:7px;border-radius:999px;background:#2ea043; margin-left:6px;"></span></h3>
            <div class="flex items-center gap-3">
                <span class="text-xs" style="color:var(--ink-soft);">menampilkan {{ min(30, $this->log->count()) }} dari {{ $this->hadirCount }} hadir</span>
                @if ($eventId)
                    <a class="btn btn-outline" href="{{ route('admin.monitoring.export', array_filter(['event' => $eventId, 'fakultas' => $fakultas, 'prodi' => $prodi, 'status' => $status, 'search' => $search])) }}">
                        <i class="ti ti-file-spreadsheet"></i>Export Excel
                    </a>
                @endif
            </div>
        </div>
        <div class="overflow-x-auto mt-2">
            <table>
                <thead><tr><th>Waktu</th><th>Nama</th><th>NIM</th><th>Fakultas / Prodi</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($this->log as $attendance)
                        <tr wire:key="log-{{ $attendance->id }}">
                            <td>{{ $attendance->check_in?->format('H:i:s') }}</td>
                            <td>{{ $attendance->mahasiswa->nama }}</td>
                            <td>{{ $attendance->mahasiswa->nim }}</td>
                            <td>{{ $attendance->mahasiswa->programStudi->name }}</td>
                            <td><span class="pill pill-active">Hadir</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="color:var(--ink-soft);">Tidak ada data yang cocok dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
