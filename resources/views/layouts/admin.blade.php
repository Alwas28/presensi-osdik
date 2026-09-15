<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} &middot; Presensi PKKMB UM Kendari</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabler-icons/3.46.0/tabler-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{-- Loaded before @livewireScripts (below) so Chart.js/QRCode already exist
         once Livewire boots Alpine and starts running x-init handlers. --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.1/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
</head>
<body class="min-h-screen admin-shell">

<div class="flex min-h-screen" x-data="{ drawerOpen: false }">

    <div class="drawer-overlay md:hidden" x-show="drawerOpen" x-cloak @click="drawerOpen = false" style="display:none;"></div>

    <aside id="sidebar" class="w-60 flex-shrink-0 flex flex-col" :class="drawerOpen ? 'open' : ''" style="background:linear-gradient(180deg, var(--umk-green) 0%, var(--umk-green-dark) 100%); width:var(--sidebar-w);">
        <div class="flex items-center gap-2.5 px-5 py-5">
            <div class="flex items-center justify-center rounded-lg flex-shrink-0" style="width:34px;height:34px;background:var(--umk-gold);color:#3a3300;font-weight:800;font-size:13px;">UMK</div>
            <div class="leading-tight">
                <p class="text-white font-bold text-sm display">Presensi PKKMB</p>
                <p class="text-xs" style="color:#9fc6a9;">UM Kendari</p>
            </div>
        </div>
        <nav class="flex-1 px-3 space-y-1 mt-2">
            @php
                $navItems = [
                    ['route' => 'admin.dashboard', 'icon' => 'ti-layout-dashboard', 'label' => 'Dashboard'],
                    ['route' => 'admin.kegiatan', 'icon' => 'ti-calendar-event', 'label' => 'Kegiatan'],
                    ['route' => 'admin.monitoring', 'icon' => 'ti-activity', 'label' => 'Monitoring'],
                    ['route' => 'admin.scan-presensi', 'icon' => 'ti-scan', 'label' => 'Scan presensi'],
                    ['route' => 'admin.mahasiswa', 'icon' => 'ti-users', 'label' => 'Data mahasiswa'],
                    ['route' => 'admin.laporan', 'icon' => 'ti-file-analytics', 'label' => 'Laporan'],
                ];
            @endphp
            @foreach ($navItems as $item)
                <a href="{{ route($item['route']) }}" wire:navigate
                   class="navlink {{ request()->routeIs($item['route']) ? 'active' : '' }}">
                    <i class="ti {{ $item['icon'] }}"></i>{{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        <div class="px-4 py-4 mx-3 mb-3 rounded-xl" style="background:rgba(255,255,255,0.08);">
            <div class="flex items-center gap-2.5">
                <div class="flex items-center justify-center rounded-full flex-shrink-0" style="width:32px;height:32px;background:var(--umk-gold);color:#3a3300;font-weight:700;font-size:12px;">
                    {{ collect(explode(' ', auth()->user()->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                </div>
                <div class="leading-tight min-w-0">
                    <p class="text-white text-xs font-semibold truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs truncate" style="color:#9fc6a9;">{{ auth()->user()->role === 'super_admin' ? 'Super Admin' : 'Panitia' }}</p>
                </div>
            </div>
        </div>
    </aside>

    <div class="flex-1 min-w-0 flex flex-col">
        <header class="bg-white border-b flex items-center justify-between px-4 md:px-7 py-3.5 sticky top-0 z-30" style="border-color:var(--line);">
            <div class="flex items-center gap-3">
                <button class="md:hidden btn-ghost" style="padding:6px;" @click="drawerOpen = true"><i class="ti ti-menu-2" style="font-size:20px;"></i></button>
                <h1 class="display font-bold text-base md:text-lg">{{ $title ?? 'Dashboard' }}</h1>
            </div>
            <div class="flex items-center gap-3">
                <span class="hidden md:inline-flex text-xs px-3 py-1.5 rounded-full" style="background:#eef6f0; color:var(--umk-green); font-weight:600;">PKKMB 2026 &middot; 15&ndash;16 September</span>
                <livewire:admin.logout-button />
            </div>
        </header>

        <main class="flex-1 p-4 md:p-7">
            {{ $slot }}
        </main>
    </div>
</div>

@livewireScripts
{{ $scripts ?? '' }}
</body>
</html>
