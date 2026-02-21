<?php

namespace App\Filament\Resources\VentaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PagosRelationManager extends RelationManager
{
    protected static string $relationship = 'pagos';
    protected static ?string $title = 'Pagos';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('vpa_monto')
                ->label('Monto')
                ->numeric()
                ->prefix('Q')
                ->required(),

            Select::make('vpa_metodo')
                ->label('Método')
                ->options([
                    'efectivo' => 'Efectivo',
                    'transferencia' => 'Transferencia',
                    'tarjeta' => 'Tarjeta',
                    'cheque' => 'Cheque',
                    'otro' => 'Otro',
                ])
                ->searchable(),

            TextInput::make('vpa_referencia')
                ->label('Referencia')
                ->maxLength(100),

            DateTimePicker::make('vpa_fecha')
                ->label('Fecha')
                ->default(now())
                ->seconds(false)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('vpa_id')
            ->columns([
                TextColumn::make('vpa_fecha')->label('Fecha')->dateTime(),
                TextColumn::make('vpa_metodo')->label('Método')->badge(),
                TextColumn::make('vpa_referencia')->label('Referencia')->wrap(),
                TextColumn::make('vpa_monto')->label('Monto')->money('GTQ'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Pago')
                    ->before(function (array $data) {

                        $venta = $this->getOwnerRecord();

                        $pagado = (float) $venta->pagos()->sum('vpa_monto');
                        $saldo = (float) $venta->ven_total - $pagado;

                        if ((float) $data['vpa_monto'] > $saldo) {
                            Notification::make()
                                ->title('El monto excede el saldo pendiente.')
                                ->danger()
                                ->send();
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'vpa_monto' => 'El monto excede el saldo pendiente.',
                            ]);
                        }
                    })
                    ->after(function ($record) {
                        $record->refresh();

                        $this->dispatch('refreshVentaTotals');
                    })
                    // Solo permitir pagos cuando no es draft/cancelled:
                    ->visible(fn() => in_array($this->getOwnerRecord()->ven_estado, ['confirmed', 'certified'], true)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => $this->getOwnerRecord()->ven_estado === 'confirmed')
                    ->after(function ($record) {

                        $this->dispatch('refreshVentaTotals');
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => $this->getOwnerRecord()->ven_estado === 'confirmed')
                    ->recordTitle(fn($record) => "Eliminar pago Q{$record->vpa_monto}")
                    ->after(function ($record) {
                        $this->dispatch('refreshVentaTotals');
                    }),
            ]);
    }

    // Validación: no dejar que paguen más del saldo

}
