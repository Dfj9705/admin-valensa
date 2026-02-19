<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmisorResource\Pages;
use App\Filament\Resources\EmisorResource\RelationManagers;
use App\Models\Emisor;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmisorResource extends Resource
{
    protected static ?string $model = Emisor::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $navigationLabel = 'Emisores FEL';
    protected static ?string $modelLabel = 'Emisor';
    protected static ?string $pluralModelLabel = 'Emisores';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Emisor')
                ->columns(2)
                ->schema([
                    TextInput::make('emi_nit')->label('NIT')->required()->maxLength(20),
                    TextInput::make('emi_nombre_emisor')->label('Nombre Emisor')->required()->maxLength(150),
                    TextInput::make('emi_codigo_establecimiento')->label('Código Establecimiento')->numeric()->required(),
                    TextInput::make('emi_nombre_comercial')->label('Nombre Comercial')->required()->maxLength(150),
                    TextInput::make('emi_correo_emisor')->label('Correo Emisor')->email()->maxLength(150),
                    TextInput::make('emi_afiliacion_iva')->label('Afiliación IVA')->required()->maxLength(10),
                    Toggle::make('emi_activo')->label('Activo')->default(true),
                ]),

            Section::make('Dirección')
                ->columns(2)
                ->schema([
                    TextInput::make('emi_direccion')->label('Dirección')->required()->maxLength(255)->columnSpanFull(),
                    TextInput::make('emi_codigo_postal')->label('Código Postal')->maxLength(10),
                    TextInput::make('emi_municipio')->label('Municipio')->required()->maxLength(100),
                    TextInput::make('emi_departamento')->label('Departamento')->required()->maxLength(100),
                    TextInput::make('emi_pais')->label('País')->required()->maxLength(2)->default('GT'),
                ]),

            Section::make('Frases')
                ->columns(3)
                ->schema([
                    TextInput::make('emi_frase_tipo')->label('TipoFrase')->numeric()->required()->default(3),
                    TextInput::make('emi_frase_escenario')->label('CódigoEscenario')->numeric()->required()->default(1),
                    TextInput::make('emi_frase_texto')->label('Texto')->required()->maxLength(150)->default('NO GENERA DERECHO A CRÉDITO FISCAL')
                        ->columnSpanFull(),
                ]),

            Section::make('Tekra (Producción)')
                ->columns(2)
                ->schema([
                    TextInput::make('emi_tekra_usuario')->label('Usuario')->required()->maxLength(100),
                    TextInput::make('emi_tekra_clave')->label('Clave')->required()->password()->revealable()->maxLength(150),
                    TextInput::make('emi_tekra_cliente')->label('Cliente')->required()->maxLength(100),
                    TextInput::make('emi_tekra_contrato')->label('Contrato')->required()->maxLength(100),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('emi_nombre_emisor')->label('Emisor')->searchable(),
                Tables\Columns\TextColumn::make('emi_nit')->label('NIT')->searchable(),
                Tables\Columns\TextColumn::make('emi_nombre_comercial')->label('Comercial')->searchable(),
                Tables\Columns\IconColumn::make('emi_activo')->label('Activo')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmisors::route('/'),
            'create' => Pages\CreateEmisor::route('/create'),
            'edit' => Pages\EditEmisor::route('/{record}/edit'),
        ];
    }
}
