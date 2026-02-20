<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoriaGastoResource\Pages;
use App\Filament\Resources\CategoriaGastoResource\RelationManagers;
use App\Models\CategoriaGasto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoriaGastoResource extends Resource
{
    protected static ?string $model = CategoriaGasto::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Finanzas';
    protected static ?string $navigationLabel = 'Categorías de gastos';
    protected static ?string $modelLabel = 'Categoría de gasto';
    protected static ?string $pluralModelLabel = 'Categorías de gastos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('cat_nombre')
                ->label('Nombre')
                ->required()
                ->maxLength(100),

            Forms\Components\Toggle::make('cat_activo')
                ->label('Activo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('cat_nombre')
            ->columns([
                Tables\Columns\TextColumn::make('cat_nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('cat_activo')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('cat_activo')->label('Activo'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategoriaGastos::route('/'),
            'create' => Pages\CreateCategoriaGasto::route('/create'),
            'edit' => Pages\EditCategoriaGasto::route('/{record}/edit'),
        ];
    }
}
