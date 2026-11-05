<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use Spatie\Permission\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Roles y permisos';

    protected static ?string $modelLabel = 'Rol';

    protected static ?string $pluralModelLabel = 'Roles';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';


    public static function form(Form $form): Form
    {
        $modulos = [
            'ventas' => 'Ventas',
            'pagos' => 'Pagos',
            'productos' => 'Productos',
            'inventario' => 'Inventario',
            'clientes' => 'Clientes',
            'gastos' => 'Gastos',
            'categorias_gastos' => 'Categorías de gastos',
            'ingresos_egresos' => 'Ingresos y egresos',
            'emisores_fel' => 'Emisores FEL',
            'reportes' => 'Reportes',
            'usuarios' => 'Usuarios',
            'roles' => 'Roles y permisos',
        ];

        $secciones = [];

        foreach ($modulos as $modulo => $titulo) {
            $secciones[] = Forms\Components\Section::make($titulo)
                ->schema([
                    Forms\Components\CheckboxList::make("permisos_{$modulo}")
                        ->label('Permisos disponibles')
                        ->options(
                            \Spatie\Permission\Models\Permission::query()
                                ->where('guard_name', 'web')
                                ->where('name', 'like', "{$modulo}.%")
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->mapWithKeys(
                                    fn($name, $id) => [
                                        $id => ucfirst(
                                            str_replace("{$modulo}.", '', $name)
                                        ),
                                    ]
                                )
                                ->all()
                        )
                        ->columns(4)
                        ->bulkToggleable(),
                ])
                ->collapsible()
                ->collapsed();
        }

        return $form->schema([
            Forms\Components\Section::make('Información del rol')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre del rol')
                        ->disabled(),

                    Forms\Components\TextInput::make('guard_name')
                        ->label('Guard')
                        ->disabled(),
                ])
                ->columns(2),

            ...$secciones,
        ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Rol')
                    ->formatStateUsing(
                        fn(string $state): string => ucfirst($state)
                    )
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'superadministrador' => 'danger',
                        'administrador' => 'success',
                        'vendedor' => 'info',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permisos')
                    ->counts('permissions')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->counts('users')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Administrar permisos')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(
                        fn(Role $record): bool =>
                            $record->name !== 'superadministrador'
                    ),
            ])
            ->bulkActions([]);
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('superadministrador') === true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole('superadministrador') === true
            && $record->name !== 'superadministrador';
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canView($record): bool { return static::canViewAny(); }
    public static function canForceDelete($record): bool { return false; }
    public static function canForceDeleteAny(): bool { return false; }
    public static function canReplicate($record): bool { return false; }

}
