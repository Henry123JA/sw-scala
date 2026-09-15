<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'mensualidad',
            'inscripcion',
            'horario_grupo',
            'grupo',
            'horario_docente',
            'especialidad_docente',
            'docente',
            'alumno',
            'curso',
            'sala',
            'especialidad',
            'metodo_pago',
            'pagina_visitada',
            'bitacora',
            'usuario',
            'rol_permiso',
            'rol_menu',
            'menu',
            'tema',
            'permiso',
            'rol'
        ];

        $isPgsql = DB::getDriverName() === 'pgsql';

        foreach ($tables as $table) {
            $cascade = $isPgsql ? ' CASCADE' : '';
            DB::statement("DROP TABLE IF EXISTS {$table}{$cascade}");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
