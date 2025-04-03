<?php

namespace App\Filament\Resources\BarangkeluarResource\Pages;

use App\Filament\Resources\BarangkeluarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListBarangkeluars extends ListRecords
{
    protected static string $resource = BarangkeluarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return "Data Barang Keluar";
    }
}
