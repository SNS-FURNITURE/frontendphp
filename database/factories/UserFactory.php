<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->numerify('+2519########'),
            'password_hash' => static::$password ??= Hash::make('password123'),
            'status' => 'ACTIVE',
            'is_active' => true,
            'last_login_at' => null,
        ];
    }
}
