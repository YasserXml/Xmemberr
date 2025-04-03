<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangkeluarResource\Pages;
use App\Filament\Resources\BarangkeluarResource\RelationManagers;
use App\Models\Barangkeluar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BarangkeluarResource extends Resource
{
    protected static ?string $model = Barangkeluar::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-left-start-on-rectangle';

    protected static ?string $navigationGroup = '🛒 Flow Barang'; 

    protected static ?string $navigationLabel = 'Barang Keluar';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'Barang-Keluar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBarangkeluars::route('/'),
            'create' => Pages\CreateBarangkeluar::route('/create'),
            'edit' => Pages\EditBarangkeluar::route('/{record}/edit'),
        ];
    }
}
