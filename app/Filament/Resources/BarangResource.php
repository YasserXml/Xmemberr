<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangResource\Pages;
use App\Filament\Resources\BarangResource\RelationManagers;
use App\Models\Barang;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BarangResource extends Resource
{
    protected static ?string $model = Barang::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationGroup = '📦 Master Barang';

    protected static ?string $navigationLabel = 'Penyimpanan Barang';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'barang';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Barang')
                    ->schema([
                        Forms\Components\TextInput::make('kode_barang')
                            ->label('Kode Barang')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nama_barang')
                            ->label('Nama Barang')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('kategori_id')
                            ->label('Kategori Barang')
                            ->relationship('kategori', 'nama_kategori')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])->columns(3),

                Forms\Components\Section::make('Harga dan Stok')
                    ->schema([
                        Forms\Components\TextInput::make('harga_beli')
                        ->label('Harga Beli')
                        ->numeric()
                        ->mask(RawJs::make('$money($input)'))
                        ->prefix('Rp.')
                        ->inputMode('numeric')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $numericValue = preg_replace('/[^0-9]/', '', $state);
                            $set('harga_beli', $numericValue);
                        })
                        ->minValue(0)
                        ->required(),

                        Forms\Components\TextInput::make('harga_jual')
                        ->label('Harga Jual')
                        ->numeric()
                        ->mask(RawJs::make('$money($input)'))
                        ->prefix('Rp.')
                        ->inputMode('numeric')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $numericValue = preg_replace('/[^0-9]/', '', $state);
                            $set('harga_jual', $numericValue);
                        })
                        ->minValue(0)
                        ->required(),

                        Forms\Components\TextInput::make('stok')
                            ->label('Stok')
                            ->integer()
                            ->default(0)
                            ->minValue(0)
                            ->required(),

                        Forms\Components\TextInput::make('stok_minimum')
                            ->label('Stok Minimum')
                            ->integer()
                            ->default(5)
                            ->minValue(0)
                            ->required(),

                        Forms\Components\Select::make('satuan')
                            ->label('Satuan')
                            ->options([
                                'pcs' => 'Pcs',
                                'kg' => 'Kg',
                                'liter' => 'Liter',
                                'meter' => 'Meter',
                                'pack' => 'Pack',
                            ])
                            ->default('pcs')
                            ->required()
                            ->preload()
                            ->searchable(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_barang')
                    ->label('Kode Barang')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nama_barang')
                    ->label('Nama Barang')
                    ->searchable(),

                Tables\Columns\TextColumn::make('kategori.nama_kategori')
                    ->label('Kategori Barang')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('harga_beli')
                    ->label('Harga Beli')
                    ->money('idr')
                    ->formatStateUsing(fn($state) => 'Rp.' . number_format($state, 0, ',', '.'))
                    ->alignRight(),

                Tables\Columns\TextColumn::make('harga_jual')
                    ->label('Harga Jual')
                    ->money('idr')
                    ->formatStateUsing(fn($state) => 'Rp.' . number_format($state, 0, ',', '.'))
                    ->alignRight(),

                Tables\Columns\TextColumn::make('stok')
                    ->label('Stok')
                    ->badge()
                    ->color(fn($state) => $state <= 5 ? 'danger' : 'success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('satuan')
                    ->label('Satuan')
                    ->badge()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
            ])
            ->filters([
                TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->relationship('kategori', 'nama_kategori')
                    ->searchable(),

                Tables\Filters\Filter::make('stok_rendah')
                    ->label('Stok Rendah')
                    ->query(fn(Builder $query): Builder => $query->where('stok', '<=', 5)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                    ->color('info'),
                    Tables\Actions\DeleteAction::make()
                    ->color('danger'),
                    Tables\Actions\RestoreAction::make(),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->tooltip('Aksi'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListBarangs::route('/'),
            'create' => Pages\CreateBarang::route('/create'),
            'edit' => Pages\EditBarang::route('/{record}/edit'),
        ];
    }
}
