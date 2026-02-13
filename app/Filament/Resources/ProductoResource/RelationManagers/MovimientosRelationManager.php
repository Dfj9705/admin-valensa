<?php

namespace App\Filament\Resources\ProductoResource\RelationManagers;

use App\Models\Producto;
use DB;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\ValidationException;

class MovimientosRelationManager extends RelationManager
{
    protected static string $relationship = 'movimientos';
    protected static ?string $title = 'Movimientos';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('mop_cantidad')
                ->label('Cantidad')
                ->numeric()
                ->minValue(1)
                ->required(),

            TextInput::make('mop_costo_unitario')
                ->label('Costo unitario (opcional)')
                ->numeric()
                ->minValue(0)
                ->prefix('Q')
                ->nullable(),

            DateTimePicker::make('mop_fecha')
                ->label('Fecha')
                ->seconds(false)
                ->default(now())
                ->required(),

            Textarea::make('mop_observacion')
                ->label('Observación')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mop_fecha')->label('Fecha')->dateTime()->sortable(),
                TextColumn::make('mop_tipo')->label('Tipo')->badge()->color(fn($state) => $state == 'entrada' ? 'success' : 'danger')->sortable(),
                TextColumn::make('mop_cantidad')->label('Cantidad')->numeric()->sortable(),
                TextColumn::make('mop_costo_unitario')->label('Costo')->money('GTQ')->toggleable(),
                TextColumn::make('mop_observacion')->label('Obs.')->limit(30)->wrap(),
            ])
            ->defaultSort('mop_fecha', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make('ingreso')
                    ->label('Nuevo ingreso')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->modalHeading('Registrar ingreso')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['mop_tipo'] = 'entrada';
                        return $data;
                    }),

                Tables\Actions\CreateAction::make('salida')
                    ->label('Nueva salida')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('danger')
                    ->modalHeading('Registrar salida')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['mop_tipo'] = 'salida';
                        return $data;
                    })
                    ->before(function (array $data) {
                        // Validar stock ANTES de guardar salida
                        DB::transaction(function () use ($data) {
                            /** @var \App\Models\Producto $producto */
                            $producto = Producto::where('pro_id', $this->getOwnerRecord()->pro_id)
                                ->lockForUpdate()
                                ->firstOrFail();
                            $stockActual = (int) $producto->pro_stock;

                            $cantidadSalida = (int) $data['mop_cantidad'];

                            if ($cantidadSalida > $stockActual) {
                                Notification::make()
                                    ->title('Stock insuficiente')
                                    ->body("Stock insuficiente. Disponible: {$stockActual}.")
                                    ->danger()
                                    ->send();
                                throw ValidationException::withMessages([
                                    'mop_cantidad' => "Stock insuficiente. Disponible: {$stockActual}.",
                                ]);
                            }
                        });


                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
