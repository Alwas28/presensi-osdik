<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Mahasiswa;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One row per mahasiswa for a single kegiatan, with their presensi status and
 * check-in details. Takes the same filters as the Monitoring page.
 */
class PresensiExport extends StringValueBinder implements FromQuery, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    private const CHANNEL_LABELS = [
        'qr_event' => 'Scan QR kegiatan',
        'qr_self' => 'Discan panitia',
        'manual' => 'Presensi manual',
    ];

    public function __construct(
        private readonly Event $event,
        private readonly ?int $fakultasId = null,
        private readonly ?int $prodiId = null,
        private readonly string $status = '',
        private readonly string $search = '',
    ) {}

    /**
     * @return Builder<Mahasiswa>
     */
    public function query(): Builder
    {
        return Mahasiswa::query()
            ->with([
                'programStudi.fakultas',
                'attendances' => fn ($q) => $q->where('event_id', $this->event->id),
            ])
            ->when($this->fakultasId, fn ($q) => $q->whereHas('programStudi', fn ($qq) => $qq->where('fakultas_id', $this->fakultasId)))
            ->when($this->prodiId, fn ($q) => $q->where('program_studi_id', $this->prodiId))
            ->when($this->search !== '', fn ($q) => $q->where(fn ($qq) => $qq
                ->where('nama', 'like', "%{$this->search}%")
                ->orWhere('nim', 'like', "%{$this->search}%")))
            ->when($this->status === 'hadir', fn ($q) => $q->whereHas('attendances', fn ($qq) => $qq->where('event_id', $this->event->id)))
            ->when($this->status === 'belum', fn ($q) => $q->whereDoesntHave('attendances', fn ($qq) => $qq->where('event_id', $this->event->id)))
            ->orderBy('nama')
            ->orderBy('id');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['NIM', 'Nama', 'Fakultas', 'Program Studi', 'Jalur', 'Kegiatan', 'Status', 'Waktu Presensi', 'Metode'];
    }

    /**
     * @param  Mahasiswa  $row
     * @return list<string|null>
     */
    public function map(mixed $row): array
    {
        /** @var Attendance|null $attendance */
        $attendance = $row->attendances->first();

        return [
            $row->nim,
            $row->nama,
            $row->programStudi?->fakultas?->name,
            $row->programStudi?->name,
            $row->jalur,
            $this->event->nama,
            $attendance ? 'Hadir' : 'Belum hadir',
            $attendance?->check_in?->format('d/m/Y H:i:s'),
            $attendance ? (self::CHANNEL_LABELS[$attendance->channel] ?? $attendance->channel) : null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
