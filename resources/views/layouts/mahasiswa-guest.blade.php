<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Masuk' }} &middot; Presensi PKKMB UM Kendari</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabler-icons/3.46.0/tabler-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="mahasiswa-shell">
    <div class="flex items-center justify-center min-h-screen p-5">
        <div class="w-full" style="max-width:360px;">
            <div class="flex flex-col items-center text-center mb-6">
                <div class="flex items-center justify-center rounded-2xl mb-3" style="width:56px;height:56px;background:var(--umk-gold);color:#3a3300;font-weight:800;font-size:16px;">UMK</div>
                <p class="display font-bold text-lg">Presensi PKKMB</p>
                <p class="text-xs" style="color:var(--ink-soft);">UM Kendari &middot; 2026</p>
            </div>

            <div class="card p-5">
                {{ $slot }}
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
