<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .subtitle {
            font-size: 14px;
            margin-bottom: 15px;
        }
        .info {
            margin-bottom: 15px;
        }
        .info-item {
            margin-bottom: 5px;
        }
        .content {
            margin-bottom: 20px;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">LAPORAN {{ strtoupper(str_replace('_', ' ', $laporan->jenis_laporan)) }}</div>
        <div class="subtitle">{{ $laporan->periode }}</div>
    </div>
    
    <div class="info">
        <div class="info-item"><strong>Kode Laporan:</strong> {{ $laporan->kode_laporan }}</div>
        <div class="info-item"><strong>Jenis Laporan:</strong> {{ ucwords(str_replace('_', ' ', $laporan->jenis_laporan)) }}</div>
        <div class="info-item"><strong>Periode:</strong> {{ $laporan->periode }}</div>
        <div class="info-item"><strong>Dibuat Pada:</strong> {{ $laporan->created_at->format('d M Y H:i') }}</div>
        <div class="info-item"><strong>Dibuat Oleh:</strong> {{ $laporan->user->name }}</div>
        @if($laporan->catatan)
        <div class="info-item"><strong>Catatan:</strong> {{ $laporan->catatan }}</div>
        @endif
    </div>
    
    <div class="content">
        <p>Detail laporan tidak tersedia untuk jenis laporan ini.</p>
    </div>
    
    <div class="footer">
        <p>Dicetak pada: {{ date('d M Y H:i:s') }}</p>
    </div>
</body>
</html>