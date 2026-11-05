<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class Access
{
    public static function allows(string $permission, ?User $user = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->hasRole('superadministrador')) {
            return true;
        }
        [$module, $action] = array_pad(explode('.', $permission, 2), 2, '');
        if (in_array($module, ['usuarios', 'roles'], true)) {
            return false;
        }
        if (! $user->hasRole('administrador')) {
            if (! $user->hasRole('vendedor') || ! in_array($module, ['ventas', 'pagos', 'productos', 'inventario', 'clientes'], true) || $action === 'eliminar') {
                return false;
            }
        }

        return $user->checkPermissionTo($permission, 'web');
    }

    public static function authorize(string $permission): void
    {
        if (! self::allows($permission)) {
            throw new AuthorizationException('No tienes permiso para realizar esta operación.');
        }
    }
}
