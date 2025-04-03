<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangKeluarResource\Pages;
use App\Models\BarangKeluar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\ViewColumn;
use App\Models\Barang;

class BarangkeluarResource extends Resource
{
    protected static ?string $model = Barangkeluar::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-left-start-on-rectangle';

    protected static ?string $navigationGroup = '🛒 Flow Barang'; 

    protected static ?string $navigationLabel = 'Barang Keluar';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'barang-keluar';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('tanggal_keluar', Carbon::today())->count();
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Section::make('Informasi Barang Keluar')
                            ->description('Detail barang yang keluar')
                            ->icon('heroicon-o-archive-box-arrow-down')
                            ->collapsible()
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('no_referensi')
                                            ->label('Nomor Referensi')
                                            ->default(fn () => 'BK-' . strtoupper(Str::random(6)))
                                            ->disabled()
                                            ->required()
                                            ->unique(BarangKeluar::class, 'no_referensi', ignoreRecord: true)
                                            ->columnSpan(2),
                                        
                                        DatePicker::make('tanggal_keluar')
                                            ->label('Tanggal Keluar')
                                            ->default(now())
                                            ->required()
                                            ->columnSpan(1),
                                        
                                        Select::make('barang_id')
                                            ->label('Pilih Barang')
                                            ->options(function () {
                                                return Barang::where('stok', '>', 0)
                                                    ->get()
                                                    ->mapWithKeys(function ($barang) {
                                                        $stokInfo = $barang->stok > 0 ? " (Stok: {$barang->stok} {$barang->satuan})" : " (Stok Habis)";
                                                        return [$barang->id => "{$barang->kode_barang} - {$barang->nama_barang}{$stokInfo}"];
                                                    });
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $barang = Barang::find($state);
                                                    if ($barang) {
                                                        $set('info_barang', "Stok Tersedia: {$barang->stok} {$barang->satuan} | Harga Jual: Rp " . 
                                                            number_format($barang->harga_jual, 0, ',', '.'));
                                                        $set('harga_jual', $barang->harga_jual);
                                                        $set('stok_tersedia', $barang->stok);
                                                    }
                                                }
                                            })
                                            ->columnSpan(2),
                                        
                                        TextInput::make('stok_tersedia')
                                            ->label('Stok Tersedia')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpan(1),
                                        
                                        TextInput::make('info_barang')
                                            ->label('Informasi Barang')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpan(3),
                                        
                                        TextInput::make('jumlah_barang_keluar')
                                            ->label('Jumlah Keluar')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                $hargaJual = $get('harga_jual') ?: 0;
                                                $set('total_harga', $hargaJual * $state);
                                                
                                                // Validasi stok
                                                $stokTersedia = $get('stok_tersedia') ?: 0;
                                                if ($state > $stokTersedia) {
                                                    $set('jumlah_barang_keluar', $stokTersedia);
                                                    $set('total_harga', $hargaJual * $stokTersedia);
                                                }
                                            })
                                            ->columnSpan(1),
                                        
                                        TextInput::make('harga_jual')
                                            ->label('Harga Jual')
                                            ->prefix('Rp')
                                            ->numeric()
                                            ->required()
                                            ->columnSpan(1)
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                $jumlah = $get('jumlah_barang_keluar') ?: 1;
                                                $set('total_harga', $state * $jumlah);
                                            }),
                                        
                                        TextInput::make('total_harga')
                                            ->label('Total Harga')
                                            ->prefix('Rp')
                                            ->numeric()
                                            ->required()
                                            ->disabled()
                                            ->columnSpan(1),
                                        
                                        Placeholder::make('stok_warning')
                                            ->label('Peringatan Stok')
                                            ->content('Jumlah barang keluar tidak boleh melebihi stok yang tersedia')
                                            ->visible(function ($get) {
                                                return ($get('jumlah_barang_keluar') ?: 0) > ($get('stok_tersedia') ?: 0);
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Section::make('Transaksi & Pengguna')
                            ->schema([
                                Select::make('transaksi_id')
                                    ->label('Transaksi Terkait')
                                    ->relationship('transaksi', 'no_transaksi')
                                    ->searchable()
                                    ->preload(),
                                
                                Select::make('user_id')
                                    ->label('Petugas')
                                    ->relationship('user', 'name')
                                    ->default(fn () => filament()->auth()->id())
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                            ]),
                        
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_referensi')
                    ->label('No. Referensi')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Nomor referensi disalin!')
                    ->copyMessageDuration(1500)
                    ->weight(FontWeight::Bold)
                    ->color('danger'),
                
                TextColumn::make('tanggal_keluar')
                    ->label('Tanggal Keluar')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar'),
                
                TextColumn::make('barang.kode_barang')
                    ->label('Kode Barang')
                    ->searchable()
                    ->sortable()
                    ->tooltip(fn ($record) => $record->barang?->nama_barang ?? 'Barang tidak ditemukan'),
                
                TextColumn::make('barang.nama_barang')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                
                TextColumn::make('jumlah_barang_keluar')
                    ->label('Jumlah')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => 
                        "{$state} " . ($record->barang?->satuan ?? 'pcs')),
                
                TextColumn::make('harga_jual')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),
                
                TextColumn::make('total_harga')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->weight(FontWeight::Bold),
                
                TextColumn::make('transaksi.no_transaksi')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->url(fn ($record) => $record->transaksi_id ? 
                        '/admin/transaksis/' . $record->transaksi_id : null)
                    ->openUrlInNewTab(),
                
                TextColumn::make('user.name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->label('Filter Petugas')
                    ->searchable()
                    ->preload()
                    ->indicator('Petugas'),
                
                SelectFilter::make('barang')
                    ->relationship('barang', 'nama_barang')
                    ->label('Filter Barang')
                    ->searchable()
                    ->preload()
                    ->indicator('Barang'),
                
                Filter::make('tanggal_keluar')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal_dari')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('tanggal_sampai')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['tanggal_dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_keluar', '>=', $date),
                            )
                            ->when(
                                $data['tanggal_sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_keluar', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        
                        if ($data['tanggal_dari'] ?? null) {
                            $indicators['tanggal_dari'] = 'Dari ' . Carbon::parse($data['tanggal_dari'])->format('d M Y');
                        }
                        
                        if ($data['tanggal_sampai'] ?? null) {
                            $indicators['tanggal_sampai'] = 'Sampai ' . Carbon::parse($data['tanggal_sampai'])->format('d M Y');
                        }
                        
                        return $indicators;
                    }),
                
                Filter::make('transaksi')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('transaksi_id'))
                    ->label('Terkait Transaksi')
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat')
                        ->icon('heroicon-o-eye'),
                    
                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->icon('heroicon-o-pencil'),
                    
                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-o-trash'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->groups([
                Group::make('tanggal_keluar')
                    ->label('Tanggal Keluar')
                    ->getTitleFromRecordUsing(fn ($record): string => Carbon::parse($record->tanggal_keluar)->format('d M Y'))
                    ->collapsible(),
                Group::make('barang.nama_barang')
                    ->label('Barang'),
            ])
            ->defaultGroup('tanggal_keluar')
            ->emptyStateHeading('Belum Ada Barang Keluar')
            ->emptyStateDescription('Buat data barang keluar baru dengan menekan tombol di bawah')
            ->emptyStateIcon('heroicon-o-archive-box-arrow-down')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat Barang Keluar')
                    ->icon('heroicon-o-plus'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
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
