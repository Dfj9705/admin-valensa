<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IngresoServicioResource\Pages;
use App\Filament\Resources\IngresoServicioResource\RelationManagers;
use App\Models\IngresoServicio;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IngresoServicioResource extends Resource
{
    protected static ?string $model = IngresoServicio::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Finanzas';
    protected static ?string $navigationLabel = 'Ingresos por Servicios';
    protected static ?string $modelLabel = 'Ingreso por Servicio';
    protected static ?string $pluralModelLabel = 'Ingresos por Servicios';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Detalle del ingreso')
                ->columns(2)
                ->schema([
                    DatePicker::make('ing_fecha')
                        ->label('Fecha')
                        ->required()
                        ->default(now()),

                    TextInput::make('ing_monto')
                        ->label('Ingreso')
                        ->prefix('Q')
                        ->numeric()
                        ->required(),

                    TextInput::make('ing_lugar')
                        ->label('Lugar / Servicio')
                        ->required()
                        ->columnSpanFull(),

                    Textarea::make('ing_observaciones')
                        ->label('Observaciones')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('ing_fecha', 'desc')
            ->columns([
                TextColumn::make('ing_fecha')
                    ->label('Fecha')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('ing_lugar')
                    ->label('Lugar / Servicio')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('ing_monto')
                    ->label('Ingreso')
                    ->numeric(2, '.', ',', 2)
                    ->prefix('GTQ ')
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total')
                            ->numeric(2, '.', ',', 2)
                            ->prefix('GTQ ')
                    ),

                TextColumn::make('ing_observaciones')
                    ->label('Observaciones')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
            ])
            ->filters([
                Filter::make('rango_fechas')
                    ->label('Rango de fechas')
                    ->form([
                        Forms\Components\DatePicker::make('desde')->label('Desde'),
                        Forms\Components\DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'] ?? null, fn($q, $d) => $q->whereDate('ing_fecha', '>=', $d))
                            ->when($data['hasta'] ?? null, fn($q, $h) => $q->whereDate('ing_fecha', '<=', $h));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIngresoServicios::route('/'),
            'create' => Pages\CreateIngresoServicio::route('/create'),
            'edit' => Pages\EditIngresoServicio::route('/{record}/edit'),
        ];
    }
}
