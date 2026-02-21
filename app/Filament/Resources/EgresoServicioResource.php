<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EgresoServicioResource\Pages;
use App\Filament\Resources\EgresoServicioResource\RelationManagers;
use App\Models\EgresoServicio;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EgresoServicioResource extends Resource
{
    protected static ?string $model = EgresoServicio::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';
    protected static ?string $navigationGroup = 'Servicios';
    protected static ?string $navigationLabel = 'Egresos';
    protected static ?string $modelLabel = 'Egreso';
    protected static ?string $pluralModelLabel = 'Egresos';
    protected static ?int $navigationSort = 2;
    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Detalle del egreso')
                ->columns(2)
                ->schema([
                    DatePicker::make('egr_fecha')
                        ->label('Fecha')
                        ->required()
                        ->default(now()),

                    TextInput::make('egr_monto')
                        ->label('Egreso')
                        ->prefix('Q')
                        ->numeric()
                        ->required(),

                    TextInput::make('egr_concepto')
                        ->label('Concepto')
                        ->required()
                        ->columnSpanFull(),

                    TextInput::make('egr_lugar')
                        ->label('Lugar / Servicio')
                        ->required()
                        ->columnSpanFull(),

                    Select::make('egr_metodo_pago')
                        ->label('Método de pago')
                        ->options([
                            'efectivo' => 'Efectivo',
                            'transferencia' => 'Transferencia',
                            'tarjeta' => 'Tarjeta',
                        ])
                        ->native(false)
                        ->searchable()
                        ->preload(),

                    TextInput::make('egr_referencia')
                        ->label('Referencia')
                        ->placeholder('Boleta / transferencia / etc.')
                        ->maxLength(80),

                    Textarea::make('egr_observaciones')
                        ->label('Observaciones')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('egr_fecha', 'desc')
            ->columns([
                TextColumn::make('egr_fecha')
                    ->label('Fecha')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('egr_concepto')
                    ->label('Concepto')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('egr_lugar')
                    ->label('Lugar / Servicio')
                    ->searchable()
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('egr_monto')
                    ->label('Egreso')
                    ->numeric(2, '.', ',', 2)
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Total')
                            ->numeric(2, '.', ',', 2)
                            ->prefix('GTQ ')
                    ),

                TextColumn::make('egr_metodo_pago')
                    ->label('Pago')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('egr_referencia')
                    ->label('Referencia')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('egr_observaciones')
                    ->label('Observaciones')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('rango_fechas')
                    ->label('Rango de fechas')
                    ->form([
                        Forms\Components\DatePicker::make('desde')->label('Desde'),
                        Forms\Components\DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'] ?? null, fn($q, $d) => $q->whereDate('egr_fecha', '>=', $d))
                            ->when($data['hasta'] ?? null, fn($q, $h) => $q->whereDate('egr_fecha', '<=', $h));
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
            'index' => Pages\ListEgresoServicios::route('/'),
            'create' => Pages\CreateEgresoServicio::route('/create'),
            'edit' => Pages\EditEgresoServicio::route('/{record}/edit'),
        ];
    }
}
