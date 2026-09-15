<?php

namespace Database\Factories;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Grupo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grupo>
 */
class GrupoFactory extends Factory
{
    protected $model = Grupo::class;

    public function definition(): array
    {
        return [
            'codigo_grupo' => fake()->unique()->bothify('GRP-####'),
            'curso_id' => Curso::factory(),
            'docente_id' => Docente::factory(),
            'capacidad_maxima' => fake()->numberBetween(10, 30),
            'estado' => 'ACTIVO',
            'eliminado' => false,
        ];
    }
}
