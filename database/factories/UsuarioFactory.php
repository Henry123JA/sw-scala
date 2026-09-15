<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        return [
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'ci' => fake()->unique()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'rol_id' => 1,
            'eliminado' => false,
        ];
    }
}
