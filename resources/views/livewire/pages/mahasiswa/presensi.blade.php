<?php

use App\Exceptions\PresensiException;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\Mahasiswa;
use App\Support\AttendanceRecorder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.mahasiswa', ['title' => 'Presensi'])] class extends Component
{
    #[Url(as: 'event')]
    public ?int $eventId = null;

    public string $method = 'scan';

    public string $manualToken = '';

    public ?string $feedback = null;

    public ?string $feedbackType = null;

    public function mount(): void
    {
        if (! $this->eventId) {
            $this->eventId = $this->activeEvent?->id;
        }
    }

    #[Computed]
    public function mahasiswa(): Mahasiswa
    {
        return auth()->user()->mahasiswa;
    }

    #[Computed]
    public function events()
    {
        return Event::query()->orderBy('tanggal')->orderBy('jam_mulai')->get();
    }

    #[Computed]
    public function activeEvent(): ?Event
    {
        return Event::query()->where('status', 'aktif')->first() ?? $this->events->first();
    }

    #[Computed]
    public function selectedEvent(): ?Event
    {
        return $this->events->firstWhere('id', $this->eventId);
    }

    #[Computed]
    public function attendanceForSelected(): ?Attendance
    {
        if (! $this->selectedEvent) {
            return null;
        }

        return Attendance::query()
            ->where('mahasiswa_id', $this->mahasiswa->id)
            ->where('event_id', $this->selectedEvent->id)
            ->first();
    }

    #[Computed]
    public function selfQrPayload(): ?string
    {
        if (! $this->selectedEvent || $this->selectedEvent->status !== 'aktif') {
            return null;
        }

        return app(AttendanceRecorder::class)->buildSelfQrPayload($this->mahasiswa, $this->selectedEvent);
    }

    public function selectEvent(int $eventId): void
    {
        $this->eventId = $eventId;
        $this->feedback = null;
        $this->feedbackType = null;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
        $this->feedback = null;
        $this->feedbackType = null;
    }

    public function checkinWithPayload(string $payload): void
    {
        try {
            $attendance = app(AttendanceRecorder::class)->recordFromEventQr($this->mahasiswa, $payload, $this->eventId);
            $this->feedbackType = 'success';
            $this->feedback = 'Presensi berhasil dicatat pukul '.$attendance->check_in->format('H:i').'.';
        } catch (PresensiException $e) {
            $this->feedbackType = 'error';
            $this->feedback = $e->getMessage();
        }
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
    <h2 class="display font-bold text-base mb-3">Presensi</h2>

    <div class="flex gap-2 overflow-x-auto pb-3 mb-3" style="scrollbar-width:none;">
        @foreach ($this->events as $event)
            <div class="chip {{ $event->id === $this->eventId ? 'active' : '' }}" wire:click="selectEvent({{ $event->id }})" wire:key="chip-{{ $event->id }}">
                {{ \Illuminate\Support\Str::of($event->nama)->words(2, '') }}
            </div>
        @endforeach
    </div>

    @if ($this->selectedEvent)
        <div class="card p-4 mb-4">
            <div class="flex items-center justify-between mb-1">
                <p class="font-semibold text-sm">{{ $this->selectedEvent->nama }}</p>
                <span class="pill {{ $this->statusPillClass($this->selectedEvent->status) }}">{{ str($this->selectedEvent->status)->replace('_', ' ')->title() }}</span>
            </div>
            <p class="text-xs" style="color:var(--ink-soft);">{{ $this->selectedEvent->tanggal->translatedFormat('d M Y') }} &middot; {{ \Illuminate\Support\Str::substr($this->selectedEvent->jam_mulai, 0, 5) }}&ndash;{{ \Illuminate\Support\Str::substr($this->selectedEvent->jam_selesai, 0, 5) }} &middot; {{ $this->selectedEvent->lokasi }}</p>
        </div>

        @if ($this->attendanceForSelected)
            <div class="flex flex-col items-center text-center py-8">
                <div class="flex items-center justify-center rounded-full mb-4" style="width:56px;height:56px;background:#e7f3ea;"><i class="ti ti-circle-check" style="font-size:28px; color:var(--umk-green);"></i></div>
                <p class="display font-bold text-sm mb-1">Sudah presensi</p>
                <p class="text-xs" style="color:var(--ink-soft);">Kamu tercatat hadir pukul {{ $this->attendanceForSelected->check_in->format('H:i') }}.</p>
            </div>
        @elseif ($this->selectedEvent->status !== 'aktif')
            <div class="flex flex-col items-center text-center py-8">
                <div class="flex items-center justify-center rounded-full mb-4" style="width:56px;height:56px;background:#fdf3d8;"><i class="ti ti-lock" style="font-size:26px; color:#8a6a00;"></i></div>
                <p class="display font-bold text-sm mb-1">Presensi belum dibuka</p>
                <p class="text-xs" style="color:var(--ink-soft);">Panitia belum membuka presensi untuk kegiatan ini.</p>
            </div>
        @else
            <div x-data="presensiPanel()" wire:key="panel-{{ $this->selectedEvent->id }}">
                <div class="flex gap-2 mb-4">
                    <button class="chip {{ $method === 'scan' ? 'active' : '' }}" style="flex:1; text-align:center;" wire:click="setMethod('scan')" @click="stopCamera()">Scan QR panitia</button>
                    <button class="chip {{ $method === 'show' ? 'active' : '' }}" style="flex:1; text-align:center;" wire:click="setMethod('show')" @click="stopCamera()">Tunjukkan QR saya</button>
                </div>

                @if ($feedback)
                    <div class="p-3 rounded-lg text-xs font-medium mb-4" style="{{ $feedbackType === 'success' ? 'background:#e7f3ea; color:var(--umk-green);' : 'background:#fdeaea; color:var(--umk-red);' }}">
                        {{ $feedback }}
                    </div>
                @endif

                <div x-show="!checking">
                    @if ($method === 'scan')
                        <div class="viewfinder mb-4" x-init="startCamera()">
                            <div id="qr-reader" style="width:100%; height:100%;"></div>
                            <template x-if="!cameraOn">
                                <div>
                                    <div class="vf-corner" style="top:14px; left:14px; border-top:3px solid var(--umk-gold); border-left:3px solid var(--umk-gold); border-radius:8px 0 0 0;"></div>
                                    <div class="vf-corner" style="top:14px; right:14px; border-top:3px solid var(--umk-gold); border-right:3px solid var(--umk-gold); border-radius:0 8px 0 0;"></div>
                                    <div class="vf-corner" style="bottom:14px; left:14px; border-bottom:3px solid var(--umk-gold); border-left:3px solid var(--umk-gold); border-radius:0 0 0 8px;"></div>
                                    <div class="vf-corner" style="bottom:14px; right:14px; border-bottom:3px solid var(--umk-gold); border-right:3px solid var(--umk-gold); border-radius:0 0 8px 0;"></div>
                                    <div class="absolute inset-0 flex items-center justify-center" style="pointer-events:none;"><p class="text-xs" style="color:#7c9c85;">Arahkan kamera ke QR Code</p></div>
                                </div>
                            </template>
                        </div>
                        <template x-if="cameraError">
                            <p class="text-xs text-center mb-3" style="color:var(--umk-red);">Kamera tidak bisa diakses. Pastikan izin kamera sudah diberikan di pengaturan browser, atau gunakan token manual di bawah.</p>
                        </template>
                        <button class="btn btn-primary w-full mb-3" @click="cameraOn ? stopCamera() : startCamera()" :disabled="starting">
                            <span x-text="starting ? 'Meminta izin kamera...' : (cameraOn ? 'Berhenti scan' : 'Mulai scan')"></span>
                        </button>
                        <p class="text-xs text-center mb-3" style="color:var(--ink-soft);">atau masukkan token manual</p>
                        <div class="flex gap-2">
                            <input class="field-input flex-1" wire:model="manualToken" placeholder="Kode token">
                            <button type="button" class="btn btn-primary" style="padding:8px 16px;" @click="submitManualToken()">Kirim</button>
                        </div>
                    @else
                        <div class="flex flex-col items-center text-center">
                            <div class="flex items-center justify-center p-3 mb-3" style="background:#fff; border:1px solid var(--line); border-radius:12px;"
                                 wire:ignore wire:poll.20s
                                 x-effect="renderSelfQr($refs.selfQrWrap, $wire.selfQrPayload)">
                                <div x-ref="selfQrWrap"></div>
                            </div>
                            <p class="text-xs" style="color:var(--ink-soft);">Tunjukkan QR ini ke panitia untuk dipindai.</p>
                        </div>
                    @endif
                </div>

                <div x-show="checking" x-cloak class="py-3">
                    <template x-for="(step, idx) in steps" :key="idx">
                        <div class="checklist-item" :class="idx < stepIndex ? 'done' : (idx === stepIndex && stepFailed ? 'fail' : '')">
                            <div class="checklist-dot">
                                <i class="ti ti-check" x-show="idx < stepIndex"></i>
                                <i class="ti ti-x" x-show="idx === stepIndex && stepFailed"></i>
                            </div>
                            <span x-text="step"></span>
                        </div>
                    </template>
                </div>
            </div>
        @endif
    @else
        <p class="text-sm" style="color:var(--ink-soft);">Belum ada kegiatan.</p>
    @endif
</div>

@script
<script>
    window.renderSelfQr = window.renderSelfQr || function (wrap, payload) {
        if (!wrap || !payload) return;
        wrap.innerHTML = '';
        new QRCode(wrap, { text: payload, width: 180, height: 180, colorDark: '#122016', colorLight: '#ffffff' });
    };

    Alpine.data('presensiPanel', () => ({
        cameraOn: false,
        cameraError: false,
        starting: false,
        checking: false,
        stepFailed: false,
        stepIndex: -1,
        steps: ['Memindai QR Code', 'Memvalidasi token', 'Memeriksa kegiatan aktif', 'Memeriksa duplikasi presensi', 'Menyimpan presensi'],
        html5Qr: null,

        init() {
            console.log('[presensi] presensiPanel() Alpine component mounted, method =', this.$wire.method);
        },
        startCamera() {
            console.log('[presensi] startCamera() called', { cameraOn: this.cameraOn, starting: this.starting, hasHtml5Qrcode: typeof window.Html5Qrcode });
            if (this.cameraOn || this.starting) return;
            if (typeof window.Html5Qrcode === 'undefined') {
                console.error('[presensi] Html5Qrcode library not loaded from CDN.');
                this.cameraError = true;
                return;
            }
            this.starting = true;
            this.cameraError = false;
            this.$nextTick(() => {
                console.log('[presensi] creating Html5Qrcode instance and calling start()...');
                this.html5Qr = new Html5Qrcode('qr-reader');
                this.html5Qr.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: 220 },
                    (decodedText) => {
                        this.stopCamera();
                        this.runCheckin(decodedText);
                    },
                    () => {}
                ).then(() => {
                    console.log('[presensi] camera started successfully.');
                    this.cameraOn = true;
                    this.starting = false;
                }).catch((err) => {
                    // Permission denied, no camera available, or insecure (non-HTTPS/localhost) context.
                    console.error('[presensi] camera start() failed:', err);
                    this.cameraOn = false;
                    this.starting = false;
                    this.cameraError = true;
                });
            });
        },
        stopCamera() {
            this.cameraOn = false;
            this.starting = false;
            if (this.html5Qr) {
                const instance = this.html5Qr;
                this.html5Qr = null;
                instance.stop().then(() => instance.clear()).catch(() => {});
            }
        },
        destroy() {
            this.stopCamera();
        },
        submitManualToken() {
            const token = (this.$wire.manualToken || '').trim();
            if (!token) return;
            this.runCheckin(token);
        },
        async runCheckin(payload) {
            this.checking = true;
            this.stepFailed = false;
            this.stepIndex = 0;
            for (let i = 1; i < this.steps.length - 1; i++) {
                await new Promise((r) => setTimeout(r, 350));
                this.stepIndex = i;
            }
            await this.$wire.checkinWithPayload(payload);
            this.$wire.manualToken = '';
            this.stepIndex = this.steps.length - 1;
            this.stepFailed = this.$wire.feedbackType === 'error';
            await new Promise((r) => setTimeout(r, 500));
            this.checking = false;
            this.stepIndex = -1;
        },
    }));
</script>
@endscript
