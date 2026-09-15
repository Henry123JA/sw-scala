<?php

namespace Database\Factories;

use App\Models\Docente;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Docente>
 */
class DocenteFactory extends Factory
{
    protected $model = Docente::class;

    public function definition(): array
    {
        return [
            'id' => fn() => Usuario::factory()->create(['rol_id' => 3])->id,
            'estado' => 'ACTIVO',
        ];
    }
}
