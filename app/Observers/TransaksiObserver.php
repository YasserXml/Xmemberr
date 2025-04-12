<?php

namespace App\Observers;

use App\Models\Transaksi;
use App\Models\Barang;
use App\Models\BarangKeluar;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class TransaksiObserver
{
    /**
     * Handle the Transaksi "created" event.
     */
    public function created(Transaksi $transaksi): void
    {
        $this->prosesStokDanBarangKeluar($transaksi);
    }

    /**
     * Handle the Transaksi "updated" event.
     */
    public function updated(Transaksi $transaksi): void
    {
        // Jika ada perubahan pada items, kita perlu memproses ulang stok
        if ($transaksi->isDirty('items')) {
            // Ambil data items lama jika tersedia
            $itemsLama = $transaksi->getOriginal('items') ?? [];
            
            // Kembalikan stok untuk item lama
            $this->kembalikanStok($itemsLama, $transaksi);
            
            // Hapus semua barang keluar terkait
            $transaksi->barangKeluars()->delete();
            
            // Proses data baru
            $this->prosesStokDanBarangKeluar($transaksi);
        }
    }

    /**
     * Handle the Transaksi "deleted" event.
     */
    public function deleted(Transaksi $transaksi): void
    {
        // Kembalikan stok untuk semua item
        $this->kembalikanStok($transaksi->items ?? [], $transaksi);
        
        // Hapus semua barang keluar terkait
        $transaksi->barangKeluars()->delete();
    }

    /**
     * Proses pengurangan stok dan pembuatan record barang keluar
     */
    private function prosesStokDanBarangKeluar(Transaksi $transaksi): void
    {
        // Tambahkan log untuk debug
        \Illuminate\Support\Facades\Log::info('Memproses transaksi', [
            'id' => $transaksi->id,
            'items' => $transaksi->items
        ]);
        
        // Pastikan transaksi memiliki items
        if (!isset($transaksi->items) || empty($transaksi->items)) {
            \Illuminate\Support\Facades\Log::warning('Transaksi tidak memiliki items', [
                'id' => $transaksi->id
            ]);
            return;
        }

        // Proses setiap item dalam transaksi
        foreach ($transaksi->items as $item) {
            // Debug item
            \Illuminate\Support\Facades\Log::info('Memproses item', $item);
            
            // Ambil data barang dari database
            $barang = Barang::find($item['barang_id']);
            
            if ($barang) {
                // Hitung jumlah yang akan dikurangi
                $jumlah = (int) $item['jumlah'];
                
                // Update stok - kurangi dengan jumlah yang terjual
                $stokLama = $barang->stok;
                $stokBaru = max(0, $barang->stok - $jumlah);
                $barang->stok = $stokBaru;
                $barang->save();
                
                \Illuminate\Support\Facades\Log::info('Stok barang diperbarui', [
                    'barang_id' => $barang->id,
                    'nama_barang' => $barang->nama_barang,
                    'stok_lama' => $stokLama,
                    'stok_baru' => $stokBaru
                ]);
                
                // Buat record di tabel barang keluar
                // Perbaikan: gunakan harga dari item (jika ada) atau harga_jual dari barang
                $hargaJual = $item['harga'] ?? $barang->harga_jual;
                
                BarangKeluar::create([
                    'no_referensi' => 'BKL-' . strtoupper(Str::random(8)),
                    'jumlah_barang_keluar' => $jumlah,
                    'harga_jual' => $hargaJual,
                    'total_harga' => $item['subtotal'] ?? ($hargaJual * $jumlah),
                    'tanggal_keluar' => $transaksi->tanggal_transaksi,
                    'barang_id' => $barang->id,
                    'transaksi_id' => $transaksi->id,
                    'user_id' => $transaksi->user_id,
                ]);
                
                \Illuminate\Support\Facades\Log::info('Barang keluar dibuat', [
                    'barang_id' => $barang->id,
                    'jumlah' => $jumlah,
                    'harga_jual' => $hargaJual
                ]);
                
                // Peringatan jika stok di bawah minimum
                if ($barang->stok <= $barang->stok_minimum) {
                    Notification::make()
                        ->title('Peringatan Stok Minimum')
                        ->body("Stok {$barang->nama_barang} sudah di bawah minimum ({$barang->stok}/{$barang->stok_minimum})")
                        ->warning()
                        ->send();
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('Barang tidak ditemukan', [
                    'barang_id' => $item['barang_id'] ?? 'tidak ada'
                ]);
            }
        }
    }

    /**
     * Kembalikan stok barang
     */
    private function kembalikanStok(array $items, Transaksi $transaksi): void
    {
        foreach ($items as $item) {
            // Ambil data barang dari database
            if (!isset($item['barang_id'])) {
                continue;
            }
            
            $barang = Barang::find($item['barang_id']);
            
            if ($barang) {
                // Kembalikan stok yang telah dikurangi
                $jumlah = (int) ($item['jumlah'] ?? 0);
                $barang->stok += $jumlah;
                $barang->save();
                
                \Illuminate\Support\Facades\Log::info('Stok barang dikembalikan', [
                    'barang_id' => $barang->id,
                    'nama_barang' => $barang->nama_barang,
                    'jumlah_dikembalikan' => $jumlah,
                    'stok_baru' => $barang->stok
                ]);
            }
        }
    }
}