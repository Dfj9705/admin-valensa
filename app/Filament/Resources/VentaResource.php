<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VentaResource\Pages;
use App\Filament\Resources\VentaResource\RelationManagers;
use App\Filament\Resources\VentaResource\RelationManagers\PagosRelationManager;
use App\Models\Cliente;
use App\Models\Emisor;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\Tekra\TekraContribuyenteService;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class VentaResource extends Resource
{
    protected static ?string $model = Venta::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Maquinaria y equipo';
    protected static ?string $modelLabel = 'Venta';
    protected static ?string $pluralModelLabel = 'Ventas';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información General')
                    ->columns(2)
                    ->schema([
                        Select::make('ven_emisor_id')
                            ->label('Emisor')
                            ->columnSpanFull()
                            ->options(function () {
                                return Emisor::where('emi_activo', true)->pluck('emi_nombre_emisor', 'emi_id');
                            })
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->emi_nombre_emisor} - {$record->emi_nombre_comercial} (NIT {$record->emi_nit})")
                            ->required()
                            ->disabled(fn($record) => $record && $record->ven_estado !== 'draft'),

                        Select::make('ven_cliente_id')
                            ->relationship('cliente', 'cli_nombre')
                            ->label('Cliente')
                            ->searchable()
                            ->disabled(fn($record) => $record && $record->ven_estado !== 'draft')
                            ->options(function () {
                                return Cliente::where('cli_activo', true)->pluck('cli_nombre', 'cli_id');
                            })
                            ->createOptionForm([
                                TextInput::make('cli_nit')
                                    ->label('NIT')
                                    ->required(),


                                Actions::make([
                                    Action::make('tekra_lookup')
                                        ->label('Consultar SAT')
                                        ->icon('heroicon-o-magnifying-glass')
                                        ->action(function (Get $get, Set $set) {
                                            $nit = preg_replace('/[\s-]/', '', $get('cli_nit'));
                                            if (blank($nit)) {
                                                Notification::make()
                                                    ->title('Ingresa NIT')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            $svc = app(TekraContribuyenteService::class);

                                            $response = $svc->consultaNit($nit);

                                            $error = (int) data_get($response, 'resultado.0.error', 1);
                                            $mensaje = (string) data_get($response, 'resultado.0.mensaje', '');

                                            if ($error !== 0) {
                                                Notification::make()
                                                    ->title('TEKRA: error')
                                                    ->body($mensaje !== '' ? $mensaje : 'No se pudo consultar.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            $datos = data_get($response, 'datos.0', []);

                                            $set('cli_nit', data_get($datos, 'nit', $nit));

                                            $nombreTekra = (string) (data_get($datos, 'nombre') ?? data_get($datos, 'nombre_completo') ?? '');
                                            $nombreLimpio = trim(preg_replace('/\s+/', ' ', str_replace(',', ' ', $nombreTekra)));

                                            if ($nombreLimpio !== '') {
                                                $set('cli_nombre', $nombreLimpio);

                                                if (blank(trim((string) $get('cli_nombre')))) {
                                                    $set('cli_nombre', $nombreLimpio);
                                                }
                                            }

                                            Notification::make()
                                                ->title('Datos cargados desde SAT')
                                                ->success()
                                                ->send();
                                        }),
                                ])->columnSpanFull(),

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
                    ->disabled(fn($record) => $record && $record->ven_estado !== 'draft')
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
                            ->default(0)
                            ->live()
                            ->reactive()
                            ->prefix('Q')
                            ->readOnly(),

                        TextInput::make('ven_tax')
                            ->label('IVA')
                            ->numeric()
                            ->default(0)
                            ->live()
                            ->reactive()
                            ->prefix('Q')
                            ->readOnly(),

                        TextInput::make('ven_total')
                            ->label('Total')
                            ->numeric()
                            ->default(0)
                            ->live()
                            ->reactive()
                            ->prefix('Q')
                            ->readOnly(),

                        TextInput::make('ven_pagado_calc')
                            ->label('Pagado')
                            ->live()
                            ->reactive()
                            ->readOnly()
                            ->prefix('Q')
                            ->formatStateUsing(fn($record) => $record?->ven_pagado),

                        TextInput::make('ven_saldo_calc')
                            ->label('Saldo pendiente')
                            ->live()
                            ->reactive()
                            ->readOnly()
                            ->prefix('Q')
                            ->formatStateUsing(fn($record) => $record?->ven_saldo_pendiente),
                    ]),

                Section::make('Información FEL')
                    ->columns(4)
                    ->schema([
                        TextInput::make('ven_fel_uuid')
                            ->label('UUID')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('ven_fel_serie')
                            ->label('Serie')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('ven_fel_numero')
                            ->label('Número')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('ven_fel_fecha_hora_certificacion')
                            ->label('Fecha y Hora de Certificación')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('ven_fel_nit_certificador')
                            ->label('NIT Certificador')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('ven_fel_nombre_certificador')
                            ->label('Nombre Certificador')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('ven_fel_estado_documento')
                            ->label('Estado Documento')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('ven_fel_nombre_receptor')
                            ->label('Nombre Receptor')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('ven_fel_fecha_hora_emision')
                            ->label('Fecha y Hora de Emisión')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('ven_fel_status')
                            ->label('Estado FEL')
                            ->disabled()
                            ->dehydrated(),

                        Placeholder::make('qr_preview')
                            ->label('QR FEL')
                            ->columnSpanFull()
                            ->content(function ($record) {
                                if (!$record?->ven_fel_qr) {
                                    return 'Sin QR';
                                }

                                return new HtmlString(
                                    '<img src="data:image/png;base64,' . $record->ven_fel_qr . '" 
                  style="max-width:200px; border:1px solid #ccc; padding:5px;" />'
                                );
                            }),
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
                TextColumn::make('ven_total')->label('Total')->numeric(2, '.', ',', 2)
                    ->prefix('GTQ ')->sortable()->summarize(
                        Sum::make()
                            ->label('Total')
                            ->numeric(2, '.', ',', 2)
                            ->prefix('GTQ ')
                            ->query(fn(Builder $query) => $query->whereIn('ven_estado', ['certified', 'confirmed']))

                    ),
                TextColumn::make('ven_pagado')
                    ->label('Pagado')
                    ->money('GTQ'),

                TextColumn::make('ven_saldo_pendiente')
                    ->label('Saldo')
                    ->money('GTQ')
                    ->badge(),
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
            PagosRelationManager::class,
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
