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
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
            Forms\Components\Tabs::make('Manajemen Barang')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Informasi Dasar')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\Section::make()
                                ->schema([
                                    Forms\Components\Grid::make()
                                        ->schema([
                                            Forms\Components\TextInput::make('kode_barang')
                                                ->label('Kode Barang')
                                                ->required()
                                                ->default(fn() => 'BR-' . strtoupper(Str::random(8)))
                                                ->unique(ignoreRecord: true)
                                                ->maxLength(255)
                                                ->disabled()
                                                ->dehydrated()
                                                ->helperText('Kode barang digenerate otomatis')
                                                ->prefixIcon('heroicon-m-document-text'),

                                            Forms\Components\TextInput::make('nama_barang')
                                                ->label('Nama Barang')
                                                ->required()
                                                ->maxLength(255)
                                                ->autofocus()
                                                ->placeholder('Masukkan nama barang')
                                                ->prefixIcon('heroicon-m-tag'),
                                        ]),

                                    Forms\Components\Select::make('kategori_id')
                                        ->label('Kategori Barang')
                                        ->relationship('kategori', 'nama_kategori')
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('nama_kategori')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\Textarea::make('deskripsi')
                                                ->maxLength(65535)
                                                ->columnSpanFull(),
                                        ])
                                        ->searchable()
                                        ->preload()
                                        ->editOptionForm([
                                            Forms\Components\TextInput::make('nama_kategori')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\Textarea::make('deskripsi')
                                                ->maxLength(65535)
                                                ->columnSpanFull(),
                                        ])
                                        ->native(false)
                                        ->prefixIcon('heroicon-m-squares-2x2'),
                                ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('Harga & Stok')
                        ->icon('heroicon-m-currency-dollar')
                        ->schema([
                            Forms\Components\Section::make('Informasi Harga')
                                ->description('Atur harga beli dan jual barang')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('harga_beli')
                                                ->label('Harga Beli')
                                                ->numeric()
                                                ->mask(RawJs::make('$money($input)'))
                                                ->prefix('Rp')
                                                ->inputMode('numeric')
                                                ->live()
                                                ->afterStateUpdated(function ($state, callable $set) {
                                                    $numericValue = preg_replace('/[^0-9]/', '', $state);
                                                    $set('harga_beli', $numericValue);
                                                })
                                                ->minValue(0)
                                                ->required()
                                                ->suffixIcon('heroicon-m-banknotes'),

                                            Forms\Components\TextInput::make('harga_jual')
                                                ->label('Harga Jual')
                                                ->numeric()
                                                ->mask(RawJs::make('$money($input)'))
                                                ->prefix('Rp')
                                                ->inputMode('numeric')
                                                ->live()
                                                ->afterStateUpdated(function ($state, callable $set, $get) {
                                                    $numericValue = preg_replace('/[^0-9]/', '', $state);
                                                    $set('harga_jual', $numericValue);
                                                    
                                                    // Calculate margin
                                                    $hargaBeli = (int)$get('harga_beli');
                                                    if ($hargaBeli > 0 && $numericValue > 0) {
                                                        $marginPercentage = (($numericValue - $hargaBeli) / $hargaBeli) * 100;
                                                        $set('margin', round($marginPercentage, 2));
                                                    }
                                                })
                                                ->minValue(0)
                                                ->required()
                                                ->suffixIcon('heroicon-m-receipt-percent'),
                                        ]),
                                    
                                    Forms\Components\TextInput::make('margin')
                                        ->label('Margin (%)')
                                        ->disabled()
                                        ->suffix('%')
                                        ->dehydrated(false),
                                ]),

                            Forms\Components\Section::make('Manajemen Stok')
                                ->description('Atur stok dan satuan barang')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('stok')
                                                ->label('Stok Saat Ini')
                                                ->integer()
                                                ->minValue(0)
                                                ->required()
                                                ->suffixIcon('heroicon-m-cube'),

                                            Forms\Components\TextInput::make('stok_minimum')
                                                ->label('Stok Minimum')
                                                ->integer()
                                                ->default(5)
                                                ->minValue(0)
                                                ->required()
                                                ->suffixIcon('heroicon-m-exclamation-triangle')
                                                ->helperText('Notifikasi akan muncul jika stok di bawah nilai ini'),

                                            Forms\Components\Select::make('satuan')
                                                ->label('Satuan')
                                                ->options([
                                                    'pcs' => 'Pcs',
                                                    'kg' => 'Kg',
                                                    'liter' => 'Liter',
                                                    'pack' => 'Pack',
                                                    'box' => 'Box',
                                                    'lusin' => 'Lusin',
                                                    'kodi' => 'Kodi',
                                                    'rim' => 'Rim',
                                                    'rol' => 'Rol',
                                                    'meter' => 'Meter',
                                                ])
                                                ->default('pcs')
                                                ->required()
                                                ->preload()
                                                ->native(false)
                                                ->searchable(),
                                        ]),
                                ]),
                        ]),
                ])
                ->activeTab(1),
        ]);
}

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('kode_barang')
                ->label('Kode Barang')
                ->searchable()
                ->sortable()
                ->copyable()
                ->copyMessage('Kode barang disalin!')
                ->tooltip('Kode unik untuk identifikasi barang')
                ->icon('heroicon-o-tag')
                ->weight('bold'),

            Tables\Columns\TextColumn::make('nama_barang')
                ->label('Nama Barang')
                ->searchable()
                ->sortable()
                ->limit(30)
                ->tooltip(fn($record) => $record->nama_barang)
                ->wrap(),

            Tables\Columns\TextColumn::make('kategori.nama_kategori')
                ->label('Kategori Barang')
                ->searchable()
                ->sortable()
                ->badge()
                ->color('secondary')
                ->icon('heroicon-o-bookmark'),

            Tables\Columns\TextColumn::make('harga_beli')
                ->label('Harga Beli')
                ->money('IDR')
                ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                ->alignEnd()
                ->sortable(),

            Tables\Columns\TextColumn::make('harga_jual')
                ->label('Harga Jual')
                ->money('IDR')
                ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                ->alignEnd()
                ->sortable(),

            Tables\Columns\TextColumn::make('stok')
                ->label('Stok')
                ->badge()
                ->alignCenter()
                ->sortable()
                ->color(
                    fn($state) =>
                    $state <= 0 ? 'danger' : ($state <= 5 ? 'warning' : 'success')
                )
                ->icon(fn($state) => 
                    $state <= 0 ? 'heroicon-o-x-circle' : 
                    ($state <= 5 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                )
                ->description(fn($record) => $record->stok <= 5 ? 'Stok Rendah' : null)
                ->searchable()
                ->summarize([
                    Tables\Columns\Summarizers\Sum::make()->label('Total Stok'),
                ]),

            Tables\Columns\TextColumn::make('satuan')
                ->label('Satuan')
                ->badge()
                ->alignCenter()
                ->searchable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Dibuat')
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->tooltip(fn($record) => 'Dibuat: ' . $record->created_at->diffForHumans()),

            Tables\Columns\TextColumn::make('updated_at')
                ->label('Diperbarui')
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->tooltip(fn($record) => 'Diperbarui: ' . $record->updated_at->diffForHumans())
                ->since(),
        ])
        ->filters([
            TrashedFilter::make(),
            Tables\Filters\SelectFilter::make('kategori_id')
                ->label('Kategori')
                ->relationship('kategori', 'nama_kategori')
                ->searchable()
                ->preload()
                ->multiple()
                ->indicator('Kategori'),

            Tables\Filters\Filter::make('stok_rendah')
                ->label('Stok Rendah')
                ->query(fn(Builder $query): Builder => $query->where('stok', '<=', 5))
                ->indicator('Stok Rendah')
                ,

            Tables\Filters\Filter::make('stok_kosong')
                ->label('Stok Kosong')
                ->query(fn(Builder $query): Builder => $query->where('stok', '<=', 0))
                ->indicator('Stok Kosong')
                ,

            Tables\Filters\SelectFilter::make('satuan')
                ->label('Satuan')
                ->options(fn() => \App\Models\Barang::distinct()->pluck('satuan', 'satuan')->toArray())
                ->multiple()
                ->indicator('Satuan'),
                
            Tables\Filters\Filter::make('harga_range')
                ->form([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('harga_dari')
                                ->label('Harga dari')
                                ->numeric()
                                ->placeholder('Min'),
                            Forms\Components\TextInput::make('harga_sampai')
                                ->label('Harga sampai')
                                ->numeric()
                                ->placeholder('Max'),
                        ]),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['harga_dari'],
                            fn(Builder $query, $amount): Builder => $query->where('harga_jual', '>=', $amount),
                        )
                        ->when(
                            $data['harga_sampai'],
                            fn(Builder $query, $amount): Builder => $query->where('harga_jual', '<=', $amount),
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    
                    if ($data['harga_dari'] ?? null) {
                        $indicators['harga_dari'] = 'Harga dari: Rp ' . number_format($data['harga_dari'], 0, ',', '.');
                    }
                    
                    if ($data['harga_sampai'] ?? null) {
                        $indicators['harga_sampai'] = 'Harga sampai: Rp ' . number_format($data['harga_sampai'], 0, ',', '.');
                    }
                    
                    return $indicators;
                }),
        ]) 
        ->actions([
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make()
                    ->color('success')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn($record) => "Detail Barang: {$record->nama_barang}")
                    ->slideOver(),
                    
                Tables\Actions\EditAction::make()
                    ->color('info')
                    ->icon('heroicon-o-pencil')
                    ->modalHeading(fn($record) => "Edit Barang: {$record->nama_barang}")
                    ->slideOver(),
                    
                Tables\Actions\Action::make('updateStok')
                    ->label('Update Stok')
                    ->color('warning')
                    ->icon('heroicon-o-plus-circle')
                    ->action(function ($record, array $data): void {
                        $record->stok = $record->stok + $data['jumlah'];
                        $record->save();
                        
                        // Gunakan facade Notification yang benar untuk Filament 3.3
                        \Filament\Notifications\Notification::make()
                            ->title('Stok berhasil diperbarui')
                            ->success()
                            ->send();
                    })
                    ->form([
                        Forms\Components\TextInput::make('jumlah')
                            ->label('Jumlah')
                            ->numeric()
                            ->required()
                            ->minValue(-100)
                            ->maxValue(1000)
                            ->placeholder('Masukkan jumlah stok')
                            ->suffix('unit')
                            ->helperText('Masukkan angka negatif untuk mengurangi stok'),
                    ])
                    ->modalHeading(fn($record) => "Update Stok: {$record->nama_barang}")
                    ->modalWidth('md'),
                    
                Tables\Actions\DeleteAction::make()
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->modalHeading('Hapus Barang')
                    ->modalDescription('Apakah Anda yakin ingin menghapus barang ini? Tindakan ini dapat dibatalkan nanti.')
                    ->requiresConfirmation(),
                    
                Tables\Actions\RestoreAction::make()
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->modalHeading('Pulihkan Barang')
                    ->modalDescription('Apakah Anda yakin ingin memulihkan barang ini?')
                    ->requiresConfirmation(),
            ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->tooltip('Aksi')
                ->size('sm'),
        ])
        
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->icon('heroicon-o-trash')
                    ->modalHeading('Hapus Barang Terpilih')
                    ->modalDescription('Apakah Anda yakin ingin menghapus semua barang yang dipilih? Tindakan ini dapat dibatalkan nanti.')
                    ->deselectRecordsAfterCompletion(),
                    
                Tables\Actions\RestoreBulkAction::make()
                    ->icon('heroicon-o-arrow-path')
                    ->modalHeading('Pulihkan Barang Terpilih')
                    ->modalDescription('Apakah Anda yakin ingin memulihkan semua barang yang dipilih?')
                    ->deselectRecordsAfterCompletion(),
                    
                Tables\Actions\BulkAction::make('updateStok')
                    ->label('Update Stok')
                    ->icon('heroicon-o-plus-circle')
                    ->color('warning')
                    ->action(function (Collection $records, array $data): void {
                        foreach ($records as $record) {
                            $record->stok = $record->stok + $data['jumlah'];
                            $record->save();
                        }
                        
                        // Gunakan facade Notification yang benar untuk Filament 3.3
                        \Filament\Notifications\Notification::make()
                            ->title('Stok ' . $records->count() . ' barang berhasil diperbarui')
                            ->success()
                            ->send();
                    })
                    ->form([
                        Forms\Components\TextInput::make('jumlah')
                            ->label('Jumlah')
                            ->numeric()
                            ->required()
                            ->minValue(-100)
                            ->maxValue(1000)
                            ->placeholder('Masukkan jumlah stok')
                            ->suffix('unit')
                            ->helperText('Masukkan angka negatif untuk mengurangi stok'),
                    ])
                    ->deselectRecordsAfterCompletion()
                    ->modalHeading('Update Stok Barang Terpilih')
                    ->modalWidth('md'),
                ])
        ])
        ->emptyStateHeading('Belum ada data barang')
        ->emptyStateDescription('Silakan tambahkan data barang baru untuk mengelola inventaris Anda')
        ->emptyStateIcon('heroicon-o-shopping-bag')
        ->emptyStateActions([
            Tables\Actions\Action::make('create')
                ->label('Tambah Barang')
                ->url(route('filament.admin.resources.barang.create'))
                ->icon('heroicon-o-plus')
                ->button(),
        ])
        ->striped()
        ->defaultSort('created_at', 'desc')
        ->paginated([10, 25, 50, 100])
        ->poll('60s')
        ->deferLoading();
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
