<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Filament\Resources\ClienteResource\RelationManagers;
use App\Models\Cliente;
use App\Models\Municipio;
use App\Services\Tekra\TekraContribuyenteService;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Identificación')
                ->columns(2)
                ->schema([
                    TextInput::make('nit')
                        ->label('NIT')
                        ->helperText('Con o sin guiones. Ej: 1234567-8')
                        ->maxLength(20)
                        ->dehydrateStateUsing(fn($state) => self::cleanId($state))
                        ->rules(fn($record) => [
                            'nullable',
                            'max:20',
                            Rule::unique('clientes', 'cli_nit')->ignore($record),
                        ]),

                    TextInput::make('cui')
                        ->label('CUI')
                        ->helperText('Con o sin espacios/guiones. Ej: 1234 56789 0101')
                        ->maxLength(20)
                        ->dehydrateStateUsing(fn($state) => self::cleanId($state))
                        ->rules(fn($record) => [
                            'nullable',
                            'max:20',
                            Rule::unique('clientes', 'cli_cui')->ignore($record),
                        ]),

                    Actions::make([
                        Action::make('tekra_lookup')
                            ->label('Consultar SAT')
                            ->icon('heroicon-o-magnifying-glass')
                            ->action(function (Get $get, Set $set) {
                                $nit = self::cleanId((string) $get('nit'));
                                $cui = self::cleanId((string) $get('cui'));

                                if (blank($nit) && blank($cui)) {
                                    Notification::make()
                                        ->title('Ingresa NIT o CUI')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                $svc = app(TekraContribuyenteService::class);

                                $response = filled($nit)
                                    ? $svc->consultaNit($nit)
                                    : $svc->consultaCui($cui);

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

                                if (filled($nit)) {
                                    $set('nit', data_get($datos, 'nit', $nit));
                                }

                                $nombreTekra = (string) (data_get($datos, 'nombre') ?? data_get($datos, 'nombre_completo') ?? '');
                                $nombreLimpio = trim(preg_replace('/\s+/', ' ', str_replace(',', ' ', $nombreTekra)));

                                if ($nombreLimpio !== '') {
                                    $set('tax_name', $nombreLimpio);

                                    if (blank(trim((string) $get('name')))) {
                                        $set('name', $nombreLimpio);
                                    }
                                }

                                $direccion = (string) data_get($datos, 'direccion_completa', '');
                                if ($direccion !== '' && blank(trim((string) $get('address')))) {
                                    $set('address', $direccion);
                                }

                                Notification::make()
                                    ->title('Datos cargados desde SAT')
                                    ->success()
                                    ->send();
                            }),
                    ])->columnSpanFull(),
                ]),

            Section::make('Datos del cliente')
                ->columns(2)
                ->schema([
                    TextInput::make('cli_nombre')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(150),

                    TextInput::make('cli_nombre_fel')
                        ->label('Nombre para factura (FEL)')
                        ->helperText('Si no lo llenas, se usa el “Nombre”.')
                        ->maxLength(150),

                    TextInput::make('cli_email')
                        ->label('Correo')
                        ->email()
                        ->maxLength(150),

                    TextInput::make('cli_telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->maxLength(50),

                    Toggle::make('cli_activo')
                        ->label('Activo')
                        ->default(true),
                ]),

            Section::make('Dirección')
                ->columns(2)
                ->schema([
                    Textarea::make('cli_direccion')
                        ->label('Dirección')
                        ->rows(2)
                        ->columnSpanFull()
                        ->maxLength(255),

                    Select::make('cli_departamento_id')
                        ->label('Departamento')
                        ->relationship('departamento', 'dep_nombre')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn(Set $set) => $set('cli_municipio_id', null)),

                    Select::make('cli_municipio_id')
                        ->label('Municipio')

                        ->key(fn(Get $get) => 'municipio-options-' . ($get('cli_departamento_id') ?? 'none'))
                        ->options(function (Get $get) {
                            $departmentId = $get('cli_departamento_id');
                            if (!$departmentId)
                                return [];

                            return Municipio::query()
                                ->where('dep_id', $departmentId)
                                ->orderBy('mun_nombre')
                                ->pluck('mun_nombre', 'mun_id')
                                ->toArray();
                        })
                        ->searchable()
                        ->disabled(fn(Get $get) => blank($get('cli_departamento_id')))
                        ->placeholder('Seleccione un municipio'),
                ]),

        ])
            ->columns(1)
            ->statePath('data');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cli_nombre')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('cli_nombre_fel')->label('Nombre FEL')->toggleable()->wrap(),
                TextColumn::make('cli_nit')->label('NIT')->searchable()->toggleable(),
                TextColumn::make('cli_cui')->label('CUI')->searchable()->toggleable(),
                TextColumn::make('departamento.dep_nombre')->label('Departamento')->sortable()->toggleable(),
                TextColumn::make('municipio.mun_nombre')->label('Municipio')->sortable()->toggleable(),
                IconColumn::make('cli_activo')->label('Activo')->boolean()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('cli_activo')->label('Activo'),
                SelectFilter::make('cli_departamento_id')
                    ->label('Departamento')
                    ->relationship('departamento', 'dep_nombre')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }

    public static function cleanId(?string $value): ?string
    {
        if ($value === null)
            return null;
        $value = trim($value);
        if ($value === '')
            return null;

        return preg_replace('/[\s-]/', '', $value);
    }
}
