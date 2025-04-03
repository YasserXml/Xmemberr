<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FakturResource\Pages;
use App\Models\Faktur;
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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Card;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FakturResource extends Resource
{
    protected static ?string $model = Faktur::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = '💰 Penjualan Barang';

    protected static ?string $navigationLabel = 'Faktur';

    protected static ?string $slug = 'Faktur';

    protected static ?int $navigationSort = 8;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string
    {
        return static::getModel()::where('status', 'belum_lunas')->count() > 0 
            ? 'warning' 
            : 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Section::make('Informasi Faktur')
                            ->description('Detail informasi faktur')
                            ->icon('heroicon-o-document')
                            ->schema([
                                TextInput::make('no_faktur')
                                    ->label('Nomor Faktur')
                                    ->default(fn () => 'INV-' . strtoupper(Str::random(8)))
                                    ->disabled()
                                    ->required()
                                    ->unique(Faktur::class, 'no_faktur', ignoreRecord: true),
                                
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
                                    ->required()
                                    ->reactive(),
                                
                                Textarea::make('keterangan')
                                    ->label('Keterangan')
                                    ->placeholder('Masukkan catatan untuk faktur ini')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                        
                        Section::make('Data Transaksi')
                            ->description('Transaksi terkait faktur ini')
                            ->icon('heroicon-o-shopping-cart')
                            ->schema([
                                Select::make('transaksi_id')
                                    ->label('Pilih Transaksi')
                                    ->relationship('transaksi', 'no_transaksi')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        // Form pembuatan transaksi cepat jika diperlukan
                                    ])
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state) {
                                            $transaksi = \App\Models\Transaksi::find($state);
                                            if ($transaksi) {
                                                $set('info_transaksi', "No: {$transaksi->no_transaksi} | Tanggal: " . 
                                                    Carbon::parse($transaksi->tanggal_transaksi)->format('d/m/Y') . 
                                                    " | Total: Rp " . number_format($transaksi->grand_total, 0, ',', '.'));
                                            }
                                        }
                                    }),
                                
                                TextInput::make('info_transaksi')
                                    ->label('Informasi Transaksi')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                            ])
                            ->columns(1),
                    ])
                    ->columnSpan(['lg' => 2]),
                
                // Forms\Components\Group::make()
                //     ->schema([
                //         Section::make('Status & Metadata')
                //             ->schema([
                //                 Forms\Components\Placeholder::make('created_at')
                //                     ->label('Dibuat Pada')
                //                     ->content(fn (Faktur $record): ?string => $record->created_at?->diffForHumans()),

                //                 Forms\Components\Placeholder::make('updated_at')
                //                     ->label('Diperbarui Pada')
                //                     ->content(fn (Faktur $record): ?string => $record->updated_at?->diffForHumans()),
                                
                //                 // Forms\Components\Badge::make('status_badge')
                //                 //     ->label('Status Pembayaran')
                //                 //     ->dehydrated(false)
                //                 //     ->content(fn (?Faktur $record) => $record?->status === 'lunas' ? 'Lunas' : 'Belum Lunas')
                //                 //     ->color(fn (?Faktur $record) => $record?->status === 'lunas' ? 'success' : 'warning'),
                //             ]),
                //     ])
                    // ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_faktur')
                    ->label('No. Faktur')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Nomor faktur disalin!')
                    ->copyMessageDuration(1500)
                    ->weight('bold')
                    ->color('primary'),
                
                TextColumn::make('tanggal_faktur')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar'),
                
                TextColumn::make('transaksi.no_transaksi')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->transaksi_id ? 
                        '/admin/transaksis/' . $record->transaksi_id : null),
                
                TextColumn::make('transaksi.grand_total')
                    ->label('Nilai Transaksi')
                    ->money('IDR')
                    ->sortable(),
                
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'lunas' => 'Lunas',
                        'belum_lunas' => 'Belum Lunas',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        'lunas' => 'success',
                        'belum_lunas' => 'danger',
                        default => 'gray',
                    }),
                
                TextColumn::make('transaksi.user.name')
                    ->label('Kasir')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Faktur')
                    ->options([
                        'lunas' => 'Lunas',
                        'belum_lunas' => 'Belum Lunas',
                    ])
                    ->indicator('Status'),
                
                Filter::make('tanggal_faktur')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_faktur', '>=', $date),
                            )
                            ->when(
                                $data['tanggal_sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_faktur', '<=', $date),
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
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat')
                        ->icon('heroicon-o-eye'),
                    
                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->icon('heroicon-o-pencil'),
                    
                    Tables\Actions\Action::make('printFaktur')
                        ->label('Cetak Faktur')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->url(fn (Faktur $record): string => route('faktur.print', $record))
                        ->openUrlInNewTab(),
                    
                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-o-trash'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('updateStatus')
                        ->label('Update Status')
                        ->icon('heroicon-o-check-circle')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->status = 'lunas';
                                $record->save();
                            }
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('Belum Ada Faktur')
            ->emptyStateDescription('Buat faktur baru dengan menekan tombol di bawah')
            ->emptyStateIcon('heroicon-o-document')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat Faktur')
                    ->icon('heroicon-o-plus'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
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
            'index' => Pages\ListFakturs::route('/'),
            'create' => Pages\CreateFaktur::route('/create'),
            'edit' => Pages\EditFaktur::route('/{record}/edit'),
        ];
    }
}
