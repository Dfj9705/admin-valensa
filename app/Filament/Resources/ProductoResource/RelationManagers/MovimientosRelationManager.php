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


    public static function canViewForRecord(
        \Illuminate\Database\Eloquent\Model $ownerRecord,
        string $pageClass
    ): bool {
        return \App\Support\Access::allows('inventario.ver') && \App\Support\Access::allows('productos.ver');
    }


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
                TextColumn::make('mop_tipo')->label('Tipo')->badge()->color(fn($state) => $state == 'entrada' || $state == 'devolucion' ? 'success' : 'danger')->sortable(),
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
                    ->authorize(
                        fn(): bool =>
                            auth()->user()?->can('inventario.crear') ?? false
                    )
                    ->mutateFormDataUsing(function (array $data): array {
                        \App\Support\Access::authorize('inventario.crear');
                        $data['mop_tipo'] = 'entrada';
                        return $data;
                    }),

                Tables\Actions\CreateAction::make('salida')
                    ->label('Nueva salida')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('danger')
                    ->modalHeading('Registrar salida')
                    ->authorize(
                        fn(): bool =>
                            auth()->user()?->can('inventario.crear') ?? false
                    )
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['mop_tipo'] = 'salida';
                        return $data;
                    })
                    ->using(function (array $data) {
                        \App\Support\Access::authorize('inventario.crear');
                        return $this->getOwnerRecord()->movimientos()->create($data);
                    }),
            ])
            ->actions([]);

    }

    protected function canCreate(): bool { return \App\Support\Access::allows('inventario.crear') && \App\Support\Access::allows('inventario.ver'); }
    protected function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    protected function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    protected function canDeleteAny(): bool { return false; }

}
