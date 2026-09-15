<?php

namespace App\Livewire\Mahasiswa;

use App\Livewire\Actions\Logout;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LogoutButton extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.mahasiswa.logout-button');
    }
}
