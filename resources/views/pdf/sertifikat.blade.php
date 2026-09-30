<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    body { margin: 0; padding: 0; }
    .page {
        position: relative;
        width: 297mm;
        height: 210mm;
    }
    .page img.bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 297mm;
        height: 210mm;
    }
    .nomor {
        position: absolute;
        top: 13%;
        left: 0;
        width: 100%;
        text-align: center;
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 11pt;
        font-weight: bold;
        color: #4a4a4a;
        letter-spacing: 0.3pt;
    }
    .nama {
        position: absolute;
        top: 41.5%;
        left: 0;
        width: 100%;
        text-align: center;
        font-family: 'DejaVu Serif', serif;
        font-size: 27pt;
        font-weight: bold;
        text-decoration: underline;
        color: #000;
    }
</style>
</head>
<body>
    <div class="page">
        <img class="bg" src="data:image/png;base64,{{ $backgroundBase64 }}">
        <div class="nomor">Nomor: {{ $nomor }}</div>
        <div class="nama">{{ $nama }}</div>
    </div>
</body>
</html>
