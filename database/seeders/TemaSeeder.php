<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tema;

class TemaSeeder extends Seeder
{
    public function run(): void
    {
        $temas = [
            [
                'nombre'                 => 'ninos',
                'color_primario_dia'     => '#FF6F61',
                'color_secundario_dia'   => '#FFE5D9',
                'contraste_dia'          => '#000000',
                'color_primario_noche'   => '#C94B3A',
                'color_secundario_noche' => '#3D1308',
                'contraste_noche'        => '#FFFFFF',
                'font_size'              => '18px',
                'descripcion'            => 'Tema Infantil',
            ],
            [
                'nombre'                 => 'jovenes',
                'color_primario_dia'     => '#6C63FF',
                'color_secundario_dia'   => '#E8E7FF',
                'contraste_dia'          => '#000000',
                'color_primario_noche'   => '#4A3FCC',
                'color_secundario_noche' => '#140D36',
                'contraste_noche'        => '#FFFFFF',
                'font_size'              => '15px',
                'descripcion'            => 'Tema Juvenil',
            ],
            [
                'nombre'                 => 'adultos',
                'color_primario_dia'     => '#1D3557',
                'color_secundario_dia'   => '#A8DADC',
                'contraste_dia'          => '#000000',
                'color_primario_noche'   => '#0A1628',
                'color_secundario_noche' => '#1D3557',
                'contraste_noche'        => '#FFFFFF',
                'font_size'              => '14px',
                'descripcion'            => 'Tema Adulto (Por defecto)',
            ],
        ];

        foreach ($temas as $tema) {
            Tema::updateOrCreate(
                ['nombre' => $tema['nombre']],
                $tema
            );
        }
    }
}
