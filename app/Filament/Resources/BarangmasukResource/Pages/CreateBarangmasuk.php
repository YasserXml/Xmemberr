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
        // PENTING: Pastikan no_referensi selalu terisi
        if (empty($data['no_referensi'])) {
            $data['no_referensi'] = 'BM-' . strtoupper(\Illuminate\Support\Str::random(8));
        }

        // Set user_id
        $data['user_id'] = filament()->auth()->id();

        // Proses berdasarkan tipe transaksi
        if (isset($data['tipe_transaksi'])) {
            if ($data['tipe_transaksi'] === 'barang_baru') {
                $this->createNewBarang($data);
            } else if ($data['tipe_transaksi'] === 'barang_lama' && isset($data['barang_id'])) {
                $this->updateExistingBarang($data);
            }
        }

        // Pastikan total_harga terhitung
        if (!isset($data['total_harga']) || empty($data['total_harga'])) {
            $data['total_harga'] = $data['harga_beli'] * $data['jumlah_barang_masuk'];
        }

        // Pastikan tanggal ada
        if (!isset($data['tanggal_masuk_barang']) || empty($data['tanggal_masuk_barang'])) {
            $data['tanggal_masuk_barang'] = now()->format('Y-m-d');
        }

        // Hanya kembalikan field yang diperlukan untuk model BarangMasuk
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

    private function createNewBarang(array &$data): void
    {
        DB::beginTransaction();
        try {
            $barang = Barang::create([
                'kode_barang' => $data['kode_barang'],
                'nama_barang' => $data['nama_barang'],
                'harga_beli' => $data['harga_beli'],
                'harga_jual' => $data['harga_jual'] ?? ($data['harga_beli'] * 1.2),
                'stok' => $data['jumlah_barang_masuk'],
                'stok_minimum' => $data['stok_minimum'] ?? 5,
                'satuan' => $data['satuan'] ?? 'pcs',
            ]);

            $data['barang_id'] = $barang->id;
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function updateExistingBarang(array $data): void
    {
        DB::beginTransaction();
        try {
            $barang = Barang::findOrFail($data['barang_id']);

            // Update stok barang
            $barang->stok += $data['jumlah_barang_masuk'];
            // Update harga beli jika diperlukan 
            $barang->harga_beli = $data['harga_beli'];
            $barang->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
