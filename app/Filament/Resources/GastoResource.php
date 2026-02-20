<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GastoResource\Pages;
use App\Filament\Resources\GastoResource\RelationManagers;
use App\Models\Gasto;
use Auth;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GastoResource extends Resource
{
    protected static ?string $model = Gasto::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Finanzas';
    protected static ?string $navigationLabel = 'Gastos / Compras';
    protected static ?string $modelLabel = 'Gasto';
    protected static ?string $pluralModelLabel = 'Gastos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Detalle')
                ->columns(2)
                ->schema([
                    DatePicker::make('gas_fecha')
                        ->label('Fecha')
                        ->required()
                        ->default(now()),

                    Select::make('gas_tipo')
                        ->label('Tipo')
                        ->options([
                            'compra' => 'Compra',
                            'gasto' => 'Gasto',
                        ])
                        ->required()
                        ->default('gasto'),

                    Select::make('cat_id')
                        ->label('Categoría')
                        ->relationship('categoria', 'cat_nombre')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            TextInput::make('cat_nombre')
                                ->label('Nombre')
                                ->required()
                                ->maxLength(100),
                            Toggle::make('cat_activo')
                                ->label('Activo')
                                ->default(true),
                        ])
                        ->required(),

                    TextInput::make('gas_monto')
                        ->label('Monto')
                        ->prefix('Q')
                        ->numeric()
                        ->required(),

                    TextInput::make('gas_referencia')
                        ->label('Referencia (opcional)')
                        ->maxLength(50),

                    Textarea::make('gas_descripcion')
                        ->label('Descripción')
                        ->required()
                        ->columnSpanFull()
                        ->maxLength(255),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('gas_fecha', 'desc')
            ->columns([
                TextColumn::make('gas_fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('gas_tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => $state === 'compra' ? 'Compra' : 'Gasto')
                    ->sortable(),

                TextColumn::make('categoria.cat_nombre')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('gas_descripcion')
                    ->label('Descripción')
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('gas_monto')
                    ->label('Monto')
                    ->money('GTQ', locale: 'es_GT')
                    ->sortable(),

                TextColumn::make('gas_referencia')
                    ->label('Ref.')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('gas_tipo')
                    ->label('Tipo')
                    ->options([
                        'compra' => 'Compra',
                        'gasto' => 'Gasto',
                    ]),

                SelectFilter::make('cat_id')
                    ->label('Categoría')
                    ->relationship('categoria', 'cat_nombre'),

                Filter::make('rango_fechas')
                    ->form([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'] ?? null, fn($q, $d) => $q->whereDate('gas_fecha', '>=', $d))
                            ->when($data['hasta'] ?? null, fn($q, $h) => $q->whereDate('gas_fecha', '<=', $h));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    /**
     * Guardar automáticamente creado_por
     */
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['creado_por'] = Auth::id();
        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGastos::route('/'),
            'create' => Pages\CreateGasto::route('/create'),
            'edit' => Pages\EditGasto::route('/{record}/edit'),
        ];
    }
}
