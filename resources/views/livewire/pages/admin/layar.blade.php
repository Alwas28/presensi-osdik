<?php

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.display', ['title' => 'Layar Presensi'])] class extends Component
{
    #[Url(as: 'event')]
    public ?int $eventId = null;

    public ?string $qrPayload = null;

    public function mount(): void
    {
        $this->eventId = $this->eventId ?: Event::query()->where('status', 'aktif')->value('id');
        $this->ensureFreshToken();
    }

    #[Computed]
    public function event(): ?Event
    {
        return $this->eventId ? Event::find($this->eventId) : null;
    }

    #[Computed]
    public function remainingSeconds(): int
    {
        $event = $this->event;

        if (! $event?->token_generated_at) {
            return 0;
        }

        return max(0, 60 - $event->token_generated_at->diffInSeconds(now(), true));
    }

    public function tick(): void
    {
        // Follow whichever event the panitia has open, in case it changes while this screen is up.
        $this->eventId = Event::query()->where('status', 'aktif')->value('id') ?? $this->eventId;
        $this->ensureFreshToken();
    }

    /**
     * Mirrors Admin\QrModal::ensureFreshToken() — qrPayload is a plain property
     * set here explicitly instead of a #[Computed] read only from Alpine, since
     * Livewire won't reliably push an untouched Computed value to the client on
     * every wire:poll tick.
     */
    private function ensureFreshToken(): void
    {
        $event = $this->event;

        if (! $event || $event->status !== 'aktif') {
            $this->qrPayload = null;

            return;
        }

        // Compute staleness directly instead of via the memoized remainingSeconds
        // computed property, so a fresh rotation here is reflected immediately
        // when the Blade view reads remainingSeconds later in this same request.
        $remaining = $event->token_generated_at
            ? max(0, 60 - $event->token_generated_at->diffInSeconds(now(), true))
            : 0;

        if (! $event->current_token || $remaining <= 0) {
            $event->rotateToken();
        }

        $this->qrPayload = "EVT:{$event->id}:{$event->current_token}";
    }
}; ?>

<div class="layar-grid" wire:poll.1000ms="tick">
    <div class="layar-info">
        <span class="layar-pill">Osdik 2026 &middot; UM Kendari</span>
        <h1 class="display font-extrabold mt-5 mb-2" style="font-size:38px; line-height:1.15;">Cara Presensi<br>dengan QR Code</h1>
        @if ($this->event)
            <p class="mb-8" style="font-size:18px; color:#cfe4d4;">{{ $this->event->nama }} &middot; {{ \Illuminate\Support\Str::substr($this->event->jam_mulai, 0, 5) }}&ndash;{{ \Illuminate\Support\Str::substr($this->event->jam_selesai, 0, 5) }}</p>
        @endif

        <div>
            <div class="layar-step">
                <div class="layar-step-num">1</div>
                <p style="font-size:19px; padding-top:8px;">Buka aplikasi <strong>Presensi Osdik</strong> di HP kamu, lalu login pakai NIM.</p>
            </div>
            <div class="layar-step">
                <div class="layar-step-num">2</div>
                <p style="font-size:19px; padding-top:8px;">Tap tombol <strong>QR hijau</strong> di menu bawah aplikasi.</p>
            </div>
            <div class="layar-step">
                <div class="layar-step-num">3</div>
                <p style="font-size:19px; padding-top:8px;">Izinkan akses kamera saat diminta oleh browser.</p>
            </div>
            <div class="layar-step">
                <div class="layar-step-num">4</div>
                <p style="font-size:19px; padding-top:8px;">Arahkan kamera HP ke QR Code di sebelah kanan layar ini.</p>
            </div>
            <div class="layar-step">
                <div class="layar-step-num">5</div>
                <p style="font-size:19px; padding-top:8px;">Tunggu sampai muncul tanda <strong>&ldquo;Presensi berhasil&rdquo;</strong> di HP kamu.</p>
            </div>
        </div>
    </div>

    <div class="layar-qr-side">
        @if (! $this->event)
            <i class="ti ti-calendar-off" style="font-size:64px; color:#c9d3cb;"></i>
            <p class="display font-bold text-xl mt-4" style="color:#5b665d;">Belum ada kegiatan aktif</p>
        @elseif ($this->event->status !== 'aktif')
            <i class="ti ti-lock" style="font-size:64px; color:#c9a600;"></i>
            <p class="display font-bold text-xl mt-4 text-center" style="color:#5b665d;">Presensi untuk<br>&ldquo;{{ $this->event->nama }}&rdquo;<br>belum dibuka</p>
        @else
            <div class="layar-qr-frame" wire:ignore x-data x-effect="renderLayarQr($refs.qrWrap, $wire.qrPayload)">
                <div x-ref="qrWrap"></div>
            </div>
            <p class="mt-7" style="font-size:16px; color:#5b665d;">QR berganti otomatis tiap <span class="font-bold">60 detik</span> &middot; {{ $this->remainingSeconds }} detik lagi</p>
        @endif
    </div>
</div>

@script
<script>
    window.renderLayarQr = window.renderLayarQr || function (wrap, payload) {
        if (!wrap || !payload) return;
        wrap.innerHTML = '';
        new QRCode(wrap, { text: payload, width: 440, height: 440, colorDark: '#122016', colorLight: '#ffffff' });
    };
</script>
@endscript
