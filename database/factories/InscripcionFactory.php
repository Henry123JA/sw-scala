<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\Grupo;
use App\Models\Inscripcion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inscripcion>
 */
class InscripcionFactory extends Factory
{
    protected $model = Inscripcion::class;

    public function definition(): array
    {
        return [
            'alumno_id' => Alumno::factory(),
            'grupo_id' => Grupo::factory(),
            'monto_mensual' => fake()->randomFloat(2, 200, 500),
            'fecha' => now(),
            'estado' => 'ACTIVA',
        ];
    }
}
