<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Barang</title>
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
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">LAPORAN STOK BARANG</div>
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
                <th>Kode</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Stok</th>
                <th>Harga Beli</th>
                <th>Harga Jual</th>
                <th>Nilai Stok</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['barangs'] as $index => $barang)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $barang['kode_barang'] }}</td>
                <td>{{ $barang['nama_barang'] }}</td>
                <td>{{ $barang['kategori'] }}</td>
                <td>{{ $barang['stok'] }}</td>
                <td>Rp {{ number_format($barang['harga_beli'], 0, ',', '.') }}</td>
                <td>Rp {{ number_format($barang['harga_jual'], 0, ',', '.') }}</td>
                <td>Rp {{ number_format($barang['harga_beli'] * $barang['stok'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="summary">
        <p><strong>Total Barang:</strong> {{ $data['total_barang'] }}</p>
        <p><strong>Total Nilai Persediaan:</strong> Rp {{ number_format($data['total_nilai_persediaan'], 0, ',', '.') }}</p>
        <p><strong>Total Nilai Potensial Penjualan:</strong> Rp {{ number_format($data['total_nilai_potensial'], 0, ',', '.') }}</p>
        <p><strong>Potensi Keuntungan:</strong> Rp {{ number_format($data['potensi_keuntungan'], 0, ',', '.') }}</p>
    </div>
    
    @if(count($data['stok_dibawah_minimum']) > 0)
    <div class="page-break"></div>
    
    <div class="header">
        <div class="title">DAFTAR BARANG DENGAN STOK DI BAWAH MINIMUM</div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Kode</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Stok</th>
                <th>Stok Minimum</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['stok_dibawah_minimum'] as $index => $barang)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $barang['kode_barang'] }}</td>
                <td>{{ $barang['nama_barang'] }}</td>
                <td>{{ $barang['kategori'] }}</td>
                <td>{{ $barang['stok'] }}</td>
                <td>{{ $barang['stok_minimum'] }}</td>
                <td>KURANG {{ $barang['stok_minimum'] - $barang['stok'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
    
    <div class="footer">
        <p>Dicetak pada: {{ date('d M Y H:i:s') }}</p>
    </div>
</body>
</html>