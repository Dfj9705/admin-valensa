<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Módulos y acciones disponibles
        $modulos = [
            'ventas' => [
                'ver',
                'crear',
                'editar',
                'eliminar',
                'confirmar',
                'certificar',
                'anular',
            ],
            'pagos' => [
                'ver',
                'crear',
                'editar',
                'eliminar',
            ],
            'productos' => [
                'ver',
                'crear',
                'editar',
                'eliminar',
            ],
            'inventario' => [
                'ver',
                'crear',
                'editar',
                'eliminar',
            ],
            'clientes' => [
                'ver',
                'crear',
                'editar',
                'eliminar',
            ],
            'gastos' => [
                'ver',
                'crear',
                'editar',
                'eliminar',
            ],
            'categorias_gastos' => [
                'ver',
                'crear',
                'editar',
                'eliminar',
            ],
            'ingresos_egresos' => [
                'ver',
                'crear',
                'editar',
                'eliminar',
            ],
            'emisores_fel' => [
                'ver',
                'crear',
                'editar',
                'eliminar',
            ],
            'reportes' => [
                'ver',
                'exportar',
            ],
            'usuarios' => [
                'ver',
                'crear',
                'editar',
                'eliminar',
            ],
            'roles' => [
                'ver',
                'crear',
                'editar',
                'eliminar',
            ],
        ];

        // Crear permisos sin duplicarlos
        foreach ($modulos as $modulo => $acciones) {
            foreach ($acciones as $accion) {
                Permission::findOrCreate(
                    "{$modulo}.{$accion}",
                    'web'
                );
            }
        }

        // Crear roles
        $superadmin = Role::findOrCreate(
            'superadministrador',
            'web'
        );

        $administrador = Role::findOrCreate(
            'administrador',
            'web'
        );

        $vendedor = Role::findOrCreate(
            'vendedor',
            'web'
        );

        // Permisos iniciales del administrador:
        // todos excepto usuarios y roles.
        $permisosAdministrador = Permission::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', [
                'usuarios.ver',
                'usuarios.crear',
                'usuarios.editar',
                'usuarios.eliminar',
                'roles.ver',
                'roles.crear',
                'roles.editar',
                'roles.eliminar',
            ])
            ->pluck('name')
            ->all();

        // Permisos iniciales del vendedor
        $permisosVendedor = [];

        foreach ([
            'ventas',
            'pagos',
            'productos',
            'inventario',
            'clientes',
        ] as $modulo) {
            foreach ($modulos[$modulo] as $accion) {
                if ($accion !== 'eliminar') {
                    $permisosVendedor[] = "{$modulo}.{$accion}";
                }
            }
        }


        if ($administrador->permissions()->count() === 0) {
            $administrador->syncPermissions(
                $permisosAdministrador
            );
        }

        if ($vendedor->permissions()->count() === 0) {
            $vendedor->syncPermissions(
                $permisosVendedor
            );
        }


        // Asignar permisos únicamente al crear cada rol
        // por primera vez. No sobrescribir cambios posteriores.
        if (!$administrador->wasRecentlyCreated) {
            // El rol ya existía antes de este Seeder.
            // Su configuración se conserva.
        } else {
            $administrador->syncPermissions(
                $permisosAdministrador
            );
        }

        if ($vendedor->wasRecentlyCreated) {
            $vendedor->syncPermissions(
                $permisosVendedor
            );
        }

        // El superadministrador tendrá acceso global
        // mediante Gate::before(), en un paso posterior.

        // Asignar administrador al usuario existente
        $email = config('services.valensa.admin_email');

        if ($email) {
            $user = User::where('email', $email)->first();

            if (
                $user &&
                !$user->hasRole('superadministrador')
            ) {
                $user->assignRole('administrador');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
