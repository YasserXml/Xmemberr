<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;
use App\Models\Transaksi;
use App\Models\Laporan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LaporanService
{
    /**
     * Buat laporan baru
     */
    public function createLaporan(
        string $jenisLaporan,
        string $kodeLaporan,
        string $tanggalMulai,
        string $tanggalAkhir,
        string $periode,
        ?string $catatan = null
    ): Laporan {
        // Ambil data berdasarkan jenis laporan
        $dataLaporan = $this->getDataForLaporan($jenisLaporan, $tanggalMulai, $tanggalAkhir);
        
        // Buat laporan baru
        return Laporan::create([
            'kode_laporan' => $kodeLaporan,
            'jenis_laporan' => $jenisLaporan,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_akhir' => $tanggalAkhir,
            'periode' => $periode,
            'data_laporan' => $dataLaporan,
            'catatan' => $catatan,
            'user_id' => Auth::id(),
        ]);
    }
    
    /**
     * Ambil data laporan berdasarkan jenis
     */
    public function getDataForLaporan(string $jenisLaporan, string $tanggalMulai, string $tanggalAkhir): array
    {
        switch ($jenisLaporan) {
            case 'barang':
                return $this->getBarangData();
                
            case 'barang_masuk':
                return $this->getBarangMasukData($tanggalMulai, $tanggalAkhir);
                
            case 'barang_keluar':
                return $this->getBarangKeluarData($tanggalMulai, $tanggalAkhir);
                
            case 'transaksi':
                return $this->getTransaksiData($tanggalMulai, $tanggalAkhir);
                
            default:
                return [];
        }
    }
    
    /**
     * Ambil data barang
     */
    protected function getBarangData(): array
    {
        return Barang::with('kategori')
            ->select([
                'barangs.id', 
                'kode_barang', 
                'nama_barang',
                'harga_beli',
                'harga_jual',
                'stok',
                'stok_minimum',
                'satuan',
                'kategori_id',
                DB::raw('(SELECT SUM(jumlah_barang_masuk) FROM barangmasuks WHERE barang_id = barangs.id) as total_masuk'),
                DB::raw('(SELECT SUM(jumlah_barang_keluar) FROM barangkeluars WHERE barang_id = barangs.id) as total_keluar'),
            ])
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
                    'kategori' => $barang->kategori ? $barang->kategori->nama : '-',
                    'total_masuk' => $barang->total_masuk ?: 0,
                    'total_keluar' => $barang->total_keluar ?: 0,
                ];
            })
            ->toArray();
    }
    
    /**
     * Ambil data barang masuk
     */
    protected function getBarangMasukData(string $tanggalMulai, string $tanggalAkhir): array
    {
        return BarangMasuk::with(['barang', 'user', 'kategori'])
            ->whereBetween('tanggal_masuk_barang', [$tanggalMulai, $tanggalAkhir])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'no_referensi' => $item->no_referensi,
                    'barang_id' => $item->barang_id,
                    'nama_barang' => $item->barang->nama_barang,
                    'jumlah_barang_masuk' => $item->jumlah_barang_masuk,
                    'harga_beli' => $item->harga_beli,
                    'total_harga' => $item->total_harga,
                    'tanggal_masuk_barang' => $item->tanggal_masuk_barang->format('Y-m-d'),
                    'user' => $item->user->name,
                    'kategori' => $item->kategori ? $item->kategori->nama : '-',
                ];
            })
            ->toArray();
    }
    
    /**
     * Ambil data barang keluar
     */
    protected function getBarangKeluarData(string $tanggalMulai, string $tanggalAkhir): array
    {
        return BarangKeluar::with(['barang', 'transaksi', 'user'])
            ->whereBetween('tanggal_keluar', [$tanggalMulai, $tanggalAkhir])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'no_referensi' => $item->no_referensi,
                    'jumlah_barang_keluar' => $item->jumlah_barang_keluar,
                    'harga_jual' => $item->harga_jual,
                    'total_harga' => $item->total_harga,
                    'tanggal_keluar' => $item->tanggal_keluar->format('Y-m-d'),
                    'barang_id' => $item->barang_id,
                    'nama_barang' => $item->barang->nama_barang,
                    'transaksi_id' => $item->transaksi_id,
                    'no_transaksi' => $item->transaksi->no_transaksi,
                    'user' => $item->user->name,
                ];
            })
            ->toArray();
    }
    
    /**
     * Ambil data transaksi
     */
    protected function getTransaksiData(string $tanggalMulai, string $tanggalAkhir): array
    {
        return Transaksi::with(['member', 'user'])
            ->whereBetween('tanggal_transaksi', [$tanggalMulai, $tanggalAkhir])
            ->get()
            ->map(function ($item) {
                $items = json_decode($item->items, true);
                
                return [
                    'id' => $item->id,
                    'no_transaksi' => $item->no_transaksi,
                    'member' => $item->member ? $item->member->nama : 'Umum',
                    'user' => $item->user->name,
                    'tanggal_transaksi' => $item->tanggal_transaksi->format('Y-m-d'),
                    'items' => $items,
                    'total_harga' => $item->total_harga,
                    'total_bayar' => $item->total_bayar,
                    'kembalian' => $item->kembalian,
                    'status_pembayaran' => $item->status_pembayaran,
                    'metode_pembayaran' => $item->metode_pembayaran,
                ];
            })
            ->toArray();
    }
}