<?php

namespace App\Filament\Resources\BarangkeluarResource\Pages;

use App\Filament\Resources\BarangkeluarResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBarangkeluar extends EditRecord
{
    protected static string $resource = BarangkeluarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
    return Notification::make()
        ->success()
        ->title('Data tersimpan')
        ->body('Data barang berhasil diubah')
        ->seconds(5);
    }
}
