<?php

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Fakultas;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin', ['title' => 'Laporan & rekap'])] class extends Component
{
    public ?int $eventId = null;

    public function mount(): void
    {
        $this->eventId = $this->events->first()?->id;
    }

    #[Computed]
    public function events()
    {
        return Event::query()->orderBy('tanggal')->orderBy('jam_mulai')->get();
    }

    #[Computed]
    public function event(): ?Event
    {
        return $this->eventId ? Event::find($this->eventId) : null;
    }

    #[Computed]
    public function rekap(): array
    {
        $eventId = $this->eventId;

        return Fakultas::query()->with('programStudis')->orderBy('name')->get()->map(function (Fakultas $fakultas) use ($eventId) {
            $prodiRows = $fakultas->programStudis->map(function ($prodi) use ($eventId) {
                $total = $prodi->mahasiswas()->count();
                $hadir = $eventId
                    ? Attendance::query()->where('event_id', $eventId)->whereHas('mahasiswa', fn ($q) => $q->where('program_studi_id', $prodi->id))->count()
                    : 0;

                return ['prodi' => $prodi->name, 'total' => $total, 'hadir' => $hadir, 'belum' => $total - $hadir];
            })->values();

            return [
                'fakultas' => $fakultas->name,
                'total' => $prodiRows->sum('total'),
                'hadir' => $prodiRows->sum('hadir'),
                'belum' => $prodiRows->sum('belum'),
                'prodi' => $prodiRows->all(),
            ];
        })->all();
    }

    #[Computed]
    public function totals(): array
    {
        $rekap = collect($this->rekap);

        return [
            'total' => $rekap->sum('total'),
            'hadir' => $rekap->sum('hadir'),
            'belum' => $rekap->sum('belum'),
        ];
    }

    #[Computed]
    public function exportRows(): array
    {
        $rows = [['Fakultas', 'Program Studi', 'Peserta', 'Hadir', 'Belum', 'Persentase']];

        foreach ($this->rekap as $fakultas) {
            foreach ($fakultas['prodi'] as $prodi) {
                $pct = $prodi['total'] ? round($prodi['hadir'] / $prodi['total'] * 1000) / 10 : 0;
                $rows[] = [$fakultas['fakultas'], $prodi['prodi'], $prodi['total'], $prodi['hadir'], $prodi['belum'], $pct.'%'];
            }
        }

        return $rows;
    }
}; ?>

