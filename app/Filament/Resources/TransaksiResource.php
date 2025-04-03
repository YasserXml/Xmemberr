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
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = '💰 Penjualan Barang';

    protected static ?string $navigationLabel = 'Transaksi Barang';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'Transaksi';

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
                                        ->icon('heroicon-o-receipt')
                                        ->collapsible()
                                        ->schema([
                                            Grid::make(3)
                                                ->schema([
                                                    TextInput::make('no_transaksi')
                                                        ->label('Nomor Transaksi')
                                                        ->default(fn () => 'TRX-' . strtoupper(Str::random(8)))
                                                        ->disabled()
                                                        ->required()
                                                        ->unique(Transaksi::class, 'no_transaksi', ignoreRecord: true)
                                                        ->columnSpan(1),
                                                    
                                                    DatePicker::make('tanggal_transaksi')
                                                        ->label('Tanggal Transaksi')
                                                        ->default(now())
                                                        ->required()
                                                        ->columnSpan(1),
                                                    
                                                    Select::make('user_id')
                                                        ->label('Kasir')
                                                        ->relationship('user', 'name')
                                                        ->default(fn () => Auth::id())
                                                        ->required()
                                                        ->searchable()
                                                        ->preload()
                                                        ->columnSpan(1),
                                                    
                                                    Select::make('member_id')
                                                        ->label('Member')
                                                        ->relationship('member', 'nama_member')
                                                        ->searchable()
                                                        ->preload()
                                                        ->createOptionForm([
                                                            TextInput::make('kode_member')
                                                                ->label('Kode Member')
                                                                ->default(fn () => 'M-' . strtoupper(Str::random(6)))
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
                    
                    Step::make('Item Transaksi')
                        ->icon('heroicon-o-clipboard-list')
                        ->description('Tambahkan item yang dibeli')
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
                                                ->afterStateUpdated(function ($state, callable $set, $get) {
                                                    if ($state) {
                                                        $barang = Barang::find($state);
                                                        if ($barang) {
                                                            $set('kode_barang', $barang->kode_barang);
                                                            $set('nama_barang', $barang->nama_barang);
                                                            $set('harga', $barang->harga_jual);
                                                            $set('stok_tersedia', $barang->stok);
                                                            $set('satuan', $barang->satuan);
                                                            
                                                            $jumlah = $get('jumlah') ?: 1;
                                                            $set('subtotal', $barang->harga_jual * $jumlah);
                                                        }
                                                    }
                                                })
                                                ->columnSpan(3),
                                            
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
                                                ->columnSpan(1)
                                                ->reactive(),
                                            
                                            TextInput::make('jumlah')
                                                ->label('Jumlah')
                                                ->numeric()
                                                ->default(1)
                                                ->minValue(1)
                                                ->required()
                                                ->columnSpan(1)
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, callable $set, $get) {
                                                    $harga = $get('harga') ?: 0;
                                                    $set('subtotal', $harga * $state);
                                                    
                                                    // Validasi stok
                                                    $stokTersedia = $get('stok_tersedia') ?: 0;
                                                    if ($state > $stokTersedia) {
                                                        $set('jumlah', $stokTersedia);
                                                        $set('subtotal', $harga * $stokTersedia);
                                                    }
                                                }),
                                            
                                            TextInput::make('subtotal')
                                                ->label('Subtotal')
                                                ->prefix('Rp')
                                                ->numeric()
                                                ->disabled()
                                                ->default(0)
                                                ->required()
                                                ->columnSpan(1),
                                        ]),
                                ])
                                ->columns(1)
                                ->itemLabel(fn (array $state): ?string => 
                                    isset($state['nama_barang']) ? 
                                    "{$state['nama_barang']} ({$state['jumlah']} {$state['satuan']})" : 
                                    null
                                )
                                ->collapsible()
                                ->defaultItems(1)
                                ->reorderable(false)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, $get) {
                                    $totalHarga = collect($state ?? [])->sum('subtotal');
                                    $set('total_harga', $totalHarga);
                                    
                                    $diskon = $get('diskon') ?: 0;
                                    $grandTotal = $totalHarga - $diskon;
                                    $set('grand_total', $grandTotal);
                                    
                                    $totalBayar = $get('total_bayar') ?: 0;
                                    $kembalian = max(0, $totalBayar - $grandTotal);
                                    $set('kembalian', $kembalian);
                                }),
                            
                            Placeholder::make('stok_warning')
                                ->label('Peringatan Stok')
                                ->content('Jumlah barang yang diinput tidak boleh melebihi stok yang tersedia')
                                ->columnSpanFull(),
                        ]),
                    
                    Step::make('Pembayaran')
                        ->icon('heroicon-o-currency-dollar')
                        ->description('Selesaikan pembayaran')
                        ->schema([
                            Section::make('Detail Pembayaran')
                                ->description('Informasi total dan pembayaran')
                                ->icon('heroicon-o-cash')
                                ->collapsible()
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('total_harga')
                                                ->label('Total Harga')
                                                ->prefix('Rp')
                                                ->disabled()
                                                ->required()
                                                ->columnSpan(1),
                                            
                                            TextInput::make('diskon')
                                                ->label('Diskon')
                                                ->prefix('Rp')
                                                ->default(0)
                                                ->numeric()
                                                ->required()
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, callable $set, $get) {
                                                    $totalHarga = $get('total_harga') ?: 0;
                                                    $grandTotal = $totalHarga - $state;
                                                    $set('grand_total', $grandTotal);
                                                    
                                                    $totalBayar = $get('total_bayar') ?: 0;
                                                    $kembalian = max(0, $totalBayar - $grandTotal);
                                                    $set('kembalian', $kembalian);
                                                })
                                                ->columnSpan(1),
                                            
                                            TextInput::make('grand_total')
                                                ->label('Grand Total')
                                                ->prefix('Rp')
                                                ->disabled()
                                                ->required()
                                                ->columnSpan(1),
                                            
                                            TextInput::make('total_bayar')
                                                ->label('Total Bayar')
                                                ->prefix('Rp')
                                                ->default(0)
                                                ->numeric()
                                                ->required()
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, callable $set, $get) {
                                                    $grandTotal = $get('grand_total') ?: 0;
                                                    $kembalian = max(0, $state - $grandTotal);
                                                    $set('kembalian', $kembalian);
                                                    
                                                    // Set status pembayaran
                                                    if ($state <= 0) {
                                                        $set('status_pembayaran', 'belum_bayar');
                                                    } elseif ($state < $grandTotal) {
                                                        $set('status_pembayaran', 'sebagian');
                                                    } else {
                                                        $set('status_pembayaran', 'lunas');
                                                    }
                                                })
                                                ->columnSpan(1),
                                            
                                            TextInput::make('kembalian')
                                                ->label('Kembalian')
                                                ->prefix('Rp')
                                                ->disabled()
                                                ->default(0)
                                                ->required()
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
                                        ->offIcon('heroicon-s-x'),
                                    
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('faktur.no_faktur')
                                                ->label('Nomor Faktur')
                                                ->default(fn () => 'INV-' . strtoupper(Str::random(8)))
                                                ->disabled()
                                                ->unique('fakturs', 'no_faktur', ignoreRecord: true)
                                                ->required()
                                                ->visibleOn('create')
                                                ->hidden(fn (callable $get) => !$get('create_faktur')),
                                            
                                            DatePicker::make('faktur.tanggal_faktur')
                                                ->label('Tanggal Faktur')
                                                ->default(now())
                                                ->required()
                                                ->visibleOn('create')
                                                ->hidden(fn (callable $get) => !$get('create_faktur')),
                                            
                                            Select::make('faktur.status')
                                                ->label('Status Faktur')
                                                ->options([
                                                    'lunas' => 'Lunas',
                                                    'belum_lunas' => 'Belum Lunas',
                                                ])
                                                ->default('belum_lunas')
                                                ->required()
                                                ->visibleOn('create')
                                                ->hidden(fn (callable $get) => !$get('create_faktur')),
                                            
                                            Textarea::make('faktur.keterangan')
                                                ->label('Keterangan')
                                                ->placeholder('Masukkan catatan untuk faktur ini')
                                                ->columnSpan(2)
                                                ->visibleOn('create')
                                                ->hidden(fn (callable $get) => !$get('create_faktur')),
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
                    ->label('Member')
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
                
                TextColumn::make('grand_total')
                    ->label('Grand Total')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\BadgeColumn::make('status_pembayaran')
                    ->label('Status')
                    ->enum([
                        'belum_bayar' => 'Belum Bayar',
                        'sebagian' => 'Bayar Sebagian',
                        'lunas' => 'Lunas',
                    ])
                    ->colors([
                        'danger' => 'belum_bayar',
                        'warning' => 'sebagian',
                        'success' => 'lunas',
                    ]),
                
                Tables\Columns\BadgeColumn::make('metode_pembayaran')
                    ->label('Metode')
                    ->enum([
                        'tunai' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                        'lainnya' => 'Lainnya',
                    ])
                    ->colors([
                        'primary' => 'tunai',
                        'success' => 'transfer',
                        'info' => 'qris',
                        'secondary' => 'lainnya',
                    ]),
                
                Tables\Columns\IconColumn::make('fakturs_count')
                    ->label('Faktur')
                    ->counts('fakturs')
                    ->tooltip(fn (int $state): string => "{$state} faktur terkait")
                    ->icon(fn (int $state): string => $state > 0 ? 'heroicon-o-document-check' : 'heroicon-o-document-minus')
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger'),
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
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_transaksi', '>=', $date),
                            )
                            ->when(
                                $data['tanggal_sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_transaksi', '<=', $date),
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
                
                Filter::make('has_fakturs')
                    ->label('Memiliki Faktur')
                    ->query(fn (Builder $query): Builder => $query->whereHas('fakturs'))
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
                                ->default(fn () => 'INV-' . strtoupper(Str::random(8)))
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
                            $record->fakturs()->create([
                                'no_faktur' => $data['no_faktur'],
                                'tanggal_faktur' => $data['tanggal_faktur'],
                                'status' => $data['status'],
                                'keterangan' => $data['keterangan'],
                            ]);
                        })
                        ->requiresConfirmation()
                        ->visible(fn (Transaksi $record): bool => $record->fakturs()->count() === 0),
                    
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
            RelationManagers\FaktursRelationManager::class,
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
