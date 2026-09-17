<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Rol;
use App\Models\Tema;
use App\Models\Usuario;
use App\Models\Docente;
use App\Models\Alumno;
use App\Models\Especialidad;
use App\Models\EspecialidadDocente;
use App\Models\Sala;
use App\Models\Curso;
use App\Models\Grupo;
use App\Models\HorarioDocente;
use App\Models\HorarioGrupo;
use App\Models\Inscripcion;
use Carbon\Carbon;

/**
 * DemoDataSeeder
 *
 * Populates the DB with realistic Bolivian data for demonstration:
 * - 1 Secretaria
 * - 5 Docentes with schedules and specialties
 * - 12 Alumnos///10
 * - 6 Cursos (Piano, Guitarra, Violín, Batería, Canto, Flauta)
 * - 8 Salas
 * - 10 Grupos with group schedules
 * - Inscripciones and Mensualidades with mixed states
 *
 * Idempotent: safe to run multiple times (uses firstOrCreate / updateOrCreate).
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $rolDocente     = Rol::where('nombre', 'Docente')->first();
        $rolAlumno      = Rol::where('nombre', 'Alumno')->first();
        $rolSecretaria  = Rol::where('nombre', 'Secretaria')->first();
        $temaDefault    = Tema::where('nombre', 'adultos')->first();


        // ─────────────────────────────────────────────────────────────
        // 1. SECRETARIA
        // ─────────────────────────────────────────────────────────────
        Usuario::updateOrCreate(
            ['email' => 'secretaria@academia.com'],
            [
                'nombres'   => 'Valeria',
                'apellidos' => 'Quispe Mamani',
                'ci'        => '9876543',
                'codigo'    => 'SEC-001',
                'password'  => Hash::make('123456'),
                'rol_id'    => $rolSecretaria?->id,
                'tema_id'   => $temaDefault?->id,
                'eliminado' => false,
            ]
        );

        // ─────────────────────────────────────────────────────────────
        // 2. DOCENTES
        // ─────────────────────────────────────────────────────────────
        $docentesData = [
            [
                'nombres'           => 'Carlos Alberto',
                'apellidos'         => 'Flores Ríos',
                'ci'                => '4521789',
                'codigo'            => 'DOC-001',
                'email'             => 'carlos.flores@academia.com',
                'fecha_nacimiento'  => '1985-03-15',
                'fecha_incorporacion' => '2020-01-10',
                'telefono'          => '70123456',
                'especialidades'    => ['Piano', 'Violín'],
                'observaciones'     => 'Graduado del Conservatorio Nacional de Bolivia.',
                'horarios'          => [
                    ['Lunes',     '08:00', '12:00'],
                    ['Miércoles', '08:00', '12:00'],
                    ['Viernes',   '08:00', '12:00'],
                ],
            ],
            [
                'nombres'           => 'María Elena',
                'apellidos'         => 'Torrico Aguilar',
                'ci'                => '3311890',
                'codigo'            => 'DOC-002',
                'email'             => 'maria.torrico@academia.com',
                'fecha_nacimiento'  => '1990-07-22',
                'fecha_incorporacion' => '2021-03-01',
                'telefono'          => '71234567',
                'especialidades'    => ['Canto', 'Piano'],
                'observaciones'     => 'Especialista en técnica vocal y canto lírico.',
                'horarios'          => [
                    ['Martes',    '14:00', '18:00'],
                    ['Jueves',    '14:00', '18:00'],
                    ['Sábado',    '09:00', '13:00'],
                ],
            ],
            [
                'nombres'           => 'Roberto',
                'apellidos'         => 'Chávez Mendoza',
                'ci'                => '5678234',
                'codigo'            => 'DOC-003',
                'email'             => 'roberto.chavez@academia.com',
                'fecha_nacimiento'  => '1982-11-08',
                'fecha_incorporacion' => '2019-08-15',
                'telefono'          => '72345678',
                'especialidades'    => ['Guitarra'],
                'observaciones'     => 'Guitarrista clásico y popular con 15 años de experiencia.',
                'horarios'          => [
                    ['Lunes',     '14:00', '20:00'],
                    ['Miércoles', '14:00', '20:00'],
                    ['Viernes',   '14:00', '20:00'],
                ],
            ],
            [
                'nombres'           => 'Paola',
                'apellidos'         => 'Mamani Condori',
                'ci'                => '6234891',
                'codigo'            => 'DOC-004',
                'email'             => 'paola.mamani@academia.com',
                'fecha_nacimiento'  => '1993-05-30',
                'fecha_incorporacion' => '2022-02-01',
                'telefono'          => '73456789',
                'especialidades'    => ['Flauta', 'Violín'],
                'observaciones'     => 'Flautista con formación en música andina y clásica.',
                'horarios'          => [
                    ['Martes',    '08:00', '13:00'],
                    ['Jueves',    '08:00', '13:00'],
                    ['Sábado',    '14:00', '18:00'],
                ],
            ],
            [
                'nombres'           => 'Diego Armando',
                'apellidos'         => 'Vargas Pereira',
                'ci'                => '7891234',
                'codigo'            => 'DOC-005',
                'email'             => 'diego.vargas@academia.com',
                'fecha_nacimiento'  => '1988-09-14',
                'fecha_incorporacion' => '2020-09-01',
                'telefono'          => '74567890',
                'especialidades'    => ['Batería'],
                'observaciones'     => 'Percusionista con experiencia en rock, jazz y música latina.',
                'horarios'          => [
                    ['Lunes',     '15:00', '21:00'],
                    ['Jueves',    '15:00', '21:00'],
                    ['Sábado',    '10:00', '16:00'],
                ],
            ],
        ];

        $docentes = [];
        foreach ($docentesData as $data) {
            $usuario = Usuario::updateOrCreate(
                ['email' => $data['email']],
                [
                    'nombres'   => $data['nombres'],
                    'apellidos' => $data['apellidos'],
                    'ci'        => $data['ci'],
                    'codigo'    => $data['codigo'],
                    'password'  => Hash::make('123456'),
                    'rol_id'    => $rolDocente?->id,
                    'tema_id'   => $temaDefault?->id,
                    'eliminado' => false,
                ]
            );

            $docente = Docente::updateOrCreate(
                ['id' => $usuario->id],
                [
                    'codigo'             => $data['codigo'],
                    'estado'             => 'ACTIVO',
                    'fecha_nacimiento'   => $data['fecha_nacimiento'],
                    'fecha_incorporacion'=> $data['fecha_incorporacion'],
                    'telefono'           => $data['telefono'],
                    'observaciones'      => $data['observaciones'],
                ]
            );

            // Especialidades
            $espIds = Especialidad::whereIn('nombre', $data['especialidades'])->pluck('id');
            foreach ($espIds as $espId) {
                EspecialidadDocente::firstOrCreate([
                    'docente_id'     => $docente->id,
                    'especialidad_id'=> $espId,
                ]);
            }

            // Horarios del docente
            foreach ($data['horarios'] as [$dia, $inicio, $fin]) {
                HorarioDocente::firstOrCreate(
                    ['docente_id' => $docente->id, 'dia_semana' => $dia],
                    ['hora_inicio' => $inicio, 'hora_fin' => $fin]
                );
            }

            $docentes[$data['codigo']] = $docente;
        }

        // ─────────────────────────────────────────────────────────────
        // 3. SALAS (Aulas)
        // ─────────────────────────────────────────────────────────────
        $salasData = [
            ['Sala Piano A',      'individual', 2,  1, 'Piano de cola Yamaha, espejo, atril'],
            ['Sala Piano B',      'individual', 2,  1, 'Piano vertical Kawai, espejo'],
            ['Sala Cuerdas',      'individual', 3,  2, 'Atriles, espejo, sillas ergonómicas'],
            ['Sala Guitarra',     'grupal',     8,  1, '8 sillas, atril, amplificador pequeño'],
            ['Sala de Batería',   'individual', 2,  2, 'Batería acústica, insonorizada'],
            ['Sala Vientos',      'individual', 3,  1, 'Atriles, espejo, piano digital'],
            ['Aula de Canto',     'individual', 4,  2, 'Piano digital, espejo, sistema de audio'],
            ['Salón de Conjuntos','grupal',    15,  3, 'Equipo de sonido completo, sillas, atriles'],
        ];

        $salas = [];
        foreach ($salasData as [$nombre, $tipo, $cap, $piso, $equip]) {
            $sala = Sala::firstOrCreate(
                ['nombre' => $nombre],
                [
                    'tipo'           => $tipo,
                    'capacidad'      => $cap,
                    'ubicacion_piso' => $piso,
                    'equipamiento'   => $equip,
                    'estado'         => 'ACTIVO',
                    'eliminado'      => false,
                ]
            );
            $salas[$nombre] = $sala;
        }

        // ─────────────────────────────────────────────────────────────
        // 4. CURSOS
        // ─────────────────────────────────────────────────────────────
        $cursosData = [
            [
                'nombre'           => 'Piano Básico',
                'descripcion'      => 'Curso introductorio de piano. Lectura musical, escalas y repertorio básico.',
                'tipo_ensenanza'   => 'individual',
                'duracion_estandar'=> 12,
            ],
            [
                'nombre'           => 'Piano Intermedio',
                'descripcion'      => 'Técnica avanzada, armonía, interpretación de obras clásicas y populares.',
                'tipo_ensenanza'   => 'individual',
                'duracion_estandar'=> 12,
            ],
            [
                'nombre'           => 'Guitarra Acústica',
                'descripcion'      => 'Rasgueos, acordes, fingerpicking y repertorio popular boliviano e internacional.',
                'tipo_ensenanza'   => 'grupal',
                'duracion_estandar'=> 10,
            ],
            [
                'nombre'           => 'Violín Inicial',
                'descripcion'      => 'Postura, arco, afinación y piezas del repertorio clásico para principiantes.',
                'tipo_ensenanza'   => 'individual',
                'duracion_estandar'=> 12,
            ],
            [
                'nombre'           => 'Batería y Percusión',
                'descripcion'      => 'Rudimentos, coordinación, ritmos de cumbia, rock, jazz y salsa.',
                'tipo_ensenanza'   => 'individual',
                'duracion_estandar'=> 10,
            ],
            [
                'nombre'           => 'Técnica Vocal',
                'descripcion'      => 'Respiración, resonancia, impostación y repertorio lírico y popular.',
                'tipo_ensenanza'   => 'individual',
                'duracion_estandar'=> 12,
            ],
        ];

        $cursos = [];
        foreach ($cursosData as $data) {
            $curso = Curso::firstOrCreate(
                ['nombre' => $data['nombre']],
                array_merge($data, ['estado' => 'ACTIVO', 'eliminado' => false])
            );
            $cursos[$data['nombre']] = $curso;
        }

        // ─────────────────────────────────────────────────────────────
        // 5. GRUPOS
        // ─────────────────────────────────────────────────────────────
        $gruposData = [
            [
                'codigo'    => 'GRP-PIA-01',
                'curso'     => 'Piano Básico',
                'docente'   => 'DOC-001',
                'nivel'     => 'básico',
                'cap'       => 6,
                'horarios'  => [
                    ['sala' => 'Sala Piano A', 'dia' => 'Lunes',     'inicio' => '09:00', 'fin' => '10:00', 'tipo' => 'clase'],
                    ['sala' => 'Sala Piano A', 'dia' => 'Miércoles', 'inicio' => '09:00', 'fin' => '10:00', 'tipo' => 'clase'],
                ],
            ],
            [
                'codigo'    => 'GRP-PIA-02',
                'curso'     => 'Piano Intermedio',
                'docente'   => 'DOC-001',
                'nivel'     => 'intermedio',
                'cap'       => 4,
                'horarios'  => [
                    ['sala' => 'Sala Piano B', 'dia' => 'Martes',   'inicio' => '10:00', 'fin' => '11:00', 'tipo' => 'clase'],
                    ['sala' => 'Sala Piano B', 'dia' => 'Viernes',  'inicio' => '10:00', 'fin' => '11:00', 'tipo' => 'clase'],
                ],
            ],
            [
                'codigo'    => 'GRP-GIT-01',
                'curso'     => 'Guitarra Acústica',
                'docente'   => 'DOC-003',
                'nivel'     => 'básico',
                'cap'       => 8,
                'horarios'  => [
                    ['sala' => 'Sala Guitarra', 'dia' => 'Lunes',     'inicio' => '16:00', 'fin' => '17:30', 'tipo' => 'clase'],
                    ['sala' => 'Sala Guitarra', 'dia' => 'Miércoles', 'inicio' => '16:00', 'fin' => '17:30', 'tipo' => 'clase'],
                ],
            ],
            [
                'codigo'    => 'GRP-GIT-02',
                'curso'     => 'Guitarra Acústica',
                'docente'   => 'DOC-003',
                'nivel'     => 'básico',
                'cap'       => 8,
                'horarios'  => [
                    ['sala' => 'Sala Guitarra', 'dia' => 'Martes',  'inicio' => '17:00', 'fin' => '18:30', 'tipo' => 'clase'],
                    ['sala' => 'Sala Guitarra', 'dia' => 'Jueves',  'inicio' => '17:00', 'fin' => '18:30', 'tipo' => 'clase'],
                ],
            ],
            [
                'codigo'    => 'GRP-VIO-01',
                'curso'     => 'Violín Inicial',
                'docente'   => 'DOC-004',
                'nivel'     => 'básico',
                'cap'       => 4,
                'horarios'  => [
                    ['sala' => 'Sala Cuerdas', 'dia' => 'Martes',  'inicio' => '09:00', 'fin' => '10:00', 'tipo' => 'clase'],
                    ['sala' => 'Sala Cuerdas', 'dia' => 'Jueves',  'inicio' => '09:00', 'fin' => '10:00', 'tipo' => 'clase'],
                ],
            ],
            [
                'codigo'    => 'GRP-BAT-01',
                'curso'     => 'Batería y Percusión',
                'docente'   => 'DOC-005',
                'nivel'     => 'básico',
                'cap'       => 3,
                'horarios'  => [
                    ['sala' => 'Sala de Batería', 'dia' => 'Lunes',  'inicio' => '16:00', 'fin' => '17:00', 'tipo' => 'clase'],
                    ['sala' => 'Sala de Batería', 'dia' => 'Jueves', 'inicio' => '16:00', 'fin' => '17:00', 'tipo' => 'clase'],
                ],
            ],
            [
                'codigo'    => 'GRP-CAN-01',
                'curso'     => 'Técnica Vocal',
                'docente'   => 'DOC-002',
                'nivel'     => 'básico',
                'cap'       => 5,
                'horarios'  => [
                    ['sala' => 'Aula de Canto', 'dia' => 'Martes',  'inicio' => '15:00', 'fin' => '16:00', 'tipo' => 'clase'],
                    ['sala' => 'Aula de Canto', 'dia' => 'Jueves',  'inicio' => '15:00', 'fin' => '16:00', 'tipo' => 'clase'],
                ],
            ],
            [
                'codigo'    => 'GRP-FLA-01',
                'curso'     => 'Violín Inicial',   // Flauta usa mismo curso base, docente Paola
                'docente'   => 'DOC-004',
                'nivel'     => 'básico',
                'cap'       => 4,
                'horarios'  => [
                    ['sala' => 'Sala Vientos', 'dia' => 'Martes',  'inicio' => '11:00', 'fin' => '12:00', 'tipo' => 'clase'],
                    ['sala' => 'Sala Vientos', 'dia' => 'Jueves',  'inicio' => '11:00', 'fin' => '12:00', 'tipo' => 'clase'],
                ],
            ],
        ];

        $grupos = [];
        foreach ($gruposData as $gd) {
            $docente = $docentes[$gd['docente']];
            $curso   = $cursos[$gd['curso']];

            $grupo = Grupo::firstOrCreate(
                ['codigo_grupo' => $gd['codigo']],
                [
                    'curso_id'        => $curso->id,
                    'docente_id'      => $docente->id,
                    'nivel'           => $gd['nivel'],
                    'capacidad_maxima'=> $gd['cap'],
                    'estado'          => 'ACTIVO',
                    'eliminado'       => false,
                ]
            );

            foreach ($gd['horarios'] as $h) {
                HorarioGrupo::firstOrCreate(
                    ['grupo_id' => $grupo->id, 'dia_semana' => $h['dia']],
                    [
                        'sala_id'     => $salas[$h['sala']]->id,
                        'hora_inicio' => $h['inicio'],
                        'hora_fin'    => $h['fin'],
                        'tipo_sesion' => $h['tipo'],
                    ]
                );
            }

            $grupos[$gd['codigo']] = $grupo;
        }

        // ─────────────────────────────────────────────────────────────
        // 6. ALUMNOS (10 alumnos bolivianos)
        // ─────────────────────────────────────────────────────────────
        $alumnosData = [
            [
                'nombres'   => 'Sofía',
                'apellidos' => 'Laime Quispe',
                'ci'        => '8821345',
                'codigo'    => 'ALU-001',
                'email'     => 'sofia.laime@gmail.com',
                'nacimiento'=> '2010-04-12',
                'sexo'      => 'F',
                'telefono'  => '75123456',
                'nivel'     => 'principiante',
            ],
            [
                'nombres'   => 'Mateo',
                'apellidos' => 'Cusi Huanca',
                'ci'        => '9234567',
                'codigo'    => 'ALU-002',
                'email'     => 'mateo.cusi@gmail.com',
                'nacimiento'=> '2008-09-05',
                'sexo'      => 'M',
                'telefono'  => '76234567',
                'nivel'     => 'principiante',
            ],
            [
                'nombres'   => 'Valentina',
                'apellidos' => 'Chura Apaza',
                'ci'        => '7654321',
                'codigo'    => 'ALU-003',
                'email'     => 'valentina.chura@gmail.com',
                'nacimiento'=> '2005-12-30',
                'sexo'      => 'F',
                'telefono'  => '77345678',
                'nivel'     => 'intermedio',
            ],
            [
                'nombres'   => 'Andrés',
                'apellidos' => 'Mamani Poma',
                'ci'        => '8123456',
                'codigo'    => 'ALU-004',
                'email'     => 'andres.mamani@gmail.com',
                'nacimiento'=> '1998-06-15',
                'sexo'      => 'M',
                'telefono'  => '78456789',
                'nivel'     => 'principiante',
            ],
            [
                'nombres'   => 'Camila',
                'apellidos' => 'Torrez Baldivia',
                'ci'        => '5432198',
                'codigo'    => 'ALU-005',
                'email'     => 'camila.torrez@gmail.com',
                'nacimiento'=> '2012-02-20',
                'sexo'      => 'F',
                'telefono'  => '79567890',
                'nivel'     => 'principiante',
            ],
            [
                'nombres'   => 'Diego',
                'apellidos' => 'Condori Mamani',
                'ci'        => '6789012',
                'codigo'    => 'ALU-006',
                'email'     => 'diego.condori@gmail.com',
                'nacimiento'=> '2003-08-18',
                'sexo'      => 'M',
                'telefono'  => '70678901',
                'nivel'     => 'intermedio',
            ],
            [
                'nombres'   => 'Isabella',
                'apellidos' => 'Rojas Sánchez',
                'ci'        => '4567890',
                'codigo'    => 'ALU-007',
                'email'     => 'isabella.rojas@gmail.com',
                'nacimiento'=> '2015-11-03',
                'sexo'      => 'F',
                'telefono'  => '71789012',
                'nivel'     => 'principiante',
            ],
            [
                'nombres'   => 'Sebastián',
                'apellidos' => 'Vega Mendoza',
                'ci'        => '3456789',
                'codigo'    => 'ALU-008',
                'email'     => 'sebastian.vega@gmail.com',
                'nacimiento'=> '2007-05-25',
                'sexo'      => 'M',
                'telefono'  => '72890123',
                'nivel'     => 'principiante',
            ],
            [
                'nombres'   => 'Lucia',
                'apellidos' => 'Quispe Vargas',
                'ci'        => '2345678',
                'codigo'    => 'ALU-009',
                'email'     => 'lucia.quispe@gmail.com',
                'nacimiento'=> '2001-03-07',
                'sexo'      => 'F',
                'telefono'  => '73901234',
                'nivel'     => 'intermedio',
            ],
            [
                'nombres'   => 'Miguel Ángel',
                'apellidos' => 'Salinas Pedraza',
                'ci'        => '4569871',
                'codigo'    => 'ALU-010',
                'email'     => 'miguel.salinas@gmail.com',
                'nacimiento'=> '1995-01-28',
                'sexo'      => 'M',
                'telefono'  => '76234891',
                'nivel'     => 'avanzado',
            ],
        ];

        $alumnos = [];
        foreach ($alumnosData as $ad) {
            $usuario = Usuario::updateOrCreate(
                ['email' => $ad['email']],
                [
                    'nombres'   => $ad['nombres'],
                    'apellidos' => $ad['apellidos'],
                    'ci'        => $ad['ci'],
                    'codigo'    => $ad['codigo'],
                    'password'  => Hash::make('123456'),
                    'rol_id'    => $rolAlumno?->id,
                    'tema_id'   => $temaDefault?->id,
                    'eliminado' => false,
                ]
            );

            $alumno = Alumno::updateOrCreate(
                ['id' => $usuario->id],
                [
                    'codigo'          => $ad['codigo'],
                    'estado'          => 'ACTIVO',
                    'fecha_nacimiento'=> $ad['nacimiento'],
                    'sexo'            => $ad['sexo'],
                    'telefono'        => $ad['telefono'],
                    'nivel'           => $ad['nivel'],
                ]
            );

            $alumnos[$ad['codigo']] = $alumno;
        }

        // ─────────────────────────────────────────────────────────────
        // 7. INSCRIPCIONES — CICLO 1 ACADÉMICO
        //    - Estados válidos: ACTIVA, PAUSADA, CANCELADA
        //    - CANCELADA conserva registro como historial con fecha_retiro
        // ─────────────────────────────────────────────────────────────
        $inscripcionesMap = [
            // [alumno_codigo, grupo_codigo, fecha_inicio, estado, fecha_pausa, fecha_retorno, fecha_retiro]
            ['ALU-001', 'GRP-PIA-01', '2026-03-01', 'ACTIVA', null, null, null],
            ['ALU-002', 'GRP-GIT-01', '2026-04-01', 'ACTIVA', null, null, null],
            ['ALU-003', 'GRP-PIA-02', '2026-05-01', 'ACTIVA', null, null, null],
            ['ALU-005', 'GRP-CAN-01', '2026-06-01', 'ACTIVA', null, null, null],
            ['ALU-010', 'GRP-GIT-01', '2026-06-15', 'ACTIVA', null, null, null],

            // PAUSADAS (pausadas temporalmente por razones académicas)
            ['ALU-004', 'GRP-VIO-01', '2026-05-01', 'PAUSADA', '2026-07-01', null, null],
            ['ALU-006', 'GRP-PIA-01', '2026-02-15', 'PAUSADA', '2026-08-01', null, null],

            // CANCELADAS (retiradas académicamente, conservadas como historial)
            ['ALU-004', 'GRP-GIT-02', '2026-03-01', 'CANCELADA', null, null, '2026-08-15'],
            ['ALU-007', 'GRP-VIO-01', '2026-04-01', 'CANCELADA', null, null, '2026-08-20'],
            ['ALU-008', 'GRP-BAT-01', '2026-05-01', 'CANCELADA', null, null, '2026-09-01'],
            ['ALU-009', 'GRP-CAN-01', '2026-01-15', 'CANCELADA', null, null, '2026-07-30'],

            // Alumno en dos grupos
            ['ALU-003', 'GRP-GIT-02', '2026-05-15', 'ACTIVA', null, null, null],
        ];

        foreach ($inscripcionesMap as $entry) {
            [$aluCod, $grpCod, $fechaInicioStr, $estado, $fechaPausaStr, $fechaRetornoStr, $fechaRetiroStr] = $entry;

            $alumno = $alumnos[$aluCod];
            $grupo  = $grupos[$grpCod];
            $fechaInicio = Carbon::parse($fechaInicioStr);

            Inscripcion::updateOrCreate(
                ['alumno_id' => $alumno->id, 'grupo_id' => $grupo->id],
                [
                    'fecha'               => $fechaInicio,
                    'fecha_inicio_clases' => $fechaInicio,
                    'fecha_pausa'         => $fechaPausaStr ? Carbon::parse($fechaPausaStr) : null,
                    'fecha_retorno'       => $fechaRetornoStr ? Carbon::parse($fechaRetornoStr) : null,
                    'fecha_retiro'        => $fechaRetiroStr ? Carbon::parse($fechaRetiroStr) : null,
                    'estado'              => $estado,
                    'observaciones'       => null,
                ]
            );
        }
    }
}