<div>
    <div class="card p-4 mb-4 flex flex-col md:flex-row md:items-center gap-3 md:justify-between">
        <div class="flex items-center gap-3">
            <label class="text-xs font-semibold">Kegiatan</label>
            <select class="field-input" wire:model.live="eventId" style="width:auto; min-width:220px;">
                @foreach ($this->events as $event)
                    <option value="{{ $event->id }}">{{ $event->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button class="btn btn-outline" onclick="presensiExport('csv')"><i class="ti ti-file-type-csv"></i>CSV</button>
            <button class="btn btn-outline" onclick="presensiExport('xlsx')"><i class="ti ti-file-spreadsheet"></i>Excel</button>
            <button class="btn btn-outline" onclick="presensiExport('pdf')"><i class="ti ti-file-type-pdf"></i>PDF</button>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="card p-4"><p class="text-xs mb-1" style="color:var(--ink-soft);">Total mahasiswa</p><p class="display font-bold text-xl">{{ number_format($this->totals['total'], 0, ',', '.') }}</p></div>
        <div class="card p-4"><p class="text-xs mb-1" style="color:var(--ink-soft);">Hadir</p><p class="display font-bold text-xl" style="color:var(--umk-green);">{{ number_format($this->totals['hadir'], 0, ',', '.') }}</p></div>
        <div class="card p-4"><p class="text-xs mb-1" style="color:var(--ink-soft);">Belum hadir</p><p class="display font-bold text-xl" style="color:var(--umk-red);">{{ number_format($this->totals['belum'], 0, ',', '.') }}</p></div>
        @php $pct = $this->totals['total'] ? $this->totals['hadir'] / $this->totals['total'] * 100 : 0; @endphp
        <div class="card p-4"><p class="text-xs mb-1" style="color:var(--ink-soft);">Persentase</p><p class="display font-bold text-xl">{{ number_format($pct, 1) }}%</p></div>
    </div>

    <div class="card overflow-hidden mb-4">
        <div class="p-4 pb-0"><h3 class="font-semibold text-sm">Rekap per fakultas</h3><p class="text-xs mt-0.5" style="color:var(--ink-soft);">Klik baris untuk melihat rincian program studi.</p></div>
        <div class="overflow-x-auto mt-2">
            <table>
                <thead><tr><th>Fakultas</th><th>Peserta</th><th>Hadir</th><th>Belum</th><th>Persentase</th></tr></thead>
                @foreach ($this->rekap as $idx => $row)
                    @php $rowPct = $row['total'] ? $row['hadir'] / $row['total'] * 100 : 0; @endphp
                    <tbody x-data="{ open: false }" wire:key="fak-{{ $idx }}">
                        <tr @click="open = !open" style="cursor:pointer;">
                            <td><i class="ti ti-chevron-right" x-show="!open"></i><i class="ti ti-chevron-down" x-show="open" x-cloak></i> {{ $row['fakultas'] }}</td>
                            <td>{{ $row['total'] }}</td>
                            <td style="color:var(--umk-green); font-weight:600;">{{ $row['hadir'] }}</td>
                            <td style="color:var(--umk-red);">{{ $row['belum'] }}</td>
                            <td>{{ number_format($rowPct, 1) }}%</td>
                        </tr>
                        @foreach ($row['prodi'] as $prodi)
                            @php $prodiPct = $prodi['total'] ? $prodi['hadir'] / $prodi['total'] * 100 : 0; @endphp
                            <tr x-show="open" x-cloak style="background:var(--paper);">
                                <td style="padding-left:34px; color:var(--ink-soft);">{{ $prodi['prodi'] }}</td>
                                <td>{{ $prodi['total'] }}</td>
                                <td style="color:var(--umk-green);">{{ $prodi['hadir'] }}</td>
                                <td style="color:var(--umk-red);">{{ $prodi['belum'] }}</td>
                                <td>{{ number_format($prodiPct, 1) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                @endforeach
            </table>
        </div>
    </div>

    <div id="laporan-export-data"
         data-name="{{ $this->event?->nama }}"
         data-rows="{{ json_encode($this->exportRows) }}"
         class="hidden"></div>
</div>

@script
<script>
    window.presensiExport = function (type) {
        var el = document.getElementById('laporan-export-data');
        var rows = JSON.parse(el.dataset.rows);
        var evName = el.dataset.name || 'Kegiatan';

        if (type === 'csv') {
            var csv = rows.map(function (r) { return r.map(function (c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(','); }).join('\n');
            var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            downloadBlob(blob, 'Rekap - ' + evName + '.csv');
        } else if (type === 'xlsx') {
            var ws = XLSX.utils.aoa_to_sheet(rows);
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Rekap');
            XLSX.writeFile(wb, 'Rekap - ' + evName + '.xlsx');
        } else if (type === 'pdf') {
            var jsPDFCtor = window.jspdf.jsPDF;
            var doc = new jsPDFCtor();
            doc.setFontSize(13);
            doc.text('Rekap Kehadiran - ' + evName, 14, 16);
            doc.setFontSize(9);
            var y = 26;
            rows.forEach(function (row, i) {
                var line = row.join('   |   ');
                doc.setFont(undefined, i === 0 ? 'bold' : 'normal');
                doc.text(line, 14, y);
                y += 6;
                if (y > 280) { doc.addPage(); y = 16; }
            });
            doc.save('Rekap - ' + evName + '.pdf');
        }
    };

    function downloadBlob(blob, filename) {
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
</script>
@endscript
