<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangmasukResource\Pages;
use App\Filament\Resources\BarangmasukResource\RelationManagers;
use App\Models\Barang;
use App\Models\Barangmasuk;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BarangmasukResource extends Resource
{
    protected static ?string $model = Barangmasuk::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-left-end-on-rectangle';

    protected static ?string $navigationGroup = '🛒 Flow Barang';

    protected static ?string $navigationLabel = 'Barang Masuk';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'barang-masuk';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Referensi')
                    ->description('Data referensi barang masuk')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('no_referensi')
                            ->label('No. Referensi')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabled()
                            ->placeholder('Nomor referensi transaksi')
                            ->default(fn() => 'BM-' . strtoupper(Str::random(8)))
                            ->helperText('Nomor unik untuk identifikasi transaksi')
                            ->prefixIcon('heroicon-o-document-text')
                            ->maxLength(20),

                        Forms\Components\DatePicker::make('tanggal_masuk_barang')
                            ->label('Tanggal Masuk')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->closeOnDateSelection()
                            ->prefixIcon('heroicon-o-calendar')
                            ->weekStartsOnSunday(),
                    ]),

                Forms\Components\Section::make('Detail Barang')
                    ->description('Informasi detail barang yang masuk')
                    ->icon('heroicon-o-cube')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Card::make()
                                    ->schema([
                                        Forms\Components\Radio::make('tipe_transaksi')
                                            ->label('Jenis Input Barang')
                                            ->options([
                                                'barang_lama' => 'Pilih Barang yang Sudah Ada',
                                                'barang_baru' => 'Tambah Barang Baru',
                                            ])
                                            ->default('barang_lama')
                                            ->required()
                                            ->inline()
                                            ->live()
                                            ->helperText('Pilih jenis input sesuai kebutuhan transaksi barang masuk')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // Bagian pilih barang yang sudah ada
                        Forms\Components\Grid::make(2)
                            ->visible(fn(callable $get) => $get('tipe_transaksi') === 'barang_lama')
                            ->schema([
                                Forms\Components\Select::make('barang_id')
                                    ->label('Pilih Barang')
                                    ->relationship('barang', 'nama_barang')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->dehydrated()
                                    ->required(fn(callable $get) => $get('tipe_transaksi') === 'barang_lama')
                                    ->placeholder('Cari dan pilih barang...')
                                    ->prefixIcon('heroicon-o-magnifying-glass')
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $barang = Barang::find($state);
                                            if ($barang) {
                                                $set('harga_beli', $barang->harga_beli ?? 0);
                                                // Clear fields untuk barang baru
                                                $set('nama_barang', null);
                                                $set('kode_barang', null);
                                                $set('harga_jual', null);
                                                $set('stok_minimum', null);
                                                $set('satuan', null);
                                            }
                                        }
                                    }),
                            ]),

                        // Bagian input barang baru
                        Forms\Components\Grid::make(2)
                            ->visible(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru')
                            ->schema([
                                Forms\Components\TextInput::make('kode_barang')
                                    ->label('Kode Barang')
                                    ->unique(table: 'barangs', column: 'kode_barang', ignoreRecord: true)
                                    ->required(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru')
                                    ->default(fn() => 'BR-' . strtoupper(Str::random(8)))
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefixIcon('heroicon-o-identification')
                                    ->maxLength(15),

                                Forms\Components\TextInput::make('nama_barang')
                                    ->label('Nama Barang')
                                    ->required(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru')
                                    ->placeholder('Masukkan nama barang')
                                    ->dehydrated()
                                    ->prefixIcon('heroicon-o-tag'),

                                Forms\Components\Select::make('kategori_id')
                                    ->label('Kategori Barang')
                                    ->relationship('kategori', 'nama_kategori')
                                    ->searchable()
                                    ->preload()
                                    ->required(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru')
                                    ->visible(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru'),

                                Forms\Components\TextInput::make('harga_jual')
                                    ->label('Harga Jual')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru')
                                    ->placeholder('0')
                                    ->dehydrated()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->inputMode('numeric')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $numericValue = preg_replace('/[^0-9]/', '', $state);
                                        $set('harga_jual', $numericValue);
                                    })
                                    ->minValue(0)
                                    ->prefixIcon('heroicon-o-banknotes'),

                                Forms\Components\TextInput::make('stok_minimum')
                                    ->label('Stok Minimum')
                                    ->numeric()
                                    ->default(5)
                                    ->required(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru')
                                    ->placeholder('5')
                                    ->dehydrated()
                                    ->prefixIcon('heroicon-o-arrow-down'),

                                Forms\Components\Select::make('satuan')
                                    ->label('Satuan')
                                    ->options([
                                        'pcs' => 'Pcs',
                                        'kg' => 'Kilogram',
                                        'lusin' => 'Lusin',
                                        'box' => 'Box',
                                        'pack' => 'Pack',
                                        'botol' => 'Botol',
                                        'meter' => 'Meter',
                                        'roll' => 'Roll',
                                        'lembar' => 'Lembar',
                                        'set' => 'Set',
                                    ])
                                    ->default('pcs')
                                    ->required(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru')
                                    ->searchable()
                                    ->dehydrated()
                                    ->prefixIcon('heroicon-o-scale'),

                            ]),

                        // Bagian jumlah dan harga untuk semua jenis transaksi
                        Forms\Components\Section::make('Informasi Harga & Kuantitas')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('jumlah_barang_masuk')
                                            ->label('Jumlah Barang Masuk')
                                            ->numeric()
                                            ->required()
                                            ->minValue(1)
                                            ->dehydrated()
                                            ->placeholder('0')
                                            ->helperText('Jumlah unit yang masuk')
                                            ->prefixIcon('heroicon-o-plus')
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                // Pastikan nilai input tetap dipertahankan
                                                $jumlah = is_numeric($state) ? floatval($state) : 0;
                                                $hargaBeli = is_numeric($get('harga_beli')) ? floatval($get('harga_beli')) : 0;

                                                // Hitung total harga hanya jika kedua nilai ada
                                                if ($hargaBeli > 0 && $jumlah > 0) {
                                                    $set('total_harga', $hargaBeli * $jumlah);
                                                }
                                            }),

                                        Forms\Components\TextInput::make('harga_beli')
                                            ->label('Harga Beli Per Unit')
                                            ->required()
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->placeholder('0')
                                            ->dehydrated()
                                            ->mask(RawJs::make('$money($input)'))
                                            ->prefixIcon('heroicon-o-banknotes')
                                            ->live()
                                            ->reactive()
                                            ->lazy()
                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                $numericValue = preg_replace('/[^0-9]/', '', $state);
                                                $set('harga_beli', $numericValue);
                                                // Pastikan nilai input tetap dipertahankan
                                                $hargaBeli = is_numeric($state) ? floatval($state) : 0;
                                                $jumlah = is_numeric($get('jumlah_barang_masuk')) ? floatval($get('jumlah_barang_masuk')) : 0;

                                                // Hitung total harga hanya jika kedua nilai ada
                                                if ($hargaBeli > 0 && $jumlah > 0) {
                                                    $set('total_harga', $hargaBeli * $jumlah);
                                                }
                                            }),

                                        Forms\Components\TextInput::make('total_harga')
                                            ->label('Total Harga')
                                            ->disabled()
                                            ->dehydrated()
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->placeholder('0')
                                            ->prefixIcon('heroicon-o-calculator')
                                            ->helperText('Dihitung otomatis dari jumlah × harga'),
                                    ]),
                            ]),

                        Forms\Components\Hidden::make('temp_barang_id'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_referensi')
                    ->label('No. Referensi')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Klik untuk menyalin')
                    ->copyMessage('Nomor referensi disalin!')
                    ->weight('bold')
                    ->color('primary')
                    ->icon('heroicon-o-document-duplicate')
                    ->iconPosition('after'),

                Tables\Columns\TextColumn::make('tanggal_masuk_barang')
                    ->label('Tanggal Masuk')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('barang.kode_barang')
                    ->label('Kode Barang')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('barang.nama_barang')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn(BarangMasuk $record): string => $record->barang->nama_barang)
                    ->wrap(),

                Tables\Columns\TextColumn::make('jumlah_barang_masuk')
                    ->label('Jumlah Barang Masuk')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-archive-box')
                    ->suffix(fn(BarangMasuk $record): string => ' ' . $record->barang->satuan),

                Tables\Columns\TextColumn::make('harga_beli')
                    ->label('Harga Beli')
                    ->money('IDR', true)
                    ->sortable()
                    ->alignEnd()
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total Harga')
                    ->money('IDR', true)
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->color('gray')
                    ->size('sm')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('barang_id')
                    ->label('Filter Barang')
                    ->relationship('barang', 'nama_barang')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('tanggal_masuk_barang')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal')
                            ->placeholder('Pilih tanggal mulai')
                            ->native(false)
                            ->icon('heroicon-o-calendar-days'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal')
                            ->placeholder('Pilih tanggal akhir')
                            ->native(false)
                            ->icon('heroicon-o-calendar-days'),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['dari_tanggal'] ?? null) {
                            $indicators[] = 'Dari tanggal ' . \Carbon\Carbon::parse($data['dari_tanggal'])->format('d M Y');
                        }

                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = 'Sampai tanggal ' . \Carbon\Carbon::parse($data['sampai_tanggal'])->format('d M Y');
                        }

                        return $indicators;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_masuk_barang', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_masuk_barang', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->tooltip('Lihat detail'),
                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->tooltip('Edit data'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->tooltip('Hapus data')
                        ->modalHeading('Hapus Transaksi Barang Masuk')
                        ->modalDescription('Apakah Anda yakin ingin menghapus transaksi ini? Stok barang akan disesuaikan otomatis.')
                        ->modalSubmitActionLabel('Ya, Hapus Transaksi')
                        ->modalCancelActionLabel('Batal'),
                ])->tooltip('Aksi')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus yang Dipilih')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->modalHeading('Hapus Transaksi yang Dipilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus transaksi yang dipilih? Stok barang akan disesuaikan otomatis.')
                        ->modalSubmitActionLabel('Ya, Hapus Transaksi')
                        ->modalCancelActionLabel('Batal'),
                ]),
            ])
            ->headerActions([])
            ->emptyStateHeading('Belum Ada Transaksi Barang Masuk')
            ->emptyStateDescription('Silakan tambahkan transaksi barang masuk pertama Anda.')
            ->emptyStateIcon('heroicon-o-arrow-down-tray')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Barang Masuk')
                    ->icon('heroicon-o-plus')
                    ->button(),
            ])
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->poll('60s');
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
            'index' => Pages\ListBarangmasuks::route('/'),
            'create' => Pages\CreateBarangmasuk::route('/create'),
            'edit' => Pages\EditBarangmasuk::route('/{record}/edit'),
        ];
    }
}
