<?php

use App\Imports\MahasiswaImport;
use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

new #[Layout('layouts.admin', ['title' => 'Data mahasiswa'])] class extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $fakultas = '';

    public string $prodi = '';

    public bool $showImportModal = false;

    public $importFile = null;

    public ?string $importSummary = null;

    /** @var list<string> */
    public array $importErrors = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFakultas(): void
    {
        $this->prodi = '';
        $this->resetPage();
    }

    public function updatingProdi(): void
    {
        $this->resetPage();
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
    public function mahasiswaList()
    {
        return Mahasiswa::query()
            ->with('programStudi.fakultas')
            ->when($this->search, fn ($q) => $q->where(fn ($qq) => $qq
                ->where('nama', 'like', "%{$this->search}%")
                ->orWhere('nim', 'like', "%{$this->search}%")))
            ->when($this->fakultas, fn ($q) => $q->whereHas('programStudi', fn ($qq) => $qq->where('fakultas_id', $this->fakultas)))
            ->when($this->prodi, fn ($q) => $q->where('program_studi_id', $this->prodi))
            ->orderBy('nama')
            ->paginate(15);
    }

    public function openImportModal(): void
    {
        $this->importFile = null;
        $this->importSummary = null;
        $this->importErrors = [];
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
    }

    public function import(): void
    {
        Validator::make(
            ['importFile' => $this->importFile],
            ['importFile' => 'required|file|mimes:xlsx,xls,csv'],
        )->validate();

        $importer = new MahasiswaImport;
        Excel::import($importer, $this->importFile->getRealPath());

        $this->importSummary = "{$importer->created} data baru, {$importer->updated} diperbarui, {$importer->skipped} dilewati.";
        $this->importErrors = array_slice($importer->errors, 0, 10);
        $this->importFile = null;
        $this->resetPage();
    }
}; ?>

<div>
    <div class="card p-4 mb-4">
        <div class="flex flex-col md:flex-row md:items-end gap-3 md:justify-between mb-3">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 flex-1">
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold block mb-1">Cari nama / NIM</label>
                    <input class="field-input" wire:model.live.debounce.400ms="search" placeholder="Ketik untuk mencari">
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Fakultas</label>
                    <select class="field-input" wire:model.live="fakultas">
                        <option value="">Semua fakultas</option>
                        @foreach ($this->fakultasList as $f)
                            <option value="{{ $f->id }}">{{ $f->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Program studi</label>
                    <select class="field-input" wire:model.live="prodi">
                        <option value="">Semua prodi</option>
                        @foreach ($this->prodiOptions as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button class="btn btn-primary flex-shrink-0" wire:click="openImportModal"><i class="ti ti-file-import"></i>Import Excel</button>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table>
                <thead><tr><th>NIM</th><th>Nama</th><th>Fakultas</th><th>Program studi</th><th>Jalur</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($this->mahasiswaList as $mhs)
                        <tr wire:key="mhs-{{ $mhs->id }}">
                            <td>{{ $mhs->nim }}</td>
                            <td>{{ $mhs->nama }}</td>
                            <td>{{ $mhs->programStudi->fakultas->name ?? '-' }}</td>
                            <td>{{ $mhs->programStudi->name ?? '-' }}</td>
                            <td>{{ $mhs->jalur ?: '-' }}</td>
                            <td><span class="pill pill-active">{{ $mhs->status_pendaftar ?: 'Aktif' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="color:var(--ink-soft);">Tidak ada mahasiswa yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t" style="border-color:var(--line);">
            {{ $this->mahasiswaList->links() }}
        </div>
    </div>

    @if ($showImportModal)
        <div class="modal-backdrop">
            <div class="card p-6" style="width:440px; max-width:100%;">
                <h3 class="display font-bold text-base mb-2">Import data mahasiswa</h3>
                <p class="text-xs mb-4" style="color:var(--ink-soft);">Kolom yang dibaca: No Registrasi, NIM, Nama, No Telp, Status Pendaftar, Jalur, Prodi, Fakultas.</p>
                <input type="file" wire:model="importFile" accept=".xlsx,.xls,.csv" class="field-input">
                @error('importFile') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
                <div wire:loading wire:target="importFile" class="text-xs mt-2" style="color:var(--ink-soft);">Mengunggah...</div>

                @if ($importSummary)
                    <div class="mt-3 p-3 rounded-lg text-xs" style="background:#eef6f0; color:var(--umk-green);">{{ $importSummary }}</div>
                @endif
                @if (! empty($importErrors))
                    <ul class="mt-2 text-xs" style="color:var(--umk-red);">
                        @foreach ($importErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                <div class="flex gap-2 mt-5">
                    <button class="btn btn-ghost flex-1 justify-center" wire:click="closeImportModal">Tutup</button>
                    <button class="btn btn-primary flex-1 justify-center" wire:click="import" wire:loading.attr="disabled" wire:target="import">Proses import</button>
                </div>
            </div>
        </div>
    @endif
</div>
