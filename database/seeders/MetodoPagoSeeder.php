<?php

namespace Database\Seeders;

use App\Models\MetodoPago;
use Illuminate\Database\Seeder;

class MetodoPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MetodoPago::updateOrCreate(['id' => 1], ['nombre' => 'Efectivo', 'activo' => true]);
        MetodoPago::updateOrCreate(['id' => 2], ['nombre' => 'QR', 'activo' => true]);
    }
}
