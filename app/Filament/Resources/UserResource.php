<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del usuario')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre completo')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->unique(
                                table: User::class,
                                column: 'email',
                                ignoreRecord: true
                            )
                            ->maxLength(255),

                        Forms\Components\Select::make('roles')
                            ->label('Rol del usuario')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) =>
                                    $query->where('guard_name', 'web')
                                        ->whereIn('name', [
                                            'administrador',
                                            'vendedor',
                                        ])
                            )
                            ->multiple()
                            ->maxItems(1)
                            ->preload()
                            ->searchable()
                            ->required()
                            ->helperText(
                                'Selecciona el rol que tendrá el usuario.'
                            ),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Seguridad')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->minLength(12)
                            ->maxLength(255)
                            ->required(
                                fn(string $operation): bool =>
                                    $operation === 'create'
                            )
                            ->dehydrated(
                                fn(?string $state): bool =>
                                    filled($state)
                            )
                            ->helperText(
                                'Al editar, deja este campo vacío para conservar la contraseña actual.'
                            ),
                    ]),
            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo electrónico')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rol')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'superadministrador' => 'danger',
                        'administrador' => 'success',
                        'vendedor' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(
                        fn(string $state): string => ucfirst($state)
                    ),

                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verificado')
                    ->boolean()
                    ->getStateUsing(
                        fn(User $record): bool =>
                            $record->email_verified_at !== null
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name')
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),

                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->requiresConfirmation(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }


    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('superadministrador') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('superadministrador') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole('superadministrador') === true
            && !$record->hasRole('superadministrador');
    }



    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('superadministrador') === true
            && $record->getKey() !== auth()->id()
            && !$record->hasRole('superadministrador');
    }


    public static function validarRolOperativo(array $data): void
    {
        $roles = $data['roles'] ?? [];

        if (
            !is_array($roles) ||
            count($roles) !== 1
        ) {
            throw ValidationException::withMessages([
                'data.roles' => 'Debes seleccionar exactamente un rol.',
            ]);
        }

        $rolValido = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [
                'administrador',
                'vendedor',
            ])
            ->whereKey($roles[0])
            ->exists();

        if (!$rolValido) {
            throw ValidationException::withMessages([
                'data.roles' => 'El rol seleccionado no está permitido.',
            ]);
        }
    }



    public static function canView($record): bool { return static::canViewAny(); }
    public static function canForceDelete($record): bool { return false; }
    public static function canForceDeleteAny(): bool { return false; }
    public static function canReplicate($record): bool { return false; }

}
