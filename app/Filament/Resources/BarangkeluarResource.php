<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangKeluarResource\Pages;
use App\Models\BarangKeluar;
use App\Models\Barang;
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
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Actions\Action as TableAction;

class BarangkeluarResource extends Resource
{
    protected static ?string $model = Barangkeluar::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-left-start-on-rectangle';

    protected static ?string $navigationGroup = '🛒 Flow Barang'; 

    protected static ?string $navigationLabel = 'Barang Keluar';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'barang-keluar';

    protected static ?string $recordTitleAttribute = 'no_referensi';

    protected static ?string $modelLabel = 'Barang Keluar';

    protected static ?string $pluralModelLabel = 'Barang Keluar';

    protected static ?string $activeNavigationIcon = 'heroicon-s-arrow-left-circle';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('tanggal_keluar', Carbon::today())->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = static::getModel()::whereDate('tanggal_keluar', Carbon::today())->count();
        
        if ($count > 10) {
            return 'danger';
        }
        
        if ($count > 5) {
            return 'warning';
        }
        
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Barang Keluar')
                    ->description('Masukkan informasi detail barang keluar')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Select::make('barang_id')
                                    ->label('Pilih Barang')
                                    ->relationship('barang', 'nama_barang')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        if($state) {
                                            $barang = Barang::find($state);
                                            if($barang) {
                                                $set('harga_jual', $barang->harga_jual ?? 0);
                                            }
                                        }
                                    }),
                                
                                TextInput::make('jumlah_barang_keluar')
                                    ->label('Jumlah')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->suffixIcon('heroicon-o-cube')
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $hargaJual = (float) $get('harga_jual');
                                        $jumlah = (int) $get('jumlah_barang_keluar');
                                        $total = $hargaJual * $jumlah;
                                        $set('total_harga', $total);
                                    }),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                TextInput::make('harga_jual')
                                    ->label('Harga Jual per Unit')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $hargaJual = (float) $get('harga_jual');
                                        $jumlah = (int) $get('jumlah_barang_keluar');
                                        $total = $hargaJual * $jumlah;
                                        $set('total_harga', $total);
                                    }),
                                    
                                TextInput::make('total_harga')
                                    ->label('Total Harga')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled(),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                TextInput::make('no_referensi')
                                    ->label('Nomor Referensi')
                                    ->required()
                                    ->maxLength(255)
                                    ->default(function() {
                                        return 'BKL-' . strtoupper(Str::random(8));
                                    })
                                    ->helperText('Kode referensi keluar barang')
                                    ->prefixIcon('heroicon-o-document-text'),
                                    
                                DatePicker::make('tanggal_keluar')
                                    ->label('Tanggal Keluar')
                                    ->required()
                                    ->default(now())
                                    ->displayFormat('d F Y')
                                    ->weekStartsOnMonday()
                                    ->closeOnDateSelection()
                                    ->native(false),
                            ]),
                    ]),
                    
                Section::make('Informasi Transaksi')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('transaksi_id')
                                    ->label('Transaksi')
                                    ->relationship('transaksi', 'no_transaksi')
                                    ->preload()
                                    ->searchable()
                                    ->required()
                                    ->helperText('Pilih transaksi terkait'),
                                    
                                Select::make('user_id')
                                    ->label('Petugas')
                                    ->relationship('user', 'name')
                                    ->preload()
                                    ->searchable()
                                    ->required()
                                    ->default(filament()->auth()->id())
                                    ->helperText('Petugas yang memproses'),
                            ]),
                    ]),
            ]);
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
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->weight(FontWeight::Bold),
                    
                TextColumn::make('barang.nama_barang')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('jumlah_barang_keluar')
                    ->label('Jumlah')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('danger'),
                    
                TextColumn::make('harga_jual')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),
                    
                TextColumn::make('total_harga')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->sortable(),
                    
                TextColumn::make('tanggal_keluar')
                    ->label('Tanggal Keluar')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->color('warning'),
                    
                TextColumn::make('transaksi.no_transaksi')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->toggleable(),
                    
                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->searchable()
                    ->toggleable(),
                    
                TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('updated_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('barang_id')
                    ->label('Jenis Barang')
                    ->relationship('barang', 'nama_barang')
                    ->preload()
                    ->searchable()
                    ->indicator('Barang'),
                    
                Filter::make('created_today')
                    ->label('Dibuat Hari Ini')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', Carbon::today()))
                    ->indicator('Hari ini'),
                    
                Filter::make('tanggal_keluar')
                    ->label('Periode')
                    ->form([
                        DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                        DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_keluar', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_keluar', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators[] = Indicator::make('Dari ' . Carbon::parse($data['dari_tanggal'])->format('d/m/Y'))
                                ->color('success');
                        }
                        
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = Indicator::make('Sampai ' . Carbon::parse($data['sampai_tanggal'])->format('d/m/Y'))
                                ->color('success');
                        }
                        
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('info')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->color('warning')
                    ->icon('heroicon-o-pencil'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s')
            ->emptyStateIcon('heroicon-o-arrow-left-circle')
            ->emptyStateHeading('Belum ada data barang keluar')
            ->emptyStateDescription('Silakan tambahkan data barang keluar baru dengan klik tombol di bawah')
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label('Tambah Barang Keluar')
                    ->url(route('filament.admin.resources.barang-keluar.create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
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
