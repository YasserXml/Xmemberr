<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Data Barang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
            padding: 0;
        }
        .header {
            margin-bottom: 30px;
        }
        .header h1 {
            text-align: center;
            font-size: 18px;
            margin-bottom: 5px;
        }
        .header p {
            text-align: center;
            font-size: 14px;
            margin-top: 0;
        }
        .company-info {
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .info-table td {
            vertical-align: top;
            padding: 3px;
        }
        .label {
            font-weight: bold;
            width: 120px;
        }
        .separator {
            border-top: 1px solid #ddd;
            margin: 15px 0;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.items th, table.items td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }
        table.items th {
            background-color: #f2f2f2;
        }
        .total-table {
            width: 100%;
            margin-top: 20px;
        }
        .total-table td {
            padding: 5px;
        }
        .total-label {
            text-align: right;
            font-weight: bold;
        }
        .total-value {
            text-align: right;
            min-width: 120px;
        }
        .catatan {
            margin-top: 20px;
            padding: 10px;
            border-top: 1px solid #ddd;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px dashed #ddd;
            padding-top: 10px;
        }
        .page-break {
            page-break-after: always;
        }
        .terima-kasih {
            text-align: center;
            margin-top: 30px;
            font-style: italic;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title ?? 'LAPORAN DATA BARANG' }}</h1>
        <p>PT. XMEMBER INDONESIA</p>
    </div>
    
    <div class="company-info">
        <table class="info-table">
            <tr>
                <td class="label">Tanggal:</td>
                <td>{{ $tanggal }}</td>
                <td class="label">No. Dokumen:</td>
                <td>BRG-{{ now()->format('Ymd') }}-{{ random_int(1000, 9999) }}</td>
            </tr>
            <tr>
                <td class="label">Filter:</td>
                <td>{{ isset($filterKategori) ? $filterKategori : 'Semua Kategori' }}</td>
                <td class="label">Jumlah Item:</td>
                <td>{{ count($barang) }}</td>
            </tr>
        </table>
    </div>
    
    <div class="separator"></div>
    
    <div class="detail-item">
        <table class="items">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    <th>Satuan</th>
                    <th>Harga Beli</th>
                    <th>Harga Jual</th>
                </tr>
            </thead>
            <tbody>
                @forelse($barang as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->kode_barang }}</td>
                    <td>{{ $item->nama_barang }}</td>
                    <td>{{ $item->kategori->nama_kategori ?? '-' }}</td>
                    <td>{{ $item->stok }}</td>
                    <td>{{ $item->satuan }}</td>
                    <td>Rp {{ number_format($item->harga_beli, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($item->harga_jual, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center;">Tidak ada data barang</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="total-info">
        <table class="total-table" style="width: 40%; margin-left: auto;">
            <tr>
                <td class="total-label">Total Item:</td>
                <td class="total-value">{{ count($barang) }}</td>
            </tr>
            <tr>
                <td class="total-label">Total Nilai Stok:</td>
                <td class="total-value">Rp {{ number_format($barang->sum(function($item) { return $item->stok * $item->harga_beli; }), 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>
    
    @if(isset($catatan))
    <div class="catatan">
        <strong>CATATAN:</strong>
        <p>{{ $catatan }}</p>
    </div>
    @endif
    
    <div class="terima-kasih">
        Laporan ini dihasilkan secara otomatis oleh sistem.
    </div>
    
    @if(!isset($showFooter) || $showFooter)
    <div class="footer">
        <p>© {{ date('Y') }} - Sistem Manajemen Inventaris</p>
    </div>
    @endif
</body>
</html>