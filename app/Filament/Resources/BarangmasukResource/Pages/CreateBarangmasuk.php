<?php

namespace App\Filament\Resources\BarangmasukResource\Pages;

use App\Filament\Resources\BarangmasukResource;
use App\Models\Barang;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;

class CreateBarangmasuk extends CreateRecord
{
    protected static string $resource = BarangmasukResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
{
    // Set user_id (sudah ada)
    $data['user_id'] = filament()->auth()->id();
    
    // Cek jika tipe_transaksi adalah barang_baru
    if (isset($data['tipe_transaksi']) && $data['tipe_transaksi'] === 'barang_baru') {
        DB::beginTransaction();
        try {
            // Buat barang baru
            $barang = Barang::create([
                'kode_barang' => $data['kode_barang'],
                'nama_barang' => $data['nama_barang'],
                'harga_beli' => $data['harga_beli'],
                'harga_jual' => $data['harga_jual'] ?? ($data['harga_beli'] * 1.2),
                'stok' => $data['jumlah_barang_masuk'],
                'stok_minimum' => $data['stok_minimum'] ?? 5,
                'satuan' => $data['satuan'] ?? 'pcs',
                'kategori_id' => $data['kategori_id'],
            ]);
            
            // Set barang_id
            $data['barang_id'] = $barang->id;
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    // Filter data yang akan disimpan ke tabel barangmasuks
    return [
        'no_referensi' => $data['no_referensi'],
        'barang_id' => $data['barang_id'],
        'jumlah_barang_masuk' => $data['jumlah_barang_masuk'],
        'harga_beli' => $data['harga_beli'],
        'total_harga' => $data['total_harga'],
        'tanggal_masuk_barang' => $data['tanggal_masuk_barang'],
        'user_id' => $data['user_id'],
    ];
}

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string|Htmlable
    {
        return "Tambah barang Masuk";
    }

    protected function getCreatedNotification(): ?Notification
    {
    return Notification::make()
        ->success()
        ->title('Data tersimpan')
        ->body('Data barang baru berhasil tersimpan')
        ->seconds(5);
    }
}
