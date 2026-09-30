<?php

use App\Models\Sertifikat;
use App\Models\SertifikatSetting;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin', ['title' => 'Sertifikat'])] class extends Component
{
    use WithPagination;

    #[Validate('required|string|max:255')]
    public string $format = '';

    #[Validate('required|integer|min:1|max:10')]
    public int $digitUrut = 3;

    #[Validate('required|integer|min:0')]
    public int $nomorUrutTerakhir = 0;

    public ?string $saved = null;

    public function mount(): void
    {
        $setting = SertifikatSetting::current();
        $this->format = $setting->format;
        $this->digitUrut = $setting->digit_urut;
        $this->nomorUrutTerakhir = $setting->nomor_urut_terakhir;
    }

    #[Computed]
    public function preview(): string
    {
        $preview = new SertifikatSetting([
            'format' => $this->format,
            'digit_urut' => $this->digitUrut,
        ]);

        return $preview->formatNomor($this->nomorUrutTerakhir + 1);
    }

    #[Computed]
    public function sertifikatList()
    {
        return Sertifikat::query()
            ->with('mahasiswa.programStudi')
            ->latest()
            ->paginate(15);
    }

    public function save(): void
    {
        $this->validate();

        SertifikatSetting::current()->update([
            'format' => $this->format,
            'digit_urut' => $this->digitUrut,
            'nomor_urut_terakhir' => $this->nomorUrutTerakhir,
        ]);

        $this->saved = 'Pengaturan nomor sertifikat disimpan.';
    }
}; ?>

<div>
    <div class="card p-4 mb-4">
        <h3 class="font-semibold text-sm mb-3">Format nomor sertifikat</h3>
        @if ($saved)
            <div class="p-3 rounded-lg text-xs font-medium mb-4" style="background:#e7f3ea; color:var(--umk-green);">{{ $saved }}</div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
            <div class="md:col-span-2">
                <label class="text-xs font-semibold block mb-1">Format</label>
                <input class="field-input" wire:model.live="format" placeholder="{urut}/SERTIFIKAT/OSDIK/UMK/{bulan_romawi}/{tahun}">
                @error('format') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
                <p class="text-xs mt-1" style="color:var(--ink-soft);">Placeholder yang bisa dipakai: <code>{urut}</code>, <code>{bulan_romawi}</code>, <code>{tahun}</code>.</p>
            </div>
            <div>
                <label class="text-xs font-semibold block mb-1">Digit nomor urut</label>
                <input type="number" min="1" max="10" class="field-input" wire:model.live="digitUrut">
                @error('digitUrut') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="mb-3">
            <label class="text-xs font-semibold block mb-1">Nomor urut terakhir</label>
            <input type="number" min="0" class="field-input" style="max-width:200px;" wire:model.live="nomorUrutTerakhir">
            @error('nomorUrutTerakhir') <p class="text-xs mt-1" style="color:var(--umk-red);">{{ $message }}</p> @enderror
            <p class="text-xs mt-1" style="color:var(--ink-soft);">Sertifikat berikutnya akan memakai nomor urut setelah ini. Ubah hanya jika perlu koreksi.</p>
        </div>
        <div class="p-3 rounded-lg text-sm mb-4" style="background:var(--paper); border:1px solid var(--line);">
            Pratinjau nomor berikutnya: <span class="font-semibold">{{ $this->preview }}</span>
        </div>
        <button class="btn btn-primary" wire:click="save"><i class="ti ti-device-floppy"></i>Simpan pengaturan</button>
    </div>

    <div class="card overflow-hidden">
        <div class="p-4 pb-0"><h3 class="font-semibold text-sm">Sertifikat yang sudah digenerate</h3></div>
        <div class="overflow-x-auto mt-2">
            <table>
                <thead><tr><th>NIM</th><th>Nama</th><th>Program studi</th><th>Nomor sertifikat</th><th>Digenerate</th></tr></thead>
                <tbody>
                    @forelse ($this->sertifikatList as $sertifikat)
                        <tr wire:key="sertifikat-{{ $sertifikat->id }}">
                            <td>{{ $sertifikat->mahasiswa->nim }}</td>
                            <td>{{ $sertifikat->mahasiswa->nama }}</td>
                            <td>{{ $sertifikat->mahasiswa->programStudi->name ?? '-' }}</td>
                            <td>{{ $sertifikat->nomor }}</td>
                            <td>{{ $sertifikat->created_at->translatedFormat('d M Y, H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="color:var(--ink-soft);">Belum ada sertifikat yang digenerate.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t" style="border-color:var(--line);">
            {{ $this->sertifikatList->links() }}
        </div>
    </div>
</div>
