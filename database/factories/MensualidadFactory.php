<?php

namespace Database\Factories;

use App\Models\Inscripcion;
use App\Models\Mensualidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mensualidad>
 */
class MensualidadFactory extends Factory
{
    protected $model = Mensualidad::class;

    public function definition(): array
    {
        return [
            'inscripcion_id' => Inscripcion::factory(),
            'numero_mes' => fake()->numberBetween(1, 12),
            'monto_base' => fake()->randomFloat(2, 200, 500),
            'fecha_vencimiento' => now()->startOfMonth(),
            'estado' => 'PENDIENTE',
        ];
    }
}
