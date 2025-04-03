<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberResource\Pages;
use App\Filament\Resources\MemberResource\RelationManagers;
use App\Models\Member;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = '🧑‍🤝‍🧑 Pelanggan';

    protected static ?string $navigationLabel = 'Pelanggan Terdaftar';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'member';

    protected static ?string $recordTitleAttribute = 'nama_member';

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
                Forms\Components\Section::make('Informasi Member')
                    ->description('Masukkan data member dengan lengkap')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        TextInput::make('kode_member')
                            ->label('Kode Member')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Masukkan kode member')
                            ->autocapitalize('characters')
                            ->prefixIcon('heroicon-o-identification'),
                            
                        TextInput::make('nama_member')
                            ->label('Nama Member')
                            ->required()
                            ->placeholder('Masukkan nama lengkap')
                            ->prefixIcon('heroicon-o-user'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Kontak')
                    ->description('Informasi kontak member')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->placeholder('email@example.com')
                            ->prefixIcon('heroicon-o-envelope'),
                        
                        TextInput::make('telepon')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->placeholder('08xxxxxxxxxx')
                            ->prefixIcon('heroicon-o-device-phone-mobile'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Alamat')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Textarea::make('alamat')
                            ->label('Alamat Lengkap')
                            ->placeholder('Masukkan alamat lengkap')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_member')
                    ->label('Kode Member')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('nama_member')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope')
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('telepon')
                    ->label('Telepon')
                    ->searchable()
                    ->icon('heroicon-o-phone'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Daftar')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
                    
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->tooltip('Aksi'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
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
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
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
