<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class QrModal extends Component
{
    public ?int $eventId = null;

    public bool $show = false;

    public ?string $currentToken = null;

    public ?string $qrPayload = null;

    #[On('qr-open')]
    public function open(int $eventId): void
    {
        $this->eventId = $eventId;
        $this->show = true;
        $this->ensureFreshToken();
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function tick(): void
    {
        if (! $this->show) {
            return;
        }

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

    private function ensureFreshToken(): void
    {
        $event = $this->event;

        if (! $event) {
            return;
        }

        if (! $event->current_token || $this->remainingSeconds <= 0) {
            $event->rotateToken();
        }

        $this->currentToken = $event->current_token;
        $this->qrPayload = "EVT:{$event->id}:{$event->current_token}";
    }

    public function render(): View
    {
        return view('livewire.admin.qr-modal');
    }
}
