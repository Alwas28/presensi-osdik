@props(['event' => 'presensi-berhasil'])

{{--
    Deliberately plain JS instead of Alpine x-init: this toast must be armed the
    instant Livewire boots. Alpine only processes x-init once it gets around to
    this element, which can lag behind several blocking CDN <script> tags in
    <head> — by then a fast success dispatch (e.g. right after a QR scan) can
    already have fired and been missed. `livewire:init` is Livewire's own
    documented hook and fires reliably regardless of Alpine's timing.
--}}
<div id="toast-{{ $event }}" class="card p-3" style="position:fixed; top:16px; left:50%; z-index:60; width:calc(100% - 32px); max-width:360px; border-color:#bfe0c8; background:#e7f3ea; box-shadow:0 12px 24px -8px rgba(1,20,10,0.25); opacity:0; visibility:hidden; transform:translate(-50%,-16px); transition:opacity .25s ease, transform .25s ease;">
    <div class="flex items-center gap-2.5">
        <i class="ti ti-circle-check" style="color:var(--umk-green); font-size:22px; flex-shrink:0;"></i>
        <p class="text-sm font-semibold" style="color:var(--umk-green);" data-toast-message></p>
    </div>
</div>

<script>
    (function () {
        var eventName = @js($event);
        var timer = null;

        function showToast(message) {
            var el = document.getElementById('toast-' + eventName);
            if (!el) return;
            el.querySelector('[data-toast-message]').textContent = message;
            el.style.visibility = 'visible';
            el.style.opacity = '1';
            el.style.transform = 'translate(-50%, 0)';
            clearTimeout(timer);
            timer = setTimeout(function () {
                el.style.opacity = '0';
                el.style.transform = 'translate(-50%, -16px)';
                setTimeout(function () { el.style.visibility = 'hidden'; }, 250);
            }, 3500);
        }

        function arm() {
            Livewire.on(eventName, function (e) { showToast(e.message); });
        }

        // `livewire:init` only fires once per full page load. If this markup
        // arrived via a wire:navigate transition, Livewire already booted on
        // the previous page and that event has already passed — in that case
        // `window.Livewire` is already there, so arm immediately instead.
        if (window.Livewire) {
            arm();
        } else {
            document.addEventListener('livewire:init', arm);
        }
    })();
</script>
