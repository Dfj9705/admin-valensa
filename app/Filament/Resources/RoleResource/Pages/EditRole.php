<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Validation\ValidationException;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $permisos = $this->record->permissions()
            ->pluck('permissions.id')
            ->map(fn($id) => (string) $id)
            ->all();

        foreach ($this->modulos() as $modulo) {
            $idsModulo = Permission::query()
                ->where('guard_name', 'web')
                ->where('name', 'like', "{$modulo}.%")
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->all();

            $data["permisos_{$modulo}"] = array_values(
                array_intersect($permisos, $idsModulo)
            );
        }

        return $data;
    }

    protected function beforeSave(): void
    {
        abort_unless(
            auth()->user()?->hasRole('superadministrador')
            && $this->record->name !== 'superadministrador',
            403
        );
    }


    protected function afterSave(): void
    {
        abort_unless(
            auth()->user()?->hasRole('superadministrador')
            && in_array(
                $this->record->name,
                ['administrador', 'vendedor'],
                true
            ),
            403
        );

        $state = $this->form->getRawState();
        $ids = [];

        foreach ($this->modulos() as $modulo) {
            $seleccionados = $state["permisos_{$modulo}"] ?? [];

            if (!is_array($seleccionados)) {
                throw ValidationException::withMessages([
                    "data.permisos_{$modulo}" =>
                        'La selección de permisos no es válida.',
                ]);
            }

            $permitidos = Permission::query()
                ->where('guard_name', 'web')
                ->where('name', 'like', "{$modulo}.%")
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all();

            foreach ($seleccionados as $id) {
                if (
                    filter_var($id, FILTER_VALIDATE_INT) === false ||
                    !in_array((int) $id, $permitidos, true)
                ) {
                    throw ValidationException::withMessages([
                        "data.permisos_{$modulo}" =>
                            'Se encontró un permiso no válido.',
                    ]);
                }

                $ids[] = (int) $id;
            }
        }

        $permisos = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('id', array_unique($ids))
            ->get();

        $this->record->syncPermissions($permisos);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset(
            $data['name'],
            $data['guard_name']
        );

        return $data;
    }


    private function modulos(): array
    {
        return [
            'ventas',
            'pagos',
            'productos',
            'inventario',
            'clientes',
            'gastos',
            'categorias_gastos',
            'ingresos_egresos',
            'emisores_fel',
            'reportes',
            'usuarios',
            'roles',
        ];
    }
}
