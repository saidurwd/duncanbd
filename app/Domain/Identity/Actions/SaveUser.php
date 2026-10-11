<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveUser
{
    /**
     * @param  array{name: string, email: string, role: string, password?: ?string, is_active?: bool}  $data
     * @return array{user: User, invited: bool}
     */
    public function __invoke(?User $user, array $data, User $actor): array
    {
        $role = Role::from($data['role']);
        $this->guardRules($user, $role, (bool) ($data['is_active'] ?? true), $actor);

        return DB::transaction(function () use ($user, $data, $role, $actor) {
            $creating = $user === null;
            $user ??= new User;
            $before = $creating ? null : $user->roles->pluck('name')->first();

            $user->fill(['name' => $data['name'], 'email' => Str::lower($data['email']), 'is_active' => (bool) ($data['is_active'] ?? true)]);

            $invited = false;
            if ($creating) {
                // Admin-set password: must be changed at first sign-in. No password: random one + emailed set-password link.
                $user->password = filled($data['password'] ?? null) ? $data['password'] : Str::random(40);
                $user->must_change_password = true;
            }
            $user->save();
            $user->syncRoles($role->value);

            if ($creating && blank($data['password'] ?? null)) {
                $invited = Password::sendResetLink(['email' => $user->email]) === Password::RESET_LINK_SENT;
            }

            if (! $creating && $before !== $role->value) {
                activity('identity')->causedBy($actor)->performedOn($user)->withProperties(['from' => $before, 'to' => $role->value])->log('role changed');
            }
            if ($creating) {
                activity('identity')->causedBy($actor)->performedOn($user)->withProperties(['role' => $role->value, 'invited' => $invited])->log('user created');
            }

            return ['user' => $user, 'invited' => $invited];
        });
    }

    private function guardRules(?User $user, Role $role, bool $active, User $actor): void
    {
        // Nobody can lock themselves out or strip their own admin rights.
        if ($user && $user->is($actor) && (! $active || $role !== Role::SuperAdmin)) {
            throw ValidationException::withMessages(['role' => 'You cannot deactivate yourself or change your own role.']);
        }

        $others = User::role(Role::SuperAdmin->value)->where('is_active', true)->when($user, fn ($q) => $q->where('id', '!=', $user->id))->count();

        if ($role === Role::SuperAdmin && $active && $others >= config('admin.max_super_admins')) {
            throw ValidationException::withMessages(['role' => 'The maximum number of Super Admin accounts ('.config('admin.max_super_admins').') is already in use.']);
        }

        // Never leave the system without a Super Admin.
        if ($user && $user->hasRole(Role::SuperAdmin->value) && ($role !== Role::SuperAdmin || ! $active) && $others === 0) {
            throw ValidationException::withMessages(['role' => 'There must be at least one active Super Admin.']);
        }
    }
}
