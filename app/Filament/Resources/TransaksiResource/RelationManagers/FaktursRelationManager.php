<?php

namespace App\Filament\Resources\TransaksiResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FaktursRelationManager extends RelationManager
{
    protected static string $relationship = 'fakturs';

    protected static ?string $recordTitleAttribute = 'no_faktur';

    protected static ?string $title = 'Faktur';

    protected static ?string $modelLabel = 'Faktur';

    protected static ?string $pluralModelLabel = 'Faktur';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('no_faktur')
                    ->label('Nomor Faktur')
                    ->default(fn () => 'INV-' . strtoupper(Str::random(8)))
                    ->disabled()
                    ->required()
                    ->unique('fakturs', 'no_faktur', ignoreRecord: true),

                Forms\Components\DatePicker::make('tanggal_faktur')
                    ->label('Tanggal Faktur')
                    ->default(now())
                    ->required(),

                Forms\Components\Select::make('status')
                    ->label('Status Faktur')
                    ->options([
                        'lunas' => 'Lunas',
                        'belum_lunas' => 'Belum Lunas',
                    ])
                    ->default('belum_lunas')
                    ->required(),

                Forms\Components\Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->placeholder('Masukkan catatan untuk faktur ini'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_faktur')
                    ->label('No. Faktur')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Nomor faktur disalin!')
                    ->copyMessageDuration(1500)
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('tanggal_faktur')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->enum([
                        'lunas' => 'Lunas',
                        'belum_lunas' => 'Belum Lunas',
                    ])
                    ->colors([
                        'success' => 'lunas',
                        'danger' => 'belum_lunas',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Faktur')
                    ->options([
                        'lunas' => 'Lunas',
                        'belum_lunas' => 'Belum Lunas',
                    ]),

                Tables\Filters\Filter::make('tanggal_faktur')
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
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat Faktur')
                    ->icon('heroicon-o-document-plus'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),
                ]),
            ])
            ->emptyStateHeading('Belum Ada Faktur')
            ->emptyStateDescription('Buat faktur baru dengan menekan tombol di atas')
            ->emptyStateIcon('heroicon-o-document')
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}