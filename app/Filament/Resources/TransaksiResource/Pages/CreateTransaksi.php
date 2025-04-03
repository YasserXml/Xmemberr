<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\TransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateTransaksi extends CreateRecord
{
    protected static string $resource = TransaksiResource::class;

    protected function afterCreate(): void
    {
        $transaksi = $this->record;

        // Membuat data barangkeluar untuk setiap item transaksi
        foreach ($transaksi->items as $item) {
            // Buat data barang keluar
            \App\Models\BarangKeluar::create([
                'no_referensi' => 'BK-' . strtoupper(Str::random(6)),
                'jumlah_barang_keluar' => $item['jumlah'],
                'harga_jual' => $item['harga'],
                'total_harga' => $item['subtotal'],
                'tanggal_keluar' => $transaksi->tanggal_transaksi,
                'barang_id' => $item['barang_id'],
                'transaksi_id' => $transaksi->id,
                'user_id' => $transaksi->user_id,
            ]);

            // Update stok barang
            $barang = \App\Models\Barang::find($item['barang_id']);
            if ($barang) {
                $barang->stok -= $item['jumlah'];
                $barang->save();
            }
        }

        // Jika create_faktur adalah true, maka data faktur sudah dibuat di form
        // Tidak perlu membuat faktur lagi di sini
    }
}
