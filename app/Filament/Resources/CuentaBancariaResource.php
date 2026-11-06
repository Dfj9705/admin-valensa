<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CuentaBancariaResource\Pages;
use App\Models\CuentaBancaria;
use App\Support\Access;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\CuentaBancariaResource\RelationManagers;

class CuentaBancariaResource extends Resource
{
    protected static ?string $model = CuentaBancaria::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Maquinaria y equipo';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Cuentas bancarias';
    protected static ?string $modelLabel = 'Cuenta bancaria';
    protected static ?string $pluralModelLabel = 'Cuentas bancarias';

    protected static ?string $recordTitleAttribute = 'cue_nombre';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('ban_id')
                ->label('Banco')
                ->relationship(
                    'banco',
                    'ban_nombre',
                    modifyQueryUsing: fn(Builder $query) =>
                        $query->where('ban_activo', true)
                )
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\TextInput::make('cue_nombre')
                ->label('Nombre de la cuenta')
                ->required()
                ->maxLength(100),

            Forms\Components\TextInput::make('cue_numero')
                ->label('Número de cuenta')
                ->required()
                ->maxLength(50),

            Forms\Components\Select::make('cue_tipo')
                ->label('Tipo de cuenta')
                ->options([
                    'monetaria' => 'Monetaria',
                    'ahorro' => 'Ahorro',
                ])
                ->required(),

            Forms\Components\Select::make('cue_moneda')
                ->label('Moneda')
                ->options([
                    'GTQ' => 'Quetzales',
                    'USD' => 'Dólares',
                ])
                ->default('GTQ')
                ->disabled(
                    fn(?CuentaBancaria $record) =>
                        $record?->tieneOperaciones() ?? false
                )
                ->dehydrated()
                ->required(),

            Forms\Components\TextInput::make('cue_saldo_inicial')
                ->label('Saldo inicial')
                ->numeric()
                ->default(0)
                ->disabled(
                    fn(?CuentaBancaria $record) =>
                        $record?->tieneOperaciones() ?? false
                )
                ->dehydrated()
                ->required(),

            Forms\Components\DatePicker::make('cue_fecha_inicio')
                ->label('Fecha de inicio de movimientos')
                ->disabled(
                    fn(?CuentaBancaria $record) =>
                        $record?->tieneOperaciones() ?? false
                )
                ->dehydrated()
                ->required(),

            Forms\Components\Toggle::make('cue_activa')
                ->label('Activa')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('banco.ban_nombre')
                    ->label('Banco')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cue_nombre')
                    ->label('Cuenta')
                    ->searchable(),

                Tables\Columns\TextColumn::make('cue_numero')
                    ->label('Número')
                    ->searchable(),

                Tables\Columns\TextColumn::make('cue_tipo')
                    ->label('Tipo')
                    ->badge(),

                Tables\Columns\TextColumn::make('cue_moneda')
                    ->label('Moneda'),

                Tables\Columns\TextColumn::make('saldo_actual')
                    ->label('Saldo actual')
                    ->state(
                        fn(CuentaBancaria $record) =>
                            $record->calcularSaldoActual()
                    )
                    ->formatStateUsing(
                        fn($state, CuentaBancaria $record) =>
                            ($record->cue_moneda === 'USD' ? '$ ' : 'Q ')
                            . number_format((float) $state, 2)
                    )
                    ->color(
                        fn($state) =>
                            (float) $state < 0 ? 'danger' : 'success'
                    ),

                Tables\Columns\IconColumn::make('cue_activa')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ban_id')
                    ->label('Banco')
                    ->relationship('banco', 'ban_nombre'),

                Tables\Filters\TernaryFilter::make('cue_activa')
                    ->label('Activa'),
            ])
            ->actions([
                Tables\Actions\Action::make('movimientos')
                    ->label('Movimientos')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('info')
                    ->visible(
                        fn() =>
                            Access::allows('reportes.ver')
                            && Access::allows('reportes.exportar')
                    )
                    ->form([
                        Forms\Components\DatePicker::make('desde')
                            ->label('Desde')
                            ->required()
                            ->default(now()->startOfMonth()),

                        Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta')
                            ->required()
                            ->default(now())
                            ->afterOrEqual('desde'),
                    ])
                    ->action(function (CuentaBancaria $record, array $data) {
                        return redirect()->route(
                            'reportes.cuentas-bancarias',
                            [
                                'cue_id' => $record->cue_id,
                                'desde' => $data['desde'],
                                'hasta' => $data['hasta'],
                            ]
                        );
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCuentaBancarias::route('/'),
            'create' => Pages\CreateCuentaBancaria::route('/create'),
            'edit' => Pages\EditCuentaBancaria::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MovimientosRelationManager::class,
        ];
    }

    public static function canViewAny(): bool
    {
        return Access::allows('cuentas_bancarias.ver');
    }

    public static function canCreate(): bool
    {
        return Access::allows('cuentas_bancarias.crear');
    }

    public static function canEdit($record): bool
    {
        return Access::allows('cuentas_bancarias.editar');
    }

    public static function canDelete($record): bool
    {
        return Access::allows('cuentas_bancarias.eliminar')
            && !$record->tieneOperaciones();
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
