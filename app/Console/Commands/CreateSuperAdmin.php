<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdmin extends Command
{
    protected $signature = 'valensa:create-superadmin';

    protected $description = 'Crear o promover un usuario superadministrador';

    public function handle(): int
    {
        $name = $this->ask('Nombre del superadministrador');
        $email = $this->ask('Correo electrónico');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if (
                !$this->confirm(
                    'El usuario ya existe. ¿Deseas asignarle el rol superadministrador?',
                    false
                )
            ) {
                return self::SUCCESS;
            }
        } else {
            $password = $this->secret('Contraseña');

            if (!$password || strlen($password) < 12) {
                $this->error('La contraseña debe tener al menos 12 caracteres.');

                return self::FAILURE;
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);
        }

        $user->syncRoles(['superadministrador']);

        $this->info('Superadministrador configurado correctamente.');

        return self::SUCCESS;
    }
}

