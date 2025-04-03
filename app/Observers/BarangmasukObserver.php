<?php

namespace App\Observers;

use App\Models\Barang;
use App\Models\Barangmasuk;
use Illuminate\Support\Facades\Log;

class BarangmasukObserver
{
    public function created(BarangMasuk $barangmasuk): void
    {
        // Hanya update stok untuk barang yang sudah ada
        // Karena untuk barang baru, stok awal sudah diset saat pembuatan barang
        if ($barangmasuk->barang_id) {
            $barang = Barang::find($barangmasuk->barang_id);

            if ($barang) {
                // Update stok
                $barang->stok += $barangmasuk->jumlah_barang_masuk;
                // Update harga beli terbaru
                $barang->harga_beli = $barangmasuk->harga_beli;
                $barang->save();
                
                Log::info('Stok barang berhasil diupdate', [
                    'barang_id' => $barang->id,
                    'nama_barang' => $barang->nama_barang,
                    'stok_baru' => $barang->stok
                ]);
            }
        }
    }

    public function updated(Barangmasuk $barangmasuk): void
    {
        if ($barangmasuk->isDirty('barang_id') || $barangmasuk->isDirty('jumlah_barang_masuk')) {
            $barang = Barang::find($barangmasuk->barang_id);
            $oldBarangId = $barangmasuk->getOriginal('barang_id');
            $oldJumlah = $barangmasuk->getOriginal('jumlah_barang_masuk');

            // If barang_id changed, reduce stock from old product and add to new
            if ($barangmasuk->isDirty('barang_id') && $oldBarangId) {
                $oldBarang = Barang::find($oldBarangId);
                if ($oldBarang) {
                    $oldBarang->stok -= $oldJumlah;
                    $oldBarang->save();
                }

                // Add full amount to new product
                if ($barang) {
                    $barang->stok += $barangmasuk->jumlah_barang_masuk;
                    $barang->save();
                }
            }
            // If only quantity changed, adjust the difference
            elseif ($barangmasuk->isDirty('jumlah_barang_masuk') && $barang) {
                $difference = $barangmasuk->jumlah_barang_masuk - $oldJumlah;
                $barang->stok += $difference;
                $barang->save();
            }
        }
    }

    public function deleted(Barangmasuk $barangmasuk): void
    {
        $barang = Barang::find($barangmasuk->barang_id);

        if ($barang) {
            $barang->stok -= $barangmasuk->jumlah_barang_masuk;
            $barang->save();
        }
    }

    /**
     * Handle the Barangmasuk "restored" event.
     */
    public function restored(Barangmasuk $barangmasuk): void
    {
        //
    }

    /**
     * Handle the Barangmasuk "force deleted" event.
     */
    public function forceDeleted(Barangmasuk $barangmasuk): void
    {
        //
    }
}
