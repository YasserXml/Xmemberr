<?php

namespace App\Filament\Resources\BarangkeluarResource\Pages;

use App\Filament\Resources\BarangkeluarResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateBarangkeluar extends CreateRecord
{
    protected static string $resource = BarangkeluarResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string|Htmlable
    {
        return "Tambah barang";
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
