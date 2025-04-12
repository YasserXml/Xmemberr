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
}
