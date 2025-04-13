<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiResource\Pages;
use App\Filament\Resources\TransaksiResource\RelationManagers;
use App\Models\Transaksi;
use App\Models\Member;
use App\Models\Barang;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\Badge;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = '💰 Penjualan Barang';

    protected static ?string $navigationLabel = 'Transaksi Barang';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'transaksi';

    public static function updateTotalHargaDanStatus(callable $set, callable $get): void
    {
        // Ambil data items
        $items = $get('../../items') ?? [];

        // Hitung total dari semua item
        $totalHarga = 0;
        foreach ($items as $item) {
            $itemSubtotal = $item['subtotal'] ?? 0;
            if (is_string($itemSubtotal)) {
                $itemSubtotal = (int) preg_replace('/[^0-9]/', '', $itemSubtotal);
            }
            $totalHarga += $itemSubtotal;
        }

        // Set total harga
        $set('../../total_harga', $totalHarga);

        // Ambil total bayar dan normalisasi
        $totalBayar = $get('../../total_bayar') ?? 0;
        if (is_string($totalBayar)) {
            $totalBayar = (int) preg_replace('/[^0-9]/', '', $totalBayar);
        }

        // Hitung kembalian
        $kembalian = max(0, $totalBayar - $totalHarga);
        $set('../../kembalian', $kembalian);

        // Tentukan status pembayaran
        $statusPembayaran = 'belum_bayar';
        if ($totalBayar >= $totalHarga && $totalHarga > 0) {
            $statusPembayaran = 'lunas';
        } else if ($totalBayar > 0 && $totalHarga > 0) {
            $statusPembayaran = 'sebagian';
        }

        // Set status pembayaran
        $set('../../status_pembayaran', $statusPembayaran);

        // Sinkronkan dengan status faktur jika create_faktur = true
        if ($get('../../create_faktur')) {
            $fakturStatus = ($statusPembayaran === 'lunas') ? 'lunas' : 'belum_lunas';
            $set('../../faktur.status', $fakturStatus);
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Informasi Transaksi')
                        ->icon('heroicon-o-document-text')
                        ->description('Masukkan informasi dasar transaksi')
                        ->schema([
                            Group::make()
                                ->schema([
                                    Section::make('Detail Transaksi')
                                        ->description('Informasi mengenai transaksi')
                                        ->icon('heroicon-o-document-duplicate')
                                        ->collapsible()
                                        ->schema([
                                            Grid::make(3)
                                                ->schema([
                                                    TextInput::make('no_transaksi')
                                                        ->label('Nomor Transaksi')
                                                        ->default(fn() => 'TRX-' . strtoupper(Str::random(8)))
                                                        ->disabled()
                                                        ->required()
                                                        ->dehydrated()
                                                        ->unique(Transaksi::class, 'no_transaksi', ignoreRecord: true)
                                                        ->columnSpan(1),

                                                    DatePicker::make('tanggal_transaksi')
                                                        ->label('Tanggal Transaksi')
                                                        ->default(now())
                                                        ->required()
                                                        ->native(false)
                                                        ->columnSpan(1),

                                                    Select::make('user_id')
                                                        ->label('Kasir')
                                                        ->relationship('user', 'name')
                                                        ->default(fn() => Auth::id())
                                                        ->required()
                                                        ->searchable()
                                                        ->preload()
                                                        ->columnSpan(1),

                                                        Forms\Components\Radio::make('tipe_pelanggan')
                                                        ->label('Tipe Pelanggan')
                                                        ->options([
                                                            'non_member' => 'Umum (Non-Member)',
                                                            'member' => 'Member',
                                                        ])
                                                        ->default('non_member')
                                                        ->required()
                                                        ->live()
                                                        ->afterStateUpdated(function (callable $set) {
                                                            // Reset member_id jika tipe pelanggan berubah
                                                            $set('member_id', null);
                                                        }),
                                                    
                                                    Select::make('member_id')
                                                        ->label('Pilih Member')
                                                        ->relationship('member', 'nama_member')
                                                        ->searchable()
                                                        ->preload()
                                                        ->required(fn (callable $get) => $get('tipe_pelanggan') === 'member')
                                                        ->visible(fn (callable $get) => $get('tipe_pelanggan') === 'member')
                                                        ->createOptionForm([
                                                            TextInput::make('kode_member')
                                                                ->label('Kode Member')
                                                                ->default(fn() => 'M-' . strtoupper(Str::random(6)))
                                                                ->required()
                                                                ->unique('members', 'kode_member'),
                                                            TextInput::make('nama_member')
                                                                ->label('Nama Member')
                                                                ->required(),
                                                            TextInput::make('email')
                                                                ->label('Email')
                                                                ->email()
                                                                ->unique('members', 'email'),
                                                            TextInput::make('telepon')
                                                                ->label('Nomor Telepon')
                                                                ->tel(),
                                                            Textarea::make('alamat')
                                                                ->label('Alamat'),
                                                        ])
                                                        ->columnSpan(3),
                                                ]),
                                        ]),
                                ]),
                        ]),

                    Step::make('Detail Transaksi')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->description('Tambahkan item dan selesaikan pembayaran')
                        ->schema([
                            Section::make('Item Transaksi')
                                ->description('Tambahkan item yang dibeli')
                                ->icon('heroicon-o-shopping-cart')
                                ->collapsible()
                                ->schema([
                                    Repeater::make('items')
                                        ->label('Daftar Barang')
                                        ->schema([
                                            Grid::make(6)
                                                ->schema([
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
                                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                            if ($state) {
                                                                $barang = Barang::find($state);
                                                                if ($barang) {
                                                                    $set('kode_barang', $barang->kode_barang);
                                                                    $set('nama_barang', $barang->nama_barang);
                                                                    $set('harga', $barang->harga_jual);
                                                                    $set('stok_tersedia', $barang->stok);
                                                                    $set('satuan', $barang->satuan);

                                                                    // Reset jumlah ke kosong
                                                                    $set('jumlah', null);
                                                                    $set('subtotal', 0);

                                                                    // Recalculate total harga
                                                                    static::updateTotalHargaDanStatus($set, $get);
                                                                }
                                                            }
                                                        }),

                                                    Hidden::make('kode_barang'),
                                                    Hidden::make('nama_barang'),
                                                    Hidden::make('satuan'),
                                                    Hidden::make('stok_tersedia'),

                                                    TextInput::make('harga')
                                                        ->label('Harga')
                                                        ->prefix('Rp')
                                                        ->numeric()
                                                        ->required()
                                                        ->disabled()
                                                        ->live()
                                                        ->columnSpan(1)
                                                        ->reactive(),

                                                    TextInput::make('jumlah')
                                                        ->label('Jumlah')
                                                        ->numeric()
                                                        ->minValue(1)
                                                        ->required()
                                                        ->columnSpan(1)
                                                        ->reactive()
                                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                            // Hitung subtotal item saat ini
                                                            $harga = $get('harga') ?: 0;
                                                            $jumlah = (int)$state;
                                                            $subtotal = $harga * $jumlah;
                                                            $set('subtotal', $subtotal);

                                                            // Validasi stok - tidak bisa minus
                                                            $stokTersedia = $get('stok_tersedia') ?: 0;
                                                            if ($jumlah > $stokTersedia) {
                                                                Notification::make()
                                                                    ->title('Stok tidak mencukupi')
                                                                    ->body("Stok tersedia hanya {$stokTersedia}")
                                                                    ->danger()
                                                                    ->send();

                                                                $set('jumlah', $stokTersedia);
                                                                $subtotal = $harga * $stokTersedia;
                                                                $set('subtotal', $subtotal);
                                                            }

                                                            // Update total keseluruhan
                                                            static::updateTotalHargaDanStatus($set, $get);
                                                        }),

                                                    TextInput::make('subtotal')
                                                        ->label('Subtotal')
                                                        ->prefix('Rp')
                                                        ->disabled()
                                                        ->default(0)
                                                        ->required()
                                                        ->live()
                                                        ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                                                        ->columnSpan(1),
                                                ]),
                                        ])
                                        ->columns(1)
                                        ->itemLabel(
                                            fn(array $state): ?string =>
                                            isset($state['nama_barang']) ?
                                                "{$state['nama_barang']} ({$state['jumlah']} {$state['satuan']})" :
                                                null
                                        )
                                        ->collapsible()
                                        ->defaultItems(1)
                                        ->reorderable(false)
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            static::updateTotalHargaDanStatus($set, $get);
                                        }),

                                    Placeholder::make('stok_warning')
                                        ->label('Peringatan Stok')
                                        ->content('Jumlah barang yang diinput tidak boleh melebihi stok yang tersedia')
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Detail Pembayaran')
                                ->description('Informasi total dan pembayaran')
                                ->icon('heroicon-o-credit-card')
                                ->collapsible()
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('total_harga')
                                                ->label('Total Harga')
                                                ->prefix('Rp')
                                                ->disabled()
                                                ->required()
                                                ->dehydrated()
                                                ->live()
                                                ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                                                ->columnSpan(1),

                                            TextInput::make('total_bayar')
                                                ->label('Total Bayar')
                                                ->helperText('Jumlah yang dibayarkan oleh pelanggan')
                                                ->prefix('Rp')
                                                ->required()
                                                ->live()
                                                ->mask(RawJs::make('$money($input)'))
                                                ->reactive()
                                                ->lazy()
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    // Pastikan nilai numerik yang bersih
                                                    $numericValue = is_string($state) ? preg_replace('/[^0-9]/', '', $state) : $state;

                                                    // Set nilai yang sudah dibersihkan
                                                    $set('total_bayar', $numericValue);

                                                    // Ambil total harga dan bersihkan juga
                                                    $totalHarga = $get('total_harga') ?: 0;
                                                    if (is_string($totalHarga)) {
                                                        $totalHarga = (int) preg_replace('/[^0-9]/', '', $totalHarga);
                                                    }

                                                    // Hitung kembalian
                                                    $kembalian = max(0, (int)$numericValue - (int)$totalHarga);
                                                    $set('kembalian', $kembalian);

                                                    // Tentukan status pembayaran
                                                    $statusPembayaran = 'belum_bayar';
                                                    if ((int)$numericValue >= (int)$totalHarga && (int)$totalHarga > 0) {
                                                        $statusPembayaran = 'lunas';
                                                    } else if ((int)$numericValue > 0 && (int)$totalHarga > 0) {
                                                        $statusPembayaran = 'sebagian';
                                                    }

                                                    // Set status pembayaran
                                                    $set('status_pembayaran', $statusPembayaran);

                                                    // Sinkronkan dengan status faktur jika create_faktur = true
                                                    if ($get('create_faktur')) {
                                                        $fakturStatus = ($statusPembayaran === 'lunas') ? 'lunas' : 'belum_lunas';
                                                        $set('faktur.status', $fakturStatus);
                                                    }

                                                    // Debug: tambahkan notifikasi untuk konfirmasi perhitungan
                                                    Notification::make()
                                                        ->title('Pembayaran diperbarui')
                                                        ->body("Total: Rp" . number_format($totalHarga) . " | Bayar: Rp" . number_format($numericValue) . " | Status: $statusPembayaran")
                                                        ->success()
                                                        ->send();
                                                })
                                                ->columnSpan(1),

                                            TextInput::make('kembalian')
                                                ->label('Kembalian')
                                                ->prefix('Rp')
                                                ->disabled()
                                                ->required()
                                                ->live()
                                                ->default(0)
                                                ->dehydrated()
                                                ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                                                ->columnSpan(1),

                                            Select::make('metode_pembayaran')
                                                ->label('Metode Pembayaran')
                                                ->options([
                                                    'tunai' => 'Tunai',
                                                    'transfer' => 'Transfer Bank',
                                                    'qris' => 'QRIS',
                                                    'lainnya' => 'Lainnya',
                                                ])
                                                ->default('tunai')
                                                ->required()
                                                ->preload()
                                                ->searchable()
                                                ->columnSpan(1),

                                            Select::make('status_pembayaran')
                                                ->label('Status Pembayaran')
                                                ->options([
                                                    'belum_bayar' => 'Belum Bayar',
                                                    'sebagian' => 'Bayar Sebagian',
                                                    'lunas' => 'Lunas',
                                                ])
                                                ->default('belum_bayar')
                                                ->disabled()
                                                ->required()
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    // Sinkronkan dengan status faktur jika create_faktur = true
                                                    if ($get('create_faktur')) {
                                                        $fakturStatus = ($state === 'lunas') ? 'lunas' : 'belum_lunas';
                                                        $set('faktur.status', $fakturStatus);
                                                    }
                                                })
                                                ->columnSpan(1),
                                        ]),
                                ]),

                            Section::make('Informasi Faktur')
                                ->description('Tentukan informasi faktur')
                                ->icon('heroicon-o-document-duplicate')
                                ->collapsible()
                                ->schema([
                                    Toggle::make('create_faktur')
                                        ->label('Buat Faktur')
                                        ->helperText('Centang untuk membuat faktur otomatis')
                                        ->default(true)
                                        ->onIcon('heroicon-s-check')
                                        ->offIcon('heroicon-s-x-mark'),

                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('faktur.no_faktur')
                                                ->label('Nomor Faktur')
                                                ->default(fn() => 'INV-' . strtoupper(Str::random(8)))
                                                ->disabled()
                                                ->unique('fakturs', 'no_faktur', ignoreRecord: true)
                                                ->required()
                                                ->visibleOn('create')
                                                ->hidden(fn(callable $get) => !$get('create_faktur')),

                                            DatePicker::make('faktur.tanggal_faktur')
                                                ->label('Tanggal Faktur')
                                                ->default(now())
                                                ->required()
                                                ->visibleOn('create')
                                                ->hidden(fn(callable $get) => !$get('create_faktur')),

                                            Select::make('faktur.status')
                                                ->label('Status Faktur')
                                                ->options([
                                                    'lunas' => 'Lunas',
                                                    'belum_lunas' => 'Belum Lunas',
                                                ])
                                                ->default(
                                                    fn(callable $get) =>
                                                    $get('status_pembayaran') === 'lunas' ? 'lunas' : 'belum_lunas'
                                                )
                                                ->required()
                                                ->preload()
                                                ->searchable()
                                                ->visibleOn('create')
                                                ->hidden(fn(callable $get) => !$get('create_faktur')),

                                            Textarea::make('faktur.keterangan')
                                                ->label('Keterangan')
                                                ->placeholder('Masukkan catatan untuk faktur ini')
                                                ->columnSpan(2)
                                                ->visibleOn('create')
                                                ->hidden(fn(callable $get) => !$get('create_faktur')),
                                        ]),
                                ]),
                        ]),
                ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_transaksi')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Nomor transaksi disalin!')
                    ->copyMessageDuration(1500)
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('tanggal_transaksi')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar'),

                TextColumn::make('member.nama_member')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->default('Umum')
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_harga')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('status_pembayaran')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => [
                        'belum_bayar' => 'Belum Bayar',
                        'sebagian' => 'Bayar Sebagian',
                        'lunas' => 'Lunas',
                    ][$state] ?? $state)
                    ->color(fn(string $state): string => match ($state) {
                        'belum_bayar' => 'danger',
                        'sebagian' => 'warning',
                        'lunas' => 'success',
                        default => 'primary',
                    }),

                TextColumn::make('metode_pembayaran')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => [
                        'tunai' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                        'lainnya' => 'Lainnya',
                    ][$state] ?? $state)
                    ->color(fn(string $state): string => match ($state) {
                        'tunai' => 'primary',
                        'transfer' => 'success',
                        'qris' => 'info',
                        'lainnya' => 'secondary',
                        default => 'primary',
                    }),

                Tables\Columns\IconColumn::make('faktur_count')
                    ->label('Faktur')
                    ->counts('faktur')
                    ->tooltip(fn(int $state): string => "{$state} faktur terkait")
                    ->icon(fn(int $state): string => $state > 0 ? 'heroicon-o-document-check' : 'heroicon-o-document-minus')
                    ->color(fn(int $state): string => $state > 0 ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('status_pembayaran')
                    ->label('Status Pembayaran')
                    ->options([
                        'belum_bayar' => 'Belum Bayar',
                        'sebagian' => 'Bayar Sebagian',
                        'lunas' => 'Lunas',
                    ])
                    ->indicator('Status'),

                SelectFilter::make('metode_pembayaran')
                    ->label('Metode Pembayaran')
                    ->options([
                        'tunai' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                        'lainnya' => 'Lainnya',
                    ])
                    ->indicator('Metode'),

                Filter::make('tanggal_transaksi')
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
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_transaksi', '>=', $date),
                            )
                            ->when(
                                $data['tanggal_sampai'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_transaksi', '<=', $date),
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

                Filter::make('has_faktur')
                    ->label('Memiliki Faktur')
                    ->query(fn(Builder $query): Builder => $query->whereHas('faktur'))
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

                    Action::make('buatFaktur')
                        ->label('Buat Faktur')
                        ->icon('heroicon-o-document-plus')
                        ->color('success')
                        ->form([
                            TextInput::make('no_faktur')
                                ->label('Nomor Faktur')
                                ->default(fn() => 'INV-' . strtoupper(Str::random(8)))
                                ->disabled()
                                ->required(),

                            DatePicker::make('tanggal_faktur')
                                ->label('Tanggal Faktur')
                                ->default(now())
                                ->required(),

                            Select::make('status')
                                ->label('Status Faktur')
                                ->options([
                                    'lunas' => 'Lunas',
                                    'belum_lunas' => 'Belum Lunas',
                                ])
                                ->default('belum_lunas')
                                ->required(),

                            Textarea::make('keterangan')
                                ->label('Keterangan')
                                ->placeholder('Masukkan catatan untuk faktur ini'),
                        ])
                        ->action(function (Transaksi $record, array $data): void {
                            $record->faktur()->create([
                                'no_faktur' => $data['no_faktur'],
                                'tanggal_faktur' => $data['tanggal_faktur'],
                                'status' => $data['status'],
                                'keterangan' => $data['keterangan'],
                            ]);
                        })
                        ->requiresConfirmation()
                        ->visible(fn(Transaksi $record): bool => $record->faktur()->count() === 0),

                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-o-trash'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->icon('heroicon-o-trash'),
                ]),
            ])
            ->emptyStateHeading('Belum Ada Transaksi')
            ->emptyStateDescription('Buat transaksi baru dengan menekan tombol di bawah')
            ->emptyStateIcon('heroicon-o-shopping-cart')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat Transaksi')
                    ->icon('heroicon-o-plus'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\FakturRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransaksis::route('/'),
            'create' => Pages\CreateTransaksi::route('/create'),
            'edit' => Pages\EditTransaksi::route('/{record}/edit'),
        ];
    }
}
