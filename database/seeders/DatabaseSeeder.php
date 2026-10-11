<?php

namespace Database\Seeders;

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $seed = config('admin.seed_super_admin');

        if ($seed['email'] && $seed['password']) {
            $user = User::firstOrCreate(
                ['email' => $seed['email']],
                ['name' => $seed['name'], 'password' => $seed['password'], 'must_change_password' => true],
            );
            $user->syncRoles(Role::SuperAdmin->value);
        }
    }
}
