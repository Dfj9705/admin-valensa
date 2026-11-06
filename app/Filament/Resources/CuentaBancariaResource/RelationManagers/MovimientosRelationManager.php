<?php

namespace App\Filament\Resources\CuentaBancariaResource\RelationManagers;

use App\Models\CuentaBancaria;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Support\Access;
use App\Models\MovimientoBancario;
use Illuminate\Database\Eloquent\Model;

class MovimientosRelationManager extends RelationManager
{
    protected static string $relationship = 'movimientos';

    protected static ?string $title = 'Movimientos bancarios';

    protected static ?string $modelLabel = 'Movimiento';

    protected static ?string $pluralModelLabel = 'Movimientos';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('mov_fecha')
                ->label('Fecha')
                ->default(now())
                ->required(),

            Forms\Components\Select::make('mov_tipo')
                ->label('Tipo de movimiento')
                ->options([
                    'ingreso' => 'Ingreso / Depósito',
                    'egreso' => 'Egreso / Retiro',
                ])
                ->required(),

            Forms\Components\TextInput::make('mov_concepto')
                ->label('Concepto')
                ->required()
                ->maxLength(150),

            Forms\Components\TextInput::make('mov_monto')
                ->label('Monto')
                ->numeric()
                ->minValue(0.01)
                ->required(),

            Forms\Components\TextInput::make('mov_referencia')
                ->label('Referencia')
                ->maxLength(100),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('mov_fecha', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('mov_fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('mov_tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(
                        fn(string $state) =>
                            $state === 'ingreso' ? 'success' : 'danger'
                    )
                    ->formatStateUsing(
                        fn(string $state) =>
                            $state === 'ingreso' ? 'Ingreso' : 'Egreso'
                    ),

                Tables\Columns\TextColumn::make('mov_concepto')
                    ->label('Concepto')
                    ->searchable(),

                Tables\Columns\TextColumn::make('mov_monto')
                    ->label('Monto')
                    ->money('GTQ', locale: 'es_GT')
                    ->sortable(),

                Tables\Columns\TextColumn::make('mov_referencia')
                    ->label('Referencia')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Registrar movimiento')
                    ->mutateFormDataUsing(function (array $data): array {
                        $cuenta = $this->getOwnerRecord();

                        $this->validarMovimiento($cuenta, $data);

                        $data['mov_created_by'] = auth()->id();

                        return $data;
                    })->visible(
                        fn() =>
                            true
                        //Access::allows('movimientos_bancarios.crear') || Auth::user()->hasRole('superadministrador')
                    )
                    ->authorize('create'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $this->validarMovimiento(
                            $this->getOwnerRecord(),
                            $data
                        );

                        return $data;
                    })->visible(
                        fn() =>
                            Access::allows('movimientos_bancarios.editar')
                    )
                    ->authorize('update'),

                Tables\Actions\DeleteAction::make()->visible(
                    fn() =>
                        Access::allows('movimientos_bancarios.eliminar')
                )
                    ->authorize('delete'),
            ]);
    }

    private function validarMovimiento(
        CuentaBancaria $cuenta,
        array $data
    ): void {
        if (!$cuenta->cue_activa) {
            throw ValidationException::withMessages([
                'cue_id' => 'La cuenta bancaria está inactiva.',
            ]);
        }

        if (
            $cuenta->cue_fecha_inicio &&
            \Carbon\Carbon::parse($data['mov_fecha'])
                ->lt($cuenta->cue_fecha_inicio)
        ) {
            throw ValidationException::withMessages([
                'mov_fecha' => 'La fecha es anterior al inicio de la cuenta.',
            ]);
        }

        if ((float) ($data['mov_monto'] ?? 0) <= 0) {
            throw ValidationException::withMessages([
                'mov_monto' => 'El monto debe ser mayor que cero.',
            ]);
        }
    }

    public static function canViewForRecord(
        \Illuminate\Database\Eloquent\Model $ownerRecord,
        string $pageClass
    ): bool {
        return Access::allows('movimientos_bancarios.ver')
            && Access::allows('cuentas_bancarias.ver');
    }
}
