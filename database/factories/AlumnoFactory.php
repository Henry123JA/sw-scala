<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alumno>
 */
class AlumnoFactory extends Factory
{
    protected $model = Alumno::class;

    public function definition(): array
    {
        return [
            'id' => fn() => Usuario::factory()->create(['rol_id' => 4])->id,
            'estado' => 'ACTIVO',
        ];
    }
}
