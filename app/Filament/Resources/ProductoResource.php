<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductoResource\Pages;
use App\Filament\Resources\ProductoResource\Pages\CreateProducto;
use App\Filament\Resources\ProductoResource\Pages\ListProductos;
use App\Filament\Resources\ProductoResource\Pages\EditProducto;
use App\Filament\Resources\ProductoResource\RelationManagers;
use App\Filament\Resources\ProductoResource\RelationManagers\MovimientosRelationManager;
use App\Models\Producto;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductoResource extends Resource
{
    protected static ?string $model = Producto::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Productos';
    protected static ?string $navigationGroup = 'Inventario';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del producto')
                    ->columns(2)
                    ->schema([
                        TextInput::make('pro_nombre')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(150)
                            ->columnSpan(2),

                        TextInput::make('pro_sku')
                            ->label('SKU')
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),

                        Toggle::make('pro_activo')
                            ->label('Activo')
                            ->default(true),

                        Textarea::make('pro_descripcion')
                            ->label('Descripción')
                            ->rows(4)
                            ->columnSpan(2),
                    ]),

                Section::make('Inventario y precios')
                    ->columns(4)
                    ->schema([
                        TextInput::make('pro_stock')
                            ->label('Stock')
                            ->numeric()
                            ->minValue(0)
                            ->readOnly()
                            ->default(0),

                        TextInput::make('pro_precio_costo')
                            ->label('Precio costo (unit.)')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Q')
                            ->default(0)
                            ->required(),

                        TextInput::make('pro_precio_venta_min')
                            ->label('Venta mínimo (unit.)')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Q')
                            ->default(0)
                            ->required(),

                        TextInput::make('pro_precio_venta_max')
                            ->label('Venta máximo (unit.)')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Q')
                            ->default(0)
                            ->required(),
                    ]),

                Section::make('Imágenes')
                    ->description('Sube varias imágenes del producto (galería).')
                    ->schema([
                        FileUpload::make('pro_imagenes')
                            ->label('Galería')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->disk('public')
                            ->directory('productos')
                            ->visibility('public')
                            ->maxFiles(10)
                            ->maxSize(2048)
                            ->imageEditor()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('pro_imagenes')
                    ->label('Img')
                    ->circular()
                    ->getStateUsing(function ($record) {
                        // mostrar la primera imagen de la galería si existe
                        $imgs = $record->pro_imagenes ?? [];
                        return $imgs[0] ?? null;
                    }),

                TextColumn::make('pro_nombre')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('pro_sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('pro_stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'success' : 'danger')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('pro_precio_costo')
                    ->label('Costo unit.')
                    ->money('GTQ')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('costo_total')
                    ->label('Costo total')
                    ->state(function ($record) {
                        return (float) $record->pro_stock * (float) $record->pro_precio_costo;
                    })
                    ->money('GTQ')
                    ->sortable(),

                TextColumn::make('pro_precio_venta_min')
                    ->label('Venta min (unit.)')
                    ->money('GTQ')
                    ->toggleable(),

                TextColumn::make('pro_precio_venta_max')
                    ->label('Venta max (unit.)')
                    ->money('GTQ')
                    ->toggleable(),

                TextColumn::make('total_min')
                    ->label('Total min')
                    ->state(fn($record) => (float) $record->pro_stock * (float) $record->pro_precio_venta_min)
                    ->money('GTQ')
                    ->toggleable(),

                TextColumn::make('total_max')
                    ->label('Total max')
                    ->state(fn($record) => (float) $record->pro_stock * (float) $record->pro_precio_venta_max)
                    ->money('GTQ')
                    ->toggleable(),

                IconColumn::make('pro_activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('pro_activo')
                    ->label('Activo'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('pro_id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            MovimientosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductos::route('/'),
            'create' => CreateProducto::route('/create'),
            'edit' => EditProducto::route('/{record}/edit'),
        ];
    }
}
