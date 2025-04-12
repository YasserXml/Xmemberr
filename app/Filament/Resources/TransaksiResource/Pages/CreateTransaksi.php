<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\TransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateTransaksi extends CreateRecord
{
    protected static string $resource = TransaksiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Normalisasi total bayar dan total harga
        $totalBayar = is_string($data['total_bayar'] ?? 0) ? (int)preg_replace('/[^0-9]/', '', $data['total_bayar']) : (int)($data['total_bayar'] ?? 0);
        $totalHarga = is_string($data['total_harga'] ?? 0) ? (int)preg_replace('/[^0-9]/', '', $data['total_harga']) : (int)($data['total_harga'] ?? 0);

        // Re-calculate status pembayaran
        if ($totalBayar >= $totalHarga && $totalHarga > 0) {
            $data['status_pembayaran'] = 'lunas';
        } else if ($totalBayar > 0 && $totalHarga > 0) {
            $data['status_pembayaran'] = 'sebagian';
        } else {
            $data['status_pembayaran'] = 'belum_bayar';
        }

        // Pastikan nilai kembalian benar
        $data['kembalian'] = max(0, $totalBayar - $totalHarga);

        // Pastikan data faktur konsisten dengan status pembayaran
        if (isset($data['create_faktur']) && $data['create_faktur'] && isset($data['faktur'])) {
            $data['faktur']['status'] = ($data['status_pembayaran'] === 'lunas') ? 'lunas' : 'belum_lunas';
        }

        return $data;
    }

    protected function mutateFormDataBeforeUpdate(array $data): array
    {
        // Gunakan kode yang sama dengan beforeCreate
        return $this->mutateFormDataBeforeCreate($data);
    }

    // protected function afterCreate(): void
    // {
    //     $transaksi = $this->record;

    //     // Membuat data barangkeluar untuk setiap item transaksi
    //     foreach ($transaksi->items as $item) {
    //         // Ambil data barang untuk mendapatkan harga yang valid
    //         $barang = \App\Models\Barang::find($item['barang_id']);

    //         if ($barang) {
    //             // Buat data barang keluar
    //             \App\Models\BarangKeluar::create([
    //                 'no_referensi' => 'BK-' . strtoupper(Str::random(6)),
    //                 'jumlah_barang_keluar' => $item['jumlah'],
    //                 'harga_jual' => $barang->harga_jual, // Menggunakan harga_jual dari model Barang
    //                 'total_harga' => $item->subtotal,
    //                 'tanggal_keluar' => $transaksi->tanggal_transaksi,
    //                 'barang_id' => $item['barang_id'],
    //                 'transaksi_id' => $transaksi->id,
    //                 'user_id' => $transaksi->user_id,
    //             ]);

    //             // Update stok barang
    //             $barang->stok -= $item['jumlah'];
    //             $barang->save();
    //         }
    //     }
    // }
}
