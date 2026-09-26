<?php

namespace Tests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createUserWithRole(string $roleName, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);

        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            [
                'description' => strtoupper(str_replace('_', ' ', $roleName)).' organizational role scope',
                'created_at' => now(),
            ]
        );

        $user->roles()->attach($role->id, ['assigned_at' => now()]);
        $user->unsetRelation('roles');
        $user->load('roles');

        return $user;
    }
}
