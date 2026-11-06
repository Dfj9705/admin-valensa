<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BancoResource\Pages;
use App\Models\Banco;
use App\Support\Access;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BancoResource extends Resource
{
    protected static ?string $model = Banco::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Bancos';
    protected static ?string $modelLabel = 'Banco';
    protected static ?string $pluralModelLabel = 'Bancos';

    protected static ?string $recordTitleAttribute = 'ban_nombre';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('ban_nombre')
                ->label('Nombre del banco')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),

            Forms\Components\Toggle::make('ban_activo')
                ->label('Activo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('ban_nombre')
            ->columns([
                Tables\Columns\TextColumn::make('ban_nombre')
                    ->label('Banco')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cuentas_count')
                    ->label('Cuentas')
                    ->counts('cuentas'),

                Tables\Columns\IconColumn::make('ban_activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('ban_activo')
                    ->label('Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBancos::route('/'),
            'create' => Pages\CreateBanco::route('/create'),
            'edit' => Pages\EditBanco::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Access::allows('bancos.ver');
    }

    public static function canCreate(): bool
    {
        return Access::allows('bancos.crear');
    }

    public static function canEdit($record): bool
    {
        return Access::allows('bancos.editar');
    }

    public static function canDelete($record): bool
    {
        return Access::allows('bancos.eliminar')
            && !$record->cuentas()->exists();
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
