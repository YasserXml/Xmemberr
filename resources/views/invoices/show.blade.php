<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .header {
            border-bottom: 2px solid #efefef;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .company-info {
            text-align: right;
        }
        .invoice-details {
            margin-bottom: 30px;
        }
        .customer-details {
            margin-bottom: 30px;
        }
        .table-items {
            margin-bottom: 30px;
        }
        .table-totals {
            width: 100%;
            max-width: 400px;
            margin-left: auto;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 0.9em;
            color: #6c757d;
        }
        .actions {
            margin-top: 30px;
            text-align: center;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-lunas {
            background-color: #d4edda;
            color: #155724;
        }
        .status-belum-lunas, .status-belum-bayar {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-sebagian {
            background-color: #fff3cd;
            color: #856404;
        }
        /* Remove URL address from the footer when printing */
        @page {
            margin-bottom: 0;
            margin-top: 0;
        }
        @media print {
            body {
                background-color: #fff;
                padding: 0;
            }
            .invoice-container {
                box-shadow: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            /* Hide the URL at the bottom */
            html {
                height: 100%;
                overflow: hidden;
            }
        }
        /* Hide URL in footer */
        .pdf-footer {
            display: none !important;
        }
        /* Center the Jumlah column text */
        .text-center {
            text-align: center !important;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header row">
            <div class="col-6">
                <h1>INVOICE</h1>
                <h5>{{ config('app.name', 'Toko Saya') }}</h5>
            </div>
            <div class="col-6 company-info">
                <p>
                    JL Kenangan kami RT04/19 Kel PayungTeduh<br>
                    Cimahi, 4000<br>
                    Telp: (123) 456-7890<br>
                    Email: info@tokosaya.com
                </p>
            </div>
        </div>

        <div class="row invoice-details">
            <div class="col-6">
                <h6>DETAIL INVOICE</h6>
                <table>
                    <tr>
                        <td><strong>No. Transaksi:</strong></td>
                        <td>{{ $transaksi->no_transaksi }}</td>
                    </tr>
                    <tr>
                        <td><strong>Tanggal:</strong></td>
                        <td>{{ \Carbon\Carbon::parse($transaksi->tanggal_transaksi)->translatedFormat('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Kasir:</strong></td>
                        <td>{{ $transaksi->user->name }}</td>
                    </tr>
                    @if($transaksi->faktur)
                    <tr>
                        <td><strong>No. Faktur:</strong></td>
                        <td>{{ $transaksi->faktur->no_faktur }}</td>
                    </tr>
                    @endif
                </table>
            </div>
            <div class="col-6 text-end">
                <h6>STATUS PEMBAYARAN</h6>
                <span class="status-badge status-{{ strtolower(str_replace('_', '-', $transaksi->status_pembayaran)) }}">
                    {{ ucwords(str_replace('_', ' ', $transaksi->status_pembayaran)) }}
                </span>
                <p class="mt-2">
                    <strong>Metode Pembayaran:</strong><br>
                    {{ ucwords(str_replace('_', ' ', $transaksi->metode_pembayaran)) }}
                </p>
            </div>
        </div>

        <div class="customer-details">
            <h6>CUSTOMER</h6>
            @if($transaksi->member)
                <p>
                    <strong>{{ $transaksi->member->nama_member }}</strong> ({{ $transaksi->member->kode_member }})<br>
                    @if($transaksi->member->telepon)
                    Telp: {{ $transaksi->member->telepon }}<br>
                    @endif
                    @if($transaksi->member->email)
                    Email: {{ $transaksi->member->email }}<br>
                    @endif
                    @if($transaksi->member->alamat)
                    Alamat: {{ $transaksi->member->alamat }}
                    @endif
                </p>
            @else
                <p>Pelanggan Umum (Non-Member)</p>
            @endif
        </div>

        <div class="table-items">
            <h6>DETAIL ITEM</h6>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th class="text-end">Harga</th>
                        <th class="text-center">Jumlah</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $items = is_string($transaksi->items) ? json_decode($transaksi->items, true) : $transaksi->items; 
                    @endphp
                    @foreach($items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item['kode_barang'] ?? '-' }}</td>
                        <td>{{ $item['nama_barang'] ?? '-' }}</td>
                        <td class="text-end">
                            @php
                                // Try to get saved price, or fetch from the product if not available
                                $harga = $item['harga'] ?? null;
                                if (!$harga && isset($item['barang_id'])) {
                                    $barang = \App\Models\Barang::find($item['barang_id']);
                                    $harga = $barang ? $barang->harga_jual : 0;
                                }
                            @endphp
                            Rp {{ number_format($harga, 0, ',', '.') }}
                        </td>
                        <td class="text-center">{{ $item['jumlah'] ?? '0' }}</td>
                        <td class="text-end">
                            @php
                                // Calculate subtotal
                                $jumlah = $item['jumlah'] ?? 0;
                                $subtotal = $item['subtotal'] ?? ($harga * $jumlah);
                            @endphp
                            Rp {{ number_format($subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="row">
            <div class="col-6">
                @if($transaksi->faktur && $transaksi->faktur->keterangan)
                <div class="mb-4">
                    <h6>CATATAN</h6>
                    <p>{{ $transaksi->faktur->keterangan }}</p>
                </div>
                @endif
            </div>
            <div class="col-6">
                <table class="table-totals table">
                    <tr>
                        <td><strong>Total Harga:</strong></td>
                        <td class="text-end">Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Total Bayar:</strong></td>
                        <td class="text-end">Rp {{ number_format($transaksi->total_bayar, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Kembalian:</strong></td>
                        <td class="text-end">Rp {{ number_format($transaksi->kembalian, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="footer">
            <p>Terima kasih telah berbelanja di {{ config('app.name', 'Toko Saya') }}.</p>
            <p>Invoice ini sah dan diproses oleh komputer.</p>
        </div>

        <div class="actions no-print">
            <button class="btn btn-primary" onclick="window.print()">Cetak Invoice</button>
            <a href="{{ route('filament.admin.resources.transaksi.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>