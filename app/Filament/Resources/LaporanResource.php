<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanResource\Pages;
use App\Models\Laporan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;

class LaporanResource extends Resource
{
    protected static ?string $model = Laporan::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = '📝 Dokumen';

    protected static ?string $navigationLabel = 'Rekap Data';

    protected static ?string $slug = 'laporan';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('kode_laporan')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),
                Forms\Components\Select::make('jenis_laporan')
                    ->options([
                        'transaksi' => 'Transaksi',
                        'barang' => 'Barang',
                        'barang_masuk' => 'Barang Masuk',
                        'barang_keluar' => 'Barang Keluar',
                    ])
                    ->required()
                    ->disabled(),
                Forms\Components\DatePicker::make('tanggal_mulai')
                    ->required()
                    ->disabled(),
                Forms\Components\DatePicker::make('tanggal_akhir')
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('periode')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),
                Forms\Components\Textarea::make('catatan')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_laporan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->icon('heroicon-o-document-text'),

                Tables\Columns\BadgeColumn::make('jenis_laporan')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'transaksi' => 'Transaksi',
                        'barang' => 'Barang',
                        'barang_masuk' => 'Barang Masuk',
                        'barang_keluar' => 'Barang Keluar',
                        default => $state
                    })
                    ->colors([
                        'primary' => fn($state) => $state === 'transaksi',
                        'success' => fn($state) => $state === 'barang',
                        'warning' => fn($state) => $state === 'barang_masuk',
                        'danger' => fn($state) => $state === 'barang_keluar',
                    ])
                    ->icons([
                        'heroicon-o-banknotes' => fn($state) => $state === 'transaksi',
                        'heroicon-o-cube' => fn($state) => $state === 'barang',
                        'heroicon-o-arrow-down-tray' => fn($state) => $state === 'barang_masuk',
                        'heroicon-o-arrow-up-tray' => fn($state) => $state === 'barang_keluar',
                    ]),

                Tables\Columns\TextColumn::make('periode')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-calendar'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable()
                    ->tooltip(fn(Laporan $record): string => $record->created_at->diffForHumans())
                    ->icon('heroicon-o-clock'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn(Laporan $record): string => $record->updated_at->diffForHumans())
                    ->icon('heroicon-o-pencil'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_laporan')
                    ->options([
                        'transaksi' => 'Transaksi',
                        'barang' => 'Barang',
                        'barang_masuk' => 'Barang Masuk',
                        'barang_keluar' => 'Barang Keluar',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Action::make('exportPdf')
                ->label('Export Dokumen')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn (Laporan $record): string => route('laporan.export-pdf', $record))
                ->openUrlInNewTab()
                ->tooltip('Unduh sebagai PDF'),

            Tables\Actions\ActionGroup::make([
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit Laporan'),
                    
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Hapus Laporan'),
                    
                Tables\Actions\RestoreAction::make()
                    ->tooltip('Pulihkan Laporan'),
                    
                Tables\Actions\ForceDeleteAction::make()
                    ->tooltip('Hapus Permanen')
                    ->requiresConfirmation(),
            ])
        ])
            ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->icon('heroicon-o-trash')
                    ->modalHeading('Hapus Laporan Terpilih'),
                    
                Tables\Actions\ForceDeleteBulkAction::make()
                    ->icon('heroicon-o-exclamation-triangle')
                    ->modalHeading('Hapus Permanen Laporan Terpilih'),
                    
                Tables\Actions\RestoreBulkAction::make()
                    ->icon('heroicon-o-arrow-path')
                    ->modalHeading('Pulihkan Laporan Terpilih'),

            ]),
        ])
        ->emptyStateHeading('Belum ada laporan')
        ->emptyStateDescription('Buat laporan baru untuk mulai mengelola data')
        ->emptyStateIcon('heroicon-o-document-text')
        ->striped()
        ->poll('10s')
        ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporans::route('/'),
            'create' => Pages\CreateLaporan::route('/create'),
            'edit' => Pages\EditLaporan::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
