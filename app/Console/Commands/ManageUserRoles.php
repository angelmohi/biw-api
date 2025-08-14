<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;

class ManageUserRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:role {action} {email?} {role?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage user roles. Usage: user:role list|assign|remove [email] [role_name]';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                $this->listUsers();
                break;
            case 'assign':
                $this->assignRole();
                break;
            case 'remove':
                $this->removeRole();
                break;
            default:
                $this->error('Acción no válida. Usa: list, assign, remove');
                return 1;
        }

        return 0;
    }

    private function listUsers()
    {
        $users = User::with('role')->get();
        
        $this->info('Lista de usuarios y sus roles:');
        $this->line('');
        
        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
                $user->id,
                $user->name,
                $user->email,
                $user->role ? $user->role->display_name : 'Sin rol'
            ];
        }
        
        $this->table(['ID', 'Nombre', 'Email', 'Rol'], $rows);
    }

    private function assignRole()
    {
        $email = $this->argument('email') ?? $this->ask('Email del usuario');
        $roleName = $this->argument('role') ?? $this->choice('Selecciona un rol', [
            Role::FULL_ADMINISTRATOR => 'Full Administrator',
            Role::STAFF => 'Staff'
        ]);

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("Usuario con email '{$email}' no encontrado.");
            return;
        }

        $role = Role::getByName($roleName);
        if (!$role) {
            $this->error("Rol '{$roleName}' no encontrado.");
            return;
        }

        $user->role_id = $role->id;
        $user->save();

        $this->info("Rol '{$role->display_name}' asignado correctamente a {$user->name}");
    }

    private function removeRole()
    {
        $email = $this->argument('email') ?? $this->ask('Email del usuario');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("Usuario con email '{$email}' no encontrado.");
            return;
        }

        $user->role_id = null;
        $user->save();

        $this->info("Rol removido correctamente de {$user->name}");
    }
}
