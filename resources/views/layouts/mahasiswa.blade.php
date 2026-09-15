<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Beranda' }} &middot; Presensi PKKMB UM Kendari</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabler-icons/3.46.0/tabler-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{-- Loaded before @livewireScripts (below) so QRCode/Html5Qrcode already exist
         once Livewire boots Alpine and starts running x-init/@script handlers. --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
</head>
<body class="mahasiswa-shell">

<div class="ms-app">
    <main class="ms-content">
        {{ $slot }}
    </main>

    @php
        $navItems = [
            ['route' => 'mahasiswa.beranda', 'icon' => 'ti-home', 'label' => 'Beranda'],
            ['route' => 'mahasiswa.kegiatan', 'icon' => 'ti-calendar-event', 'label' => 'Kegiatan'],
            ['route' => 'mahasiswa.riwayat', 'icon' => 'ti-history', 'label' => 'Riwayat'],
            ['route' => 'mahasiswa.profil', 'icon' => 'ti-user', 'label' => 'Profil'],
        ];
    @endphp
    <nav class="ms-navbar">
        <a href="{{ route('mahasiswa.presensi') }}" wire:navigate class="navcenter"><i class="ti ti-qrcode"></i></a>
        <div class="ms-navrow">
            <a href="{{ route($navItems[0]['route']) }}" wire:navigate class="navitem {{ request()->routeIs($navItems[0]['route']) ? 'active' : '' }}">
                <i class="ti {{ $navItems[0]['icon'] }}"></i><span>{{ $navItems[0]['label'] }}</span>
            </a>
            <a href="{{ route($navItems[1]['route']) }}" wire:navigate class="navitem {{ request()->routeIs($navItems[1]['route']) ? 'active' : '' }}">
                <i class="ti {{ $navItems[1]['icon'] }}"></i><span>{{ $navItems[1]['label'] }}</span>
            </a>
            <div style="flex:1;"></div>
            <a href="{{ route($navItems[2]['route']) }}" wire:navigate class="navitem {{ request()->routeIs($navItems[2]['route']) ? 'active' : '' }}">
                <i class="ti {{ $navItems[2]['icon'] }}"></i><span>{{ $navItems[2]['label'] }}</span>
            </a>
            <a href="{{ route($navItems[3]['route']) }}" wire:navigate class="navitem {{ request()->routeIs($navItems[3]['route']) ? 'active' : '' }}">
                <i class="ti {{ $navItems[3]['icon'] }}"></i><span>{{ $navItems[3]['label'] }}</span>
            </a>
        </div>
    </nav>
</div>

@livewireScripts
</body>
</html>
