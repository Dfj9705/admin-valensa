<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VentaResource\Pages;
use App\Filament\Resources\VentaResource\RelationManagers;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VentaResource extends Resource
{
    protected static ?string $model = Venta::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?string $modelLabel = 'Venta';
    protected static ?string $pluralModelLabel = 'Ventas';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información General')
                    ->columns(2)
                    ->schema([

                        Select::make('ven_cliente_id')
                            ->relationship('cliente', 'cli_nombre')
                            ->label('Cliente')
                            ->searchable()
                            ->disabled(fn($record) => $record?->ven_estado !== 'draft')
                            ->options(function () {
                                return Cliente::where('cli_activo', true)->pluck('cli_nombre', 'cli_id');
                            })
                            ->createOptionForm([
                                TextInput::make('cli_nit')
                                    ->label('NIT')
                                    ->required(),
                                TextInput::make('cli_nombre')
                                    ->label('Nombre')
                                    ->required(),

                            ])
                            ->required(),

                        Select::make('ven_estado')
                            ->label('Estado')
                            ->options([
                                'draft' => 'Borrador',
                                'confirmed' => 'Confirmada',
                                'certified' => 'Certificada',
                                'cancelled' => 'Cancelada',
                            ])
                            ->default('draft')
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                    ]),

                Repeater::make('productos') // nombre de la relación hasMany
                    ->label('Detalle')
                    ->relationship() // usa la relación "productos()" del modelo Venta
                    ->defaultItems(0)
                    ->columnSpanFull()
                    ->addActionLabel('Agregar producto')
                    ->columns(12)
                    ->disabled(fn($record) => $record?->ven_estado !== 'draft')
                    ->schema([

                        Select::make('pro_id')
                            ->label('Producto')
                            ->columnSpanFull()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->relationship('producto', 'pro_nombre') // ajusta "pro_nombre" según tu tabla productos
                            ->reactive()

                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!$state) {
                                    return;
                                }

                                $producto = Producto::query()->find($state);
                                if (!$producto) {
                                    return;
                                }

                                // snapshots
                                $set('description_snapshot', $producto->pro_nombre ?? $producto->name ?? '');
                                $set('uom_snapshot', $producto->pro_uom ?? $producto->uom ?? 'UNI');

                                // precio default (elige uno: min, max, etc.)
                                $set('unit_price', (float) ($producto->pro_precio_venta_min ?? $producto->price_min ?? 0));

                                // recalcular
                                $qty = 1;
                                $unit = (float) ($producto->pro_precio_venta_min ?? $producto->price_min ?? 0);
                                $disc = (float) 0;
                                $set('qty', $qty);
                                $set('discount', $disc);
                                $set('line_total', round(($qty * $unit) - $disc, 2));
                            }),

                        TextInput::make('qty')
                            ->label('Cant.')
                            ->columnSpan(3)
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $qty = (float) ($get('qty') ?? 0);
                                $unit = (float) ($get('unit_price') ?? 0);
                                $disc = (float) ($get('discount') ?? 0);
                                $set('line_total', round(($qty * $unit) - $disc, 2));
                            }),

                        TextInput::make('unit_price')
                            ->label('P. Unit')
                            ->columnSpan(3)
                            ->numeric()
                            ->prefix('Q')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $qty = (float) ($get('qty') ?? 0);
                                $unit = (float) ($get('unit_price') ?? 0);
                                $disc = (float) ($get('discount') ?? 0);
                                $set('line_total', round(($qty * $unit) - $disc, 2));
                            }),

                        TextInput::make('discount')
                            ->label('Desc.')
                            ->columnSpan(3)
                            ->numeric()
                            ->default(0)
                            ->reactive()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $qty = (float) ($get('qty') ?? 0);
                                $unit = (float) ($get('unit_price') ?? 0);
                                $disc = (float) ($get('discount') ?? 0);
                                $set('line_total', round(($qty * $unit) - $disc, 2));
                            }),

                        TextInput::make('line_total')
                            ->label('Total')
                            ->columnSpan(3)
                            ->numeric()
                            ->prefix('Q')
                            ->disabled()
                            ->dehydrated(), // se guarda aunque esté disabled

                        Hidden::make('description_snapshot')->dehydrated(),
                        Hidden::make('uom_snapshot')->dehydrated(),
                    ])
                    ->itemLabel(fn(array $state): ?string => $state['description_snapshot'] ?? 'Producto')
                    ->reorderable(false),


                Section::make('Totales')
                    ->columns(3)
                    ->schema([
                        TextInput::make('ven_subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->live()
                            ->reactive()
                            ->prefix('Q')
                            ->disabled(),

                        TextInput::make('ven_tax')
                            ->label('IVA')
                            ->numeric()
                            ->live()
                            ->reactive()
                            ->prefix('Q')
                            ->disabled(),

                        TextInput::make('ven_total')
                            ->label('Total')
                            ->numeric()
                            ->live()
                            ->reactive()
                            ->prefix('Q')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ven_id')->label('#')->sortable(),
                TextColumn::make('cliente.cli_nombre')->label('Cliente')->searchable(),
                TextColumn::make('ven_estado')->label('Estado')->sortable()->badge()->color(function ($state) {
                    switch ($state) {
                        case 'draft':
                            return 'gray';
                        case 'confirmed':
                            return 'info';
                        case 'certified':
                            return 'success';
                        case 'cancelled':
                            return 'danger';
                        default:
                            return 'gray';
                    }
                }),
                TextColumn::make('ven_total')->label('Total')->money('GTQ')->sortable(),
                TextColumn::make('created_at')->dateTime()->label('Creada'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()->disabled(fn($record) => $record?->ven_estado !== 'draft'),
            ])
            ->defaultSort('ven_id', 'desc')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVentas::route('/'),
            'create' => Pages\CreateVenta::route('/create'),
            'edit' => Pages\EditVenta::route('/{record}/edit'),
        ];
    }
}
