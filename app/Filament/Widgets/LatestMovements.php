<?php

namespace App\Filament\Widgets;

use App\Models\ProductoMovimiento;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestMovements extends BaseWidget
{

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductoMovimiento::query()->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('producto.pro_nombre')
                    ->label('Producto'),

                Tables\Columns\BadgeColumn::make('mop_tipo')
                    ->label('Tipo')
                    ->colors([
                        'success' => 'entrada',
                        'danger' => 'salida',
                    ]),

                Tables\Columns\TextColumn::make('mop_cantidad')
                    ->label('Cantidad')
                    ->numeric(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ]);
    }
}
