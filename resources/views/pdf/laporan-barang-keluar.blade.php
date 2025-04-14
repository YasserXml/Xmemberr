<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Barang Keluar</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .summary {
            margin-top: 20px;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">LAPORAN BARANG KELUAR</div>
        <div class="subtitle">{{ $laporan->periode }}</div>
    </div>
    
    <div class="info">
        <div class="info-item"><strong>Kode Laporan:</strong> {{ $laporan->kode_laporan }}</div>
        <div class="info-item"><strong>Periode:</strong> {{ $laporan->periode }}</div>
        <div class="info-item"><strong>Dibuat Pada:</strong> {{ $laporan->created_at->format('d M Y H:i') }}</div>
        <div class="info-item"><strong>Dibuat Oleh:</strong> {{ $laporan->user->name }}</div>
        @if($laporan->catatan)
        <div class="info-item"><strong>Catatan:</strong> {{ $laporan->catatan }}</div>
        @endif
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>No. Referensi</th>
                <th>No. Transaksi</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Harga Jual</th>
                <th>Total</th>
                <th>Tanggal</th>
                <th>Petugas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['barang_keluars'] as $index => $barangKeluar)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $barangKeluar['no_referensi'] }}</td>
                <td>{{ $barangKeluar['transaksi']['no_transaksi'] }}</td>
                <td>{{ $barangKeluar['barang']['kode_barang'] }}</td>
                <td>{{ $barangKeluar['barang']['nama_barang'] }}</td>
                <td>{{ $barangKeluar['jumlah_barang_keluar'] }}</td>
                <td>Rp {{ number_format($barangKeluar['harga_jual'], 0, ',', '.') }}</td>
                <td>Rp {{ number_format($barangKeluar['total_harga'], 0, ',', '.') }}</td>
                <td>{{ date('d/m/Y', strtotime($barangKeluar['tanggal_keluar'])) }}</td>
                <td>{{ $barangKeluar['petugas'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="summary">
        <p><strong>Total Pendapatan:</strong> Rp {{ number_format($data['total_pendapatan'], 0, ',', '.') }}</p>
    </div>
    
    <div class="footer">
        <p>Dicetak pada: {{ date('d M Y H:i:s') }}</p>
    </div>
</body>
</html>