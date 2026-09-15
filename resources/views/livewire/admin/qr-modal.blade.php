<div>
    @if ($show && $this->event)
        <div class="modal-backdrop" wire:poll.1000ms="tick">
            <div class="card p-6" style="width:340px; text-align:center;">
                <p class="text-xs font-semibold mb-1" style="color:var(--ink-soft);">Presensi</p>
                <h3 class="display font-bold text-base mb-4">{{ $this->event->nama }}</h3>
                <div class="flex items-center justify-center p-3 mb-3" style="background:#fff; border:1px solid var(--line); border-radius:12px;"
                     wire:ignore
                     x-data
                     x-effect="renderPresensiQr($refs.qrWrap, $wire.qrPayload)">
                    <div x-ref="qrWrap"></div>
                </div>
                <p class="text-xs mb-4" style="color:var(--ink-soft);">Token diperbarui dalam <span class="font-semibold" style="color:var(--umk-green);">{{ $this->remainingSeconds }}</span> detik</p>
                <button class="btn btn-ghost w-full justify-center" wire:click="close">Tutup</button>
            </div>
        </div>
    @endif
</div>

@script
<script>
    window.renderPresensiQr = window.renderPresensiQr || function (wrap, token) {
        if (!wrap || !token) return;
        wrap.innerHTML = '';
        new QRCode(wrap, { text: token, width: 180, height: 180, colorDark: '#122016', colorLight: '#ffffff' });
    };
</script>
@endscript
