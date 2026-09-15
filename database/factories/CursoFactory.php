<?php

namespace Database\Factories;

use App\Models\Curso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Curso>
 */
class CursoFactory extends Factory
{
    protected $model = Curso::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(2, true) . ' Musical',
            'precio' => fake()->randomFloat(2, 100, 500),
            'estado' => 'ACTIVO',
            'eliminado' => false,
        ];
    }
}
