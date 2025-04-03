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

    protected function beforeCreate(array $data): array
    {
        // Cek jika tipe transaksi adalah barang baru
        if ($data['tipe_transaksi'] === 'barang_baru') {
            DB::beginTransaction();
            try {
                // Buat barang baru
                $barang = Barang::create([
                    'kode_barang' => $data['kode_barang'],
                    'nama_barang' => $data['nama_barang'],
                    'harga_beli' => $data['harga_beli'],
                    'harga_jual' => $data['harga_jual'] ?? ($data['harga_beli'] * 1.2),
                    'stok' => $data['jumlah_barang_masuk'],
                    'stok_minimum' => $data['stok_minimum'] ?? 5,
                    'satuan' => $data['satuan'] ?? 'pcs',
                    'kategori_id' => $data['kategori_id'],
                ]);

                // Set barang_id untuk disimpan
                $data['barang_id'] = $barang->id;

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        return $data;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Barang Masuk')
                    ->schema([
                        Forms\Components\TextInput::make('no_referensi')
                            ->label('No. Referensi')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Masukkan nomor referensi'),

                        Forms\Components\DatePicker::make('tanggal_masuk_barang')
                            ->label('Tanggal Masuk')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])->columns(2),

                Forms\Components\Section::make('Detail Barang')
                    ->schema([
                        Forms\Components\Radio::make('tipe_transaksi')
                            ->label('Jenis Input')
                            ->options([
                                'barang_lama' => 'Pilih Barang yang Sudah Ada',
                                'barang_baru' => 'Tambah Barang Baru',
                            ])
                            ->default('barang_lama')
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('barang_id')
                            ->label('Pilih Barang')
                            ->relationship('barang', 'nama_barang')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(fn(callable $get) => $get('tipe_transaksi') === 'barang_lama')
                            ->visible(fn(callable $get) => $get('tipe_transaksi') === 'barang_lama')
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
                                        $set('kategori_id', null);
                                    }
                                }
                            }),

                        // Fields untuk barang baru - hanya muncul jika tipe_transaksi adalah barang_baru
                        Forms\Components\TextInput::make('kode_barang')
                            ->label('Kode Barang')
                            ->unique(table: 'barangs', column: 'kode_barang', ignoreRecord: true)
                            ->required(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru')
                            ->visible(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru')
                            ->placeholder('Kode barang baru'),

                        Forms\Components\TextInput::make('nama_barang')
                            ->label('Nama Barang')
                            ->required(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru')
                            ->visible(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru')
                            ->placeholder('Nama barang baru'),

                        Forms\Components\TextInput::make('harga_jual')
                            ->label('Harga Jual')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru')
                            ->visible(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru')
                            ->placeholder('Harga jual barang')
                            ->mask(RawJs::make('$money($input)'))
                            ->inputMode('numeric')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $numericValue = preg_replace('/[^0-9]/', '', $state);
                                $set('harga_jual', $numericValue);
                            })
                            ->minValue(0),

                        Forms\Components\TextInput::make('stok_minimum')
                            ->label('Stok Minimum')
                            ->numeric()
                            ->default(5)
                            ->required(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru')
                            ->visible(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru')
                            ->placeholder('Stok minimum'),

                        Forms\Components\Select::make('satuan')
                            ->label('Satuan')
                            ->options([
                                'pcs' => 'pcs',
                                'box' => 'box',
                                'kg' => 'kg',
                                'lusin' => 'lusin',
                            ])
                            ->default('pcs')
                            ->required(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru')
                            ->visible(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru'),

                        Forms\Components\Select::make('kategori_id')
                            ->label('Kategori Barang')
                            ->relationship('kategori', 'nama_kategori')
                            ->searchable()
                            ->preload()
                            ->required(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru')
                            ->visible(fn(callable $get) => $get('tipe_transaksi') === 'barang_baru'),

                        Forms\Components\TextInput::make('jumlah_barang_masuk')
                            ->label('Jumlah Barang Masuk')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->placeholder('Masukkan jumlah barang')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                // Pastikan nilai adalah numerik
                                $hargaBeli = floatval($get('harga_beli') ?: 0);
                                $jumlah = floatval($state ?: 0);

                                // Hitung total harga hanya jika kedua nilai ada
                                if ($hargaBeli > 0 && $jumlah > 0) {
                                    $set('total_harga', $hargaBeli * $jumlah);
                                }
                            }),

                        Forms\Components\TextInput::make('harga_beli')
                            ->label('Harga Beli')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('Harga beli per unit')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                // Pastikan nilai adalah numerik
                                $hargaBeli = floatval($state ?: 0);
                                $jumlah = floatval($get('jumlah_barang_masuk') ?: 0);

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
                            ->placeholder('Total harga'),
                        Forms\Components\Hidden::make('temp_barang_id'),
                    ])->columns(2),
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
                    ->icon('heroicon-o-document-text')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('barang.nama_barang')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->alignCenter()
                    ->tooltip(function ($state) {
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('jumlah_barang_masuk')
                    ->label('Jumlah Barang')
                    ->sortable()
                    ->color('success')
                    ->size('lg')
                    ->alignCenter()
                    ->badge(),

                Tables\Columns\TextColumn::make('harga_beli')
                    ->label('Harga Beli')
                    ->money('IDR')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->alignment('right')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->alignment('right')
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('tanggal_masuk_barang')
                    ->label('Tanggal Masuk')
                    ->date('d/m/Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Diinput Oleh')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->tooltip('User yang menambahkan data'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since(),
            ])
            ->filters([
                Tables\Filters\Filter::make('tanggal_masuk_barang')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['dari_tanggal'] ?? null) {
                            $indicators['dari_tanggal'] = 'Dari tanggal ' . \Carbon\Carbon::parse($data['dari_tanggal'])->format('d/m/Y');
                        }

                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators['sampai_tanggal'] = 'Sampai tanggal ' . \Carbon\Carbon::parse($data['sampai_tanggal'])->format('d/m/Y');
                        }

                        return $indicators;
                    }),
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
            'index' => Pages\ListBarangmasuks::route('/'),
            'create' => Pages\CreateBarangmasuk::route('/create'),
            'edit' => Pages\EditBarangmasuk::route('/{record}/edit'),
        ];
    }
}
