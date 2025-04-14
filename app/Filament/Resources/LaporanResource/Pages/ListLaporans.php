<?php

namespace App\Filament\Resources\LaporanResource\Pages;

use App\Filament\Resources\LaporanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Transaksi;
use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class ListLaporans extends ListRecords
{
    protected static string $resource = LaporanResource::class;

    protected function getHeaderActions(): array
    {
        return [    
            Actions\Action::make('generateLaporanTransaksi')
                ->label('Buat Laporan Transaksi')
                ->icon('heroicon-o-document-chart-bar')
                ->action(function (array $data): void {
                    $this->generateLaporanTransaksi($data);
                })
                ->form([
                    \Filament\Forms\Components\DatePicker::make('tanggal_mulai')
                        ->label('Tanggal Mulai')
                        ->required()
                        ->native(false),
                    \Filament\Forms\Components\DatePicker::make('tanggal_akhir')
                        ->label('Tanggal Akhir')
                        ->required()
                        ->native(false)
                        ->after('tanggal_mulai'),
                    \Filament\Forms\Components\TextInput::make('catatan')
                        ->label('Catatan')
                        ->maxLength(255),
                ]),
            Actions\Action::make('generateLaporanBarang')
                ->label('Buat Laporan Barang')
                ->icon('heroicon-o-cube')
                ->action(function (array $data): void {
                    $this->generateLaporanBarang($data);
                })
                ->form([
                    \Filament\Forms\Components\TextInput::make('catatan')
                        ->label('Catatan')
                        ->maxLength(255),
                ]),
            Actions\Action::make('generateLaporanBarangMasuk')
                ->label('Buat Laporan Barang Masuk')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function (array $data): void {
                    $this->generateLaporanBarangMasuk($data);
                })
                ->form([
                    \Filament\Forms\Components\DatePicker::make('tanggal_mulai')
                        ->label('Tanggal Mulai')
                        ->required()
                        ->native(false),
                    \Filament\Forms\Components\DatePicker::make('tanggal_akhir')
                        ->label('Tanggal Akhir')
                        ->required()
                        ->native(false)
                        ->after('tanggal_mulai'),
                    \Filament\Forms\Components\TextInput::make('catatan')
                        ->label('Catatan')
                        ->maxLength(255),
                ]),
            Actions\Action::make('generateLaporanBarangKeluar')
                ->label('Buat Laporan Barang Keluar')
                ->icon('heroicon-o-arrow-up-tray')
                ->action(function (array $data): void {
                    $this->generateLaporanBarangKeluar($data);
                })
                ->form([
                    \Filament\Forms\Components\DatePicker::make('tanggal_mulai')
                        ->label('Tanggal Mulai')
                        ->required()
                        ->native(false),
                    \Filament\Forms\Components\DatePicker::make('tanggal_akhir')
                        ->label('Tanggal Akhir')
                        ->required()
                        ->native(false)
                        ->after('tanggal_mulai'),
                    \Filament\Forms\Components\TextInput::make('catatan')
                        ->label('Catatan')
                        ->maxLength(255),
                ]),
        ];
    }

    private function generateLaporanTransaksi(array $data): void
    {
        $tanggalMulai = $data['tanggal_mulai'];
        $tanggalAkhir = $data['tanggal_akhir'];
        
        $transaksis = Transaksi::whereBetween('tanggal_transaksi', [$tanggalMulai, $tanggalAkhir])
            ->get()
            ->map(function ($transaksi) {
                return [
                    'id' => $transaksi->id,
                    'no_transaksi' => $transaksi->no_transaksi,
                    'member' => $transaksi->member ? $transaksi->member->nama : 'Non-Member',
                    'tanggal_transaksi' => $transaksi->tanggal_transaksi,
                    'total_harga' => $transaksi->total_harga,
                    'total_bayar' => $transaksi->total_bayar,
                    'kembalian' => $transaksi->kembalian,
                    'status_pembayaran' => $transaksi->status_pembayaran,
                    'metode_pembayaran' => $transaksi->metode_pembayaran,
                    'items' => $transaksi->items,
                ];
            })
            ->toArray();
        
        $totalPendapatan = array_sum(array_column($transaksis, 'total_harga'));
        
        // Membuat kode laporan
        $kode = 'LT-' . date('Ymd') . '-' . Str::random(5);
        
        // Menyimpan laporan ke database
        \App\Models\Laporan::create([
            'kode_laporan' => $kode,
            'jenis_laporan' => 'transaksi',
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_akhir' => $tanggalAkhir,
            'periode' => date('d M Y', strtotime($tanggalMulai)) . ' - ' . date('d M Y', strtotime($tanggalAkhir)),
            'data_laporan' => [
                'transaksis' => $transaksis,
                'total_transaksi' => count($transaksis),
                'total_pendapatan' => $totalPendapatan,
            ],
            'catatan' => $data['catatan'] ?? null,
            'user_id' => filament()->auth()->id(),
        ]);
        
        Notification::make()
            ->title('Laporan transaksi berhasil dibuat')
            ->success()
            ->send();
    }
    
    private function generateLaporanBarang(array $data): void
    {
        $barangs = Barang::with('kategori')
            ->get()
            ->map(function ($barang) {
                return [
                    'id' => $barang->id,
                    'kode_barang' => $barang->kode_barang,
                    'nama_barang' => $barang->nama_barang,
                    'harga_beli' => $barang->harga_beli,
                    'harga_jual' => $barang->harga_jual,
                    'stok' => $barang->stok,
                    'stok_minimum' => $barang->stok_minimum,
                    'satuan' => $barang->satuan,
                    'kategori' => $barang->kategori ? $barang->kategori->nama_kategori : 'Tanpa Kategori',
                ];
            })
            ->toArray();
        
        $totalNilaiPersediaan = array_sum(array_map(function ($barang) {
            return $barang['harga_beli'] * $barang['stok'];
        }, $barangs));
        
        $totalNilaiPotensial = array_sum(array_map(function ($barang) {
            return $barang['harga_jual'] * $barang['stok'];
        }, $barangs));
        
        $stokDibawahMinimum = array_filter($barangs, function ($barang) {
            return $barang['stok'] < $barang['stok_minimum'];
        });
        
        // Membuat kode laporan
        $kode = 'LB-' . date('Ymd') . '-' . Str::random(5);
        
        // Menyimpan laporan ke database
        \App\Models\Laporan::create([
            'kode_laporan' => $kode,
            'jenis_laporan' => 'barang',
            'tanggal_mulai' => now(),
            'tanggal_akhir' => now(),
            'periode' => 'Per ' . date('d M Y'),
            'data_laporan' => [
                'barangs' => $barangs,
                'total_barang' => count($barangs),
                'total_nilai_persediaan' => $totalNilaiPersediaan,
                'total_nilai_potensial' => $totalNilaiPotensial,
                'potensi_keuntungan' => $totalNilaiPotensial - $totalNilaiPersediaan,
                'stok_dibawah_minimum' => $stokDibawahMinimum,
            ],
            'catatan' => $data['catatan'] ?? null,
            'user_id' => filament()->auth()->id(),
        ]);
        
        Notification::make()
            ->title('Laporan barang berhasil dibuat')
            ->success()
            ->send();
    }
    
    private function generateLaporanBarangMasuk(array $data): void
    {
        $tanggalMulai = $data['tanggal_mulai'];
        $tanggalAkhir = $data['tanggal_akhir'];
        
        $barangMasuks = BarangMasuk::with(['barang', 'kategori', 'user'])
            ->whereBetween('tanggal_masuk_barang', [$tanggalMulai, $tanggalAkhir])
            ->get()
            ->map(function ($barangMasuk) {
                return [
                    'id' => $barangMasuk->id,
                    'no_referensi' => $barangMasuk->no_referensi,
                    'barang' => [
                        'id' => $barangMasuk->barang->id,
                        'kode_barang' => $barangMasuk->barang->kode_barang,
                        'nama_barang' => $barangMasuk->barang->nama_barang,
                    ],
                    'jumlah_barang_masuk' => $barangMasuk->jumlah_barang_masuk,
                    'harga_beli' => $barangMasuk->harga_beli,
                    'total_harga' => $barangMasuk->total_harga,
                    'tanggal_masuk_barang' => $barangMasuk->tanggal_masuk_barang,
                    'kategori' => $barangMasuk->kategori ? $barangMasuk->kategori->nama_kategori : 'Tanpa Kategori',
                    'petugas' => $barangMasuk->user->name,
                ];
            })
            ->toArray();
        
        $totalPengeluaran = array_sum(array_column($barangMasuks, 'total_harga'));
        
        // Membuat kode laporan
        $kode = 'LBM-' . date('Ymd') . '-' . Str::random(5);
        
        // Menyimpan laporan ke database
        \App\Models\Laporan::create([
            'kode_laporan' => $kode,
            'jenis_laporan' => 'barang_masuk',
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_akhir' => $tanggalAkhir,
            'periode' => date('d M Y', strtotime($tanggalMulai)) . ' - ' . date('d M Y', strtotime($tanggalAkhir)),
            'data_laporan' => [
                'barang_masuks' => $barangMasuks,
                'total_barang_masuk' => count($barangMasuks),
                'total_pengeluaran' => $totalPengeluaran,
            ],
            'catatan' => $data['catatan'] ?? null,
            'user_id' => filament()->auth()->id(),
        ]);
        
        Notification::make()
            ->title('Laporan barang masuk berhasil dibuat')
            ->success()
            ->send();
    }
    
    private function generateLaporanBarangKeluar(array $data): void
    {
        $tanggalMulai = $data['tanggal_mulai'];
        $tanggalAkhir = $data['tanggal_akhir'];
        
        $barangKeluars = BarangKeluar::with(['barang', 'transaksi', 'user'])
            ->whereBetween('tanggal_keluar', [$tanggalMulai, $tanggalAkhir])
            ->get()
            ->map(function ($barangKeluar) {
                return [
                    'id' => $barangKeluar->id,
                    'no_referensi' => $barangKeluar->no_referensi,
                    'barang' => [
                        'id' => $barangKeluar->barang->id,
                        'kode_barang' => $barangKeluar->barang->kode_barang,
                        'nama_barang' => $barangKeluar->barang->nama_barang,
                    ],
                    'transaksi' => [
                        'id' => $barangKeluar->transaksi->id,
                        'no_transaksi' => $barangKeluar->transaksi->no_transaksi,
                    ],
                    'jumlah_barang_keluar' => $barangKeluar->jumlah_barang_keluar,
                    'harga_jual' => $barangKeluar->harga_jual,
                    'total_harga' => $barangKeluar->total_harga,
                    'tanggal_keluar' => $barangKeluar->tanggal_keluar,
                    'petugas' => $barangKeluar->user->name,
                ];
            })
            ->toArray();
        
        $totalPendapatan = array_sum(array_column($barangKeluars, 'total_harga'));
        
        // Membuat kode laporan
        $kode = 'LBK-' . date('Ymd') . '-' . Str::random(5);
        
        // Menyimpan laporan ke database
        \App\Models\Laporan::create([
            'kode_laporan' => $kode,
            'jenis_laporan' => 'barang_keluar',
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_akhir' => $tanggalAkhir,
            'periode' => date('d M Y', strtotime($tanggalMulai)) . ' - ' . date('d M Y', strtotime($tanggalAkhir)),
            'data_laporan' => [
                'barang_keluars' => $barangKeluars,
                'total_barang_keluar' => count($barangKeluars),
                'total_pendapatan' => $totalPendapatan,
            ],
            'catatan' => $data['catatan'] ?? null,
            'user_id' => filament()->auth()->id(),
        ]);
        
        Notification::make()
            ->title('Laporan barang keluar berhasil dibuat')
            ->success()
            ->send();
    }
}