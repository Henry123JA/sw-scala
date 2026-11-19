<?php

namespace Database\Seeders;

use App\Models\AlertaSeguimiento;
use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Bitacora;
use App\Models\Calificacion;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Especialidad;
use App\Models\EspecialidadDocente;
use App\Models\Evaluacion;
use App\Models\Grupo;
use App\Models\HorarioDocente;
use App\Models\HorarioGrupo;
use App\Models\Inscripcion;
use App\Models\IntervencionSeguimiento;
use App\Models\Menu;
use App\Models\ObservacionAcademica;
use App\Models\PaginaVisitada;
use App\Models\Rol;
use App\Models\Sala;
use App\Models\SesionClase;
use App\Models\Usuario;
use App\Services\RiesgoPredictivoService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * VolumenMasivoSeeder
 *
 * Expande todas las tablas operativas de la Academia de Música SCALA para contar con
 * entre 40 y 50+ registros realistas por tabla (usuarios, alumnos, docentes, horarios,
 * salas, cursos, grupos, inscripciones, sesiones, asistencias, evaluaciones, calificaciones,
 * observaciones académicas, alertas predictivas, intervenciones, bitácora y páginas visitadas).
 */
class VolumenMasivoSeeder extends Seeder
{
    public function run(): void
    {
        $rolDocente    = Rol::where('nombre', 'Docente')->first();
        $rolAlumno     = Rol::where('nombre', 'Alumno')->first();
        $rolSecretaria = Rol::where('nombre', 'Secretaria')->first();
        $passwordHash  = Hash::make('123456');

        // ─────────────────────────────────────────────────────────────────────
        // 1. SECRETARIAS ADICIONALES (SEC-002 a SEC-004)
        // ─────────────────────────────────────────────────────────────────────
        $secretariasExtra = [
            ['SEC-002', 'Gabriela', 'Morales Justiniano', '6412389', 'gabriela.morales@academia.com'],
            ['SEC-003', 'Daniela',  'Saucedo Roca',       '6519823', 'daniela.saucedo@academia.com'],
            ['SEC-004', 'Patricia', 'Añez Gutiérrez',     '6623491', 'patricia.anez@academia.com'],
        ];

        foreach ($secretariasExtra as [$cod, $nom, $ape, $ci, $mail]) {
            Usuario::updateOrCreate(
                ['email' => $mail],
                [
                    'nombres'               => $nom,
                    'apellidos'             => $ape,
                    'ci'                    => $ci,
                    'codigo'                => $cod,
                    'password'              => $passwordHash,
                    'rol_id'                => $rolSecretaria?->id,
                    'politica_aceptada_at'  => now()->subDays(rand(5, 30)),
                    'terminos_aceptados_at' => now()->subDays(rand(5, 30)),
                    'eliminado'             => false,
                ]
            );
        }

        // ─────────────────────────────────────────────────────────────────────
        // 2. ESPECIALIDADES ADICIONALES
        // ─────────────────────────────────────────────────────────────────────
        $nuevasEspecialidades = [
            'Violonchelo', 'Saxofón', 'Bajo Eléctrico', 'Charango', 'Ukelele',
            'Teoría Musical', 'Dirección Coral', 'Acordeón', 'Trompeta', 'Clarinete',
            'Armonía Moderna', 'Producción Musical', 'Percusión Folclórica', 'Guitarra Eléctrica',
        ];
        foreach ($nuevasEspecialidades as $espNombre) {
            Especialidad::firstOrCreate(
                ['nombre' => $espNombre],
                ['descripcion' => "Especialidad académica en {$espNombre}", 'eliminado' => false]
            );
        }

        // ─────────────────────────────────────────────────────────────────────
        // 3. DOCENTES ADICIONALES (DOC-006 a DOC-020 -> 20 Docentes y 50+ Horarios Docente)
        // ─────────────────────────────────────────────────────────────────────
        $docentesExtra = [
            ['DOC-006', 'Fernando',      'Ribera Saucedo',    '4812006', 'fernando.ribera@academia.com',    '1984-02-11', '2020-03-10', '71001006', ['Guitarra', 'Bajo Eléctrico']],
            ['DOC-007', 'Claudia',       'Paz Estenssoro',    '4812007', 'claudia.paz@academia.com',        '1991-06-18', '2021-04-12', '71001007', ['Violín', 'Violonchelo']],
            ['DOC-008', 'Jorge Luis',    'Roca Hurtado',      '4812008', 'jorge.roca@academia.com',         '1980-09-25', '2019-05-20', '71001008', ['Saxofón', 'Flauta']],
            ['DOC-009', 'Verónica',      'Suárez Banzer',     '4812009', 'veronica.suarez@academia.com',    '1989-12-03', '2022-01-15', '71001009', ['Piano', 'Teoría Musical']],
            ['DOC-010', 'Mauricio',      'Justiniano Céspedes','4812010', 'mauricio.justiniano@academia.com','1986-04-29', '2020-08-01', '71001010', ['Batería', 'Percusión Folclórica']],
            ['DOC-011', 'Ana Lucía',     'Gutiérrez Antelo',  '4812011', 'analucia.gutierrez@academia.com', '1994-07-14', '2023-02-10', '71001011', ['Canto', 'Dirección Coral']],
            ['DOC-012', 'Ramiro',        'Choquetopa Nina',   '4812012', 'ramiro.choquetopa@academia.com',  '1983-10-08', '2019-11-05', '71001012', ['Charango', 'Guitarra']],
            ['DOC-013', 'Fabiola',       'Cronenbold Vaca',   '4812013', 'fabiola.cronenbold@academia.com', '1992-01-21', '2022-06-18', '71001013', ['Piano', 'Acordeón']],
            ['DOC-014', 'Álvaro',        'Méndez Castedo',    '4812014', 'alvaro.mendez@academia.com',      '1987-08-19', '2021-09-01', '71001014', ['Guitarra Eléctrica', 'Producción Musical']],
            ['DOC-015', 'Natalia',       'Ardaya Landívar',   '4812015', 'natalia.ardaya@academia.com',     '1995-03-27', '2023-07-01', '71001015', ['Ukelele', 'Canto']],
            ['DOC-016', 'Héctor',        'Salvatierra Paz',   '4812016', 'hector.salvatierra@academia.com', '1981-05-12', '2018-03-15', '71001016', ['Trompeta', 'Saxofón']],
            ['DOC-017', 'Mónica',        'Cuéllar Dorado',    '4812017', 'monica.cuellar@academia.com',     '1990-11-11', '2021-02-01', '71001017', ['Violín', 'Piano']],
            ['DOC-018', 'Sebastián',     'Ávila Soleto',      '4812018', 'sebastian.avila@academia.com',    '1989-06-04', '2020-10-12', '71001018', ['Bajo Eléctrico', 'Armonía Moderna']],
            ['DOC-019', 'Lorena',        'Peñaranda Ortiz',   '4812019', 'lorena.penaranda@academia.com',   '1993-09-16', '2022-08-20', '71001019', ['Clarinete', 'Flauta']],
            ['DOC-020', 'Gustavo',       'Zeballos Terán',    '4812020', 'gustavo.zeballos@academia.com',   '1985-12-28', '2019-04-01', '71001020', ['Guitarra', 'Charango']],
        ];

        $diasCombo = [
            [['Lunes', '08:00', '12:30'], ['Miércoles', '08:00', '12:30'], ['Viernes', '08:00', '12:30']],
            [['Martes', '14:00', '19:30'], ['Jueves', '14:00', '19:30'], ['Sábado', '08:30', '13:00']],
            [['Lunes', '14:00', '20:00'], ['Miércoles', '14:00', '20:00'], ['Viernes', '14:00', '20:00']],
            [['Martes', '08:00', '12:30'], ['Jueves', '08:00', '12:30'], ['Sábado', '14:00', '18:30']],
        ];

        foreach ($docentesExtra as $idx => [$cod, $nom, $ape, $ci, $mail, $nac, $inc, $tel, $esps]) {
            $u = Usuario::updateOrCreate(
                ['email' => $mail],
                [
                    'nombres'               => $nom,
                    'apellidos'             => $ape,
                    'ci'                    => $ci,
                    'codigo'                => $cod,
                    'password'              => $passwordHash,
                    'rol_id'                => $rolDocente?->id,
                    'politica_aceptada_at'  => now()->subDays(rand(10, 45)),
                    'terminos_aceptados_at' => now()->subDays(rand(10, 45)),
                    'eliminado'             => false,
                ]
            );

            $doc = Docente::updateOrCreate(
                ['id' => $u->id],
                [
                    'codigo'              => $cod,
                    'estado'              => 'ACTIVO',
                    'fecha_nacimiento'    => $nac,
                    'fecha_incorporacion' => $inc,
                    'telefono'            => $tel,
                    'observaciones'       => 'Docente titular del plantel académico SCALA.',
                ]
            );

            $espIds = Especialidad::whereIn('nombre', $esps)->pluck('id');
            foreach ($espIds as $espId) {
                EspecialidadDocente::firstOrCreate([
                    'docente_id'      => $doc->id,
                    'especialidad_id' => $espId,
                ]);
            }

            foreach ($diasCombo[$idx % count($diasCombo)] as [$dia, $ini, $fin]) {
                HorarioDocente::firstOrCreate(
                    ['docente_id' => $doc->id, 'dia_semana' => $dia],
                    ['hora_inicio' => $ini, 'hora_fin' => $fin]
                );
            }
        }

        // ─────────────────────────────────────────────────────────────────────
        // 4. SALAS Y CURSOS ADICIONALES
        // ─────────────────────────────────────────────────────────────────────
        $salasExtra = [
            ['Sala Piano C',          'individual', 2,  1, 'Piano vertical Yamaha U1, metrónomo'],
            ['Sala Guitarra B',       'grupal',     10, 1, '10 sillas acústicas, atriles, pizarra pentagramada'],
            ['Sala Ensamble Rock',    'grupal',     8,  2, 'Amplificadores Marshall/Fender, batería, consola'],
            ['Sala Folclore Andino',  'grupal',     10, 2, 'Charangos, quenas, bombo legüero, atriles'],
            ['Sala Violonchelo',      'individual', 3,  2, 'Sillas de violonchelo, espejo de postura'],
            ['Laboratorio Teoría A',  'grupal',     16, 3, 'Pizarra acrílica pentagramada, teclado maestro, proyector'],
            ['Estudio Grabación SCALA','individual', 4, 3, 'Interfaz Focusrite, monitores KRK, micrófono condensador'],
            ['Auditorio de Cámara',   'grupal',     25, 3, 'Piano de media cola, escenario iluminado, 25 butacas'],
        ];

        foreach ($salasExtra as [$nombre, $tipo, $cap, $piso, $equip]) {
            Sala::firstOrCreate(
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
        }

        $cursosExtra = [
            ['Piano Avanzado',             'Interpretación de sonatas clásicas, estudios de Chopin y armonía aplicada.', 'individual', 12],
            ['Guitarra Eléctrica Moderna', 'Técnicas de púa, bending, tapping, escalas pentatónicas y efectos.', 'grupal', 10],
            ['Violín Intermedio',          'Cambios de tercera posición, vibrato y repertorio barroco.', 'individual', 12],
            ['Charango Boliviano',         'Rasgueos tradicionales, kalampeado, trémolo y repertorio nacional.', 'grupal', 10],
            ['Bajo Eléctrico y Groove',    'Slap, walking bass, lectura en clave de Fa y acompañamiento rítmico.', 'individual', 10],
            ['Saxofón Alto y Tenor',       'Embocadura, respiración, articulación jazz y repertorio moderno.', 'individual', 12],
            ['Ukelele y Canto Infantil',   'Iniciación musical lúdica con acordes básicos y entonación.', 'grupal', 8],
            ['Teoría Musical y Solfeo',    'Lectura entonada, dictado rítmico-melódico y análisis armónico.', 'grupal', 12],
            ['Violonchelo Clásico',        'Arco, posición del pulgar, suites de Bach y música de cámara.', 'individual', 12],
            ['Coro Polifónico SCALA',      'Práctica coral a 3 y 4 voces, respiración grupal y repertorio universal.', 'grupal', 12],
        ];

        foreach ($cursosExtra as [$nombre, $desc, $tipo, $dur]) {
            Curso::firstOrCreate(
                ['nombre' => $nombre],
                [
                    'descripcion'       => $desc,
                    'tipo_ensenanza'    => $tipo,
                    'duracion_estandar' => $dur,
                    'estado'            => 'ACTIVO',
                    'eliminado'         => false,
                ]
            );
        }

        // ─────────────────────────────────────────────────────────────────────
        // 5. GRUPOS ADICIONALES (Hasta 24 Grupos y 48 Horarios de Grupo)
        // ─────────────────────────────────────────────────────────────────────
        $gruposExtra = [
            ['GRP-PIA-03', 'Piano Básico',               'DOC-009', 'básico',     6, 'Sala Piano C',          'Lunes',  '15:00', '16:00', 'Miércoles', '15:00', '16:00'],
            ['GRP-PIA-04', 'Piano Avanzado',             'DOC-013', 'avanzado',   4, 'Sala Piano A',          'Martes', '15:00', '16:30', 'Jueves',    '15:00', '16:30'],
            ['GRP-GIT-03', 'Guitarra Acústica',          'DOC-006', 'intermedio', 8, 'Sala Guitarra B',       'Lunes',  '09:00', '10:30', 'Miércoles', '09:00', '10:30'],
            ['GRP-GEL-01', 'Guitarra Eléctrica Moderna', 'DOC-014', 'básico',     8, 'Sala Ensamble Rock',    'Martes', '16:00', '17:30', 'Jueves',    '16:00', '17:30'],
            ['GRP-VIO-02', 'Violín Intermedio',          'DOC-007', 'intermedio', 5, 'Sala Cuerdas',          'Lunes',  '16:00', '17:00', 'Miércoles', '16:00', '17:00'],
            ['GRP-CHA-01', 'Charango Boliviano',         'DOC-012', 'básico',     8, 'Sala Folclore Andino',  'Lunes',  '17:00', '18:30', 'Viernes',   '17:00', '18:30'],
            ['GRP-CHA-02', 'Charango Boliviano',         'DOC-020', 'intermedio', 8, 'Sala Folclore Andino',  'Martes', '17:00', '18:30', 'Jueves',    '17:00', '18:30'],
            ['GRP-BAJ-01', 'Bajo Eléctrico y Groove',    'DOC-018', 'básico',     5, 'Sala Ensamble Rock',    'Lunes',  '10:00', '11:00', 'Miércoles', '10:00', '11:00'],
            ['GRP-SAX-01', 'Saxofón Alto y Tenor',       'DOC-008', 'básico',     5, 'Sala Vientos',          'Lunes',  '15:00', '16:00', 'Miércoles', '15:00', '16:00'],
            ['GRP-UKE-01', 'Ukelele y Canto Infantil',   'DOC-015', 'básico',     8, 'Sala Guitarra B',       'Martes', '09:30', '11:00', 'Jueves',    '09:30', '11:00'],
            ['GRP-TEO-01', 'Teoría Musical y Solfeo',    'DOC-009', 'básico',     12,'Laboratorio Teoría A',  'Lunes',  '11:00', '12:30', 'Viernes',   '11:00', '12:30'],
            ['GRP-TEO-02', 'Teoría Musical y Solfeo',    'DOC-009', 'intermedio', 12,'Laboratorio Teoría A',  'Martes', '17:30', '19:00', 'Jueves',    '17:30', '19:00'],
            ['GRP-CHE-01', 'Violonchelo Clásico',        'DOC-007', 'básico',     4, 'Sala Violonchelo',      'Martes', '14:00', '15:00', 'Jueves',    '14:00', '15:00'],
            ['GRP-COR-01', 'Coro Polifónico SCALA',      'DOC-011', 'intermedio', 14,'Auditorio de Cámara',   'Miércoles','18:00','19:30','Viernes',   '18:00', '19:30'],
            ['GRP-BAT-02', 'Batería y Percusión',        'DOC-010', 'intermedio', 4, 'Sala de Batería',       'Martes', '15:00', '16:00', 'Jueves',    '15:00', '16:00'],
            ['GRP-CAN-02', 'Técnica Vocal',              'DOC-011', 'intermedio', 6, 'Aula de Canto',         'Lunes',  '16:30', '17:30', 'Miércoles', '16:30', '17:30'],
        ];

        foreach ($gruposExtra as [$codGrp, $nomCurso, $codDoc, $nivel, $cap, $nomSala, $d1, $i1, $f1, $d2, $i2, $f2]) {
            $curso   = Curso::where('nombre', $nomCurso)->first();
            $docente = Docente::where('codigo', $codDoc)->first();
            $sala    = Sala::where('nombre', $nomSala)->first() ?? Sala::first();

            if (!$curso || !$docente || !$sala) {
                continue;
            }

            $grupo = Grupo::firstOrCreate(
                ['codigo_grupo' => $codGrp],
                [
                    'curso_id'         => $curso->id,
                    'docente_id'       => $docente->id,
                    'nivel'            => $nivel,
                    'capacidad_maxima' => $cap,
                    'estado'           => 'ACTIVO',
                    'eliminado'        => false,
                ]
            );

            HorarioGrupo::firstOrCreate(
                ['grupo_id' => $grupo->id, 'dia_semana' => $d1],
                ['sala_id' => $sala->id, 'hora_inicio' => $i1, 'hora_fin' => $f1, 'tipo_sesion' => 'clase']
            );
            HorarioGrupo::firstOrCreate(
                ['grupo_id' => $grupo->id, 'dia_semana' => $d2],
                ['sala_id' => $sala->id, 'hora_inicio' => $i2, 'hora_fin' => $f2, 'tipo_sesion' => 'clase']
            );
        }

        // ─────────────────────────────────────────────────────────────────────
        // 6. ALUMNOS ADICIONALES (ALU-011 a ALU-048 -> 48 Alumnos en total)
        // ─────────────────────────────────────────────────────────────────────
        $alumnosExtra = [
            ['ALU-011', 'Alejandro',  'Suárez Justiniano', '8901011', 'alejandro.suarez@gmail.com',   '2006-03-14', 'M', '75001011', 'intermedio'],
            ['ALU-012', 'Fernanda',   'Ribera Saucedo',    '8901012', 'fernanda.ribera@gmail.com',    '2009-07-21', 'F', '75001012', 'principiante'],
            ['ALU-013', 'Gabriel',    'Hurtado Roca',      '8901013', 'gabriel.hurtado@gmail.com',    '2004-11-08', 'M', '75001013', 'avanzado'],
            ['ALU-014', 'Mariana',    'Céspedes Antelo',   '8901014', 'mariana.cespedes@gmail.com',   '2011-01-19', 'F', '75001014', 'principiante'],
            ['ALU-015', 'Nicolás',    'Banzer Gutiérrez',  '8901015', 'nicolas.banzer@gmail.com',     '2007-05-30', 'M', '75001015', 'intermedio'],
            ['ALU-016', 'Valeria',    'Vaca Diez',         '8901016', 'valeria.vacadiez@gmail.com',   '2010-09-12', 'F', '75001016', 'principiante'],
            ['ALU-017', 'Joaquín',    'Castedo Dorado',    '8901017', 'joaquin.castedo@gmail.com',    '2002-12-04', 'M', '75001017', 'avanzado'],
            ['ALU-018', 'Antonella',  'Landívar Paz',      '8901018', 'antonella.landivar@gmail.com', '2013-04-25', 'F', '75001018', 'principiante'],
            ['ALU-019', 'Emiliano',   'Soleto Cuéllar',    '8901019', 'emiliano.soleto@gmail.com',    '2008-08-16', 'M', '75001019', 'intermedio'],
            ['ALU-020', 'Renata',     'Ortiz Peñaranda',   '8901020', 'renata.ortiz@gmail.com',       '2005-02-28', 'F', '75001020', 'intermedio'],
            ['ALU-021', 'Santiago',   'Terán Zeballos',    '8901021', 'santiago.teran@gmail.com',     '2009-06-10', 'M', '75001021', 'principiante'],
            ['ALU-022', 'Victoria',   'Aguilera Monasterio','8901022','victoria.aguilera@gmail.com',  '2006-10-22', 'F', '75001022', 'intermedio'],
            ['ALU-023', 'Matías',     'Choquevillca Nina', '8901023', 'matias.choquevillca@gmail.com','2003-01-15', 'M', '75001023', 'avanzado'],
            ['ALU-024', 'Abril',      'ticona Apaza',      '8901024', 'abril.ticona@gmail.com',       '2012-07-07', 'F', '75001024', 'principiante'],
            ['ALU-025', 'Leonardo',   'Callisaya Poma',    '8901025', 'leonardo.callisaya@gmail.com', '2001-11-19', 'M', '75001025', 'intermedio'],
            ['ALU-026', 'Martina',    'Yujra Huanca',      '8901026', 'martina.yujra@gmail.com',      '2010-03-03', 'F', '75001026', 'principiante'],
            ['ALU-027', 'Samuel',     'Canaviri Quispe',   '8901027', 'samuel.canaviri@gmail.com',    '2007-09-27', 'M', '75001027', 'intermedio'],
            ['ALU-028', 'Julieta',    'Catacora Mamani',   '8901028', 'julieta.catacora@gmail.com',   '2014-05-14', 'F', '75001028', 'principiante'],
            ['ALU-029', 'Benjamín',   'Zalles Siles',      '8901029', 'benjamin.zalles@gmail.com',    '2004-08-09', 'M', '75001029', 'avanzado'],
            ['ALU-030', 'Catalina',   'OrmacheaCalvo',     '8901030', 'catalina.ormachea@gmail.com',  '2008-12-31', 'F', '75001030', 'intermedio'],
            ['ALU-031', 'Tomás',      'Gamarra Loza',      '8901031', 'tomas.gamarra@gmail.com',      '2006-04-17', 'M', '75001031', 'principiante'],
            ['ALU-032', 'Agustina',   'Ballivián Reyes',   '8901032', 'agustina.ballivian@gmail.com', '2011-10-05', 'F', '75001032', 'principiante'],
            ['ALU-033', 'Lucas',      'Arce Campero',      '8901033', 'lucas.arce@gmail.com',         '2000-02-12', 'M', '75001033', 'avanzado'],
            ['ALU-034', 'Elena',      'virreira Gumucio',  '8901034', 'elena.virreira@gmail.com',     '2009-06-24', 'F', '75001034', 'intermedio'],
            ['ALU-035', 'Maximiliano','Urquidi Laredo',    '8901035', 'maximiliano.urquidi@gmail.com','2005-09-01', 'M', '75001035', 'intermedio'],
            ['ALU-036', 'Zoe',        'Capriles Rico',     '8901036', 'zoe.capriles@gmail.com',       '2013-01-29', 'F', '75001036', 'principiante'],
            ['ALU-037', 'Thiago',     'Anaya Céspedes',    '8901037', 'thiago.anaya@gmail.com',       '2007-07-18', 'M', '75001037', 'principiante'],
            ['ALU-038', 'Mía',        'Tardío Zamora',     '8901038', 'mia.tardio@gmail.com',         '2010-11-11', 'F', '75001038', 'principiante'],
            ['ALU-039', 'Gael',       'Daza Zilveti',      '8901039', 'gael.daza@gmail.com',          '2003-03-23', 'M', '75001039', 'intermedio'],
            ['ALU-040', 'Bianca',     'Ostria Reynolds',   '8901040', 'bianca.ostria@gmail.com',      '2006-08-06', 'F', '75001040', 'avanzado'],
            ['ALU-041', 'Bruno',      'Navajas Methfessel','8901041', 'bruno.navajas@gmail.com',      '2008-05-15', 'M', '75001041', 'intermedio'],
            ['ALU-042', 'Alma',       'Kohlberg Castellanos','8901042','alma.kohlberg@gmail.com',     '2012-12-09', 'F', '75001042', 'principiante'],
            ['ALU-043', 'Ian',        'Rodo Pantoja',      '8901043', 'ian.rodo@gmail.com',           '2004-04-02', 'M', '75001043', 'intermedio'],
            ['ALU-044', 'Clara',      'Bruckner Moreno',   '8901044', 'clara.bruckner@gmail.com',     '2009-10-16', 'F', '75001044', 'principiante'],
            ['ALU-045', 'Dante',      'Chávez Gil',        '8901045', 'dante.chavez@gmail.com',       '2001-06-28', 'M', '75001045', 'avanzado'],
            ['ALU-046', 'Regina',     'Leigue Dellien',    '8901046', 'regina.leigue@gmail.com',      '2007-02-14', 'F', '75001046', 'intermedio'],
            ['ALU-047', 'Cristóbal',  'Melgar Rioja',      '8901047', 'cristobal.melgar@gmail.com',   '2005-07-31', 'M', '75001047', 'intermedio'],
            ['ALU-048', 'Lola',       'Parada Bowles',     '8901048', 'lola.parada@gmail.com',        '2011-09-20', 'F', '75001048', 'principiante'],
        ];

        foreach ($alumnosExtra as [$cod, $nom, $ape, $ci, $mail, $nac, $sexo, $tel, $niv]) {
            $u = Usuario::updateOrCreate(
                ['email' => strtolower($mail)],
                [
                    'nombres'               => ucfirst($nom),
                    'apellidos'             => ucwords($ape),
                    'ci'                    => $ci,
                    'codigo'                => $cod,
                    'password'              => $passwordHash,
                    'rol_id'                => $rolAlumno?->id,
                    'politica_aceptada_at'  => now()->subDays(rand(3, 40)),
                    'terminos_aceptados_at' => now()->subDays(rand(3, 40)),
                    'eliminado'             => false,
                ]
            );

            Alumno::updateOrCreate(
                ['id' => $u->id],
                [
                    'codigo'           => $cod,
                    'estado'           => 'ACTIVO',
                    'fecha_nacimiento' => $nac,
                    'sexo'             => $sexo,
                    'telefono'         => $tel,
                    'nivel'            => $niv,
                ]
            );
        }

        // ─────────────────────────────────────────────────────────────────────
        // 7. INSCRIPCIONES ADICIONALES (38 nuevas -> 50 Inscripciones en total)
        // ─────────────────────────────────────────────────────────────────────
        $inscripcionesExtra = [
            ['ALU-011', 'GRP-PIA-03', '2026-08-01', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0001'],
            ['ALU-012', 'GRP-PIA-03', '2026-08-03', 'ACTIVA',    'PAGADO',    'EFECTIVO', 'REC-INS-2026-0002'],
            ['ALU-013', 'GRP-PIA-04', '2026-08-05', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0003'],
            ['ALU-014', 'GRP-PIA-04', '2026-08-06', 'ACTIVA',    'PENDIENTE', null,       null],
            ['ALU-015', 'GRP-GIT-03', '2026-08-10', 'ACTIVA',    'PAGADO',    'EFECTIVO', 'REC-INS-2026-0004'],
            ['ALU-016', 'GRP-GIT-03', '2026-08-11', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0005'],
            ['ALU-017', 'GRP-GEL-01', '2026-08-12', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0006'],
            ['ALU-018', 'GRP-GEL-01', '2026-08-14', 'PAUSADA',   'PAGADO',    'EFECTIVO', 'REC-INS-2026-0007'],
            ['ALU-019', 'GRP-VIO-02', '2026-08-15', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0008'],
            ['ALU-020', 'GRP-VIO-02', '2026-08-16', 'ACTIVA',    'PENDIENTE', null,       null],
            ['ALU-021', 'GRP-CHA-01', '2026-08-18', 'ACTIVA',    'PAGADO',    'EFECTIVO', 'REC-INS-2026-0009'],
            ['ALU-022', 'GRP-CHA-01', '2026-08-19', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0010'],
            ['ALU-023', 'GRP-CHA-02', '2026-08-20', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0011'],
            ['ALU-024', 'GRP-CHA-02', '2026-08-21', 'PAUSADA',   'PENDIENTE', null,       null],
            ['ALU-025', 'GRP-BAJ-01', '2026-08-22', 'ACTIVA',    'PAGADO',    'EFECTIVO', 'REC-INS-2026-0012'],
            ['ALU-026', 'GRP-BAJ-01', '2026-08-24', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0013'],
            ['ALU-027', 'GRP-SAX-01', '2026-08-25', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0014'],
            ['ALU-028', 'GRP-SAX-01', '2026-08-26', 'ACTIVA',    'PENDIENTE', null,       null],
            ['ALU-029', 'GRP-UKE-01', '2026-08-27', 'ACTIVA',    'PAGADO',    'EFECTIVO', 'REC-INS-2026-0015'],
            ['ALU-030', 'GRP-UKE-01', '2026-08-28', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0016'],
            ['ALU-031', 'GRP-TEO-01', '2026-09-01', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0017'],
            ['ALU-032', 'GRP-TEO-01', '2026-09-02', 'ACTIVA',    'PAGADO',    'EFECTIVO', 'REC-INS-2026-0018'],
            ['ALU-033', 'GRP-TEO-01', '2026-09-03', 'ACTIVA',    'PENDIENTE', null,       null],
            ['ALU-034', 'GRP-TEO-02', '2026-09-04', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0019'],
            ['ALU-035', 'GRP-TEO-02', '2026-09-05', 'PAUSADA',   'PAGADO',    'EFECTIVO', 'REC-INS-2026-0020'],
            ['ALU-036', 'GRP-CHE-01', '2026-09-07', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0021'],
            ['ALU-037', 'GRP-CHE-01', '2026-09-08', 'ACTIVA',    'PENDIENTE', null,       null],
            ['ALU-038', 'GRP-COR-01', '2026-09-09', 'ACTIVA',    'PAGADO',    'EFECTIVO', 'REC-INS-2026-0022'],
            ['ALU-039', 'GRP-COR-01', '2026-09-10', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0023'],
            ['ALU-040', 'GRP-COR-01', '2026-09-11', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0024'],
            ['ALU-041', 'GRP-BAT-02', '2026-09-12', 'ACTIVA',    'PAGADO',    'EFECTIVO', 'REC-INS-2026-0025'],
            ['ALU-042', 'GRP-BAT-02', '2026-09-14', 'CANCELADA', 'PENDIENTE', null,       null],
            ['ALU-043', 'GRP-CAN-02', '2026-09-15', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0026'],
            ['ALU-044', 'GRP-CAN-02', '2026-09-16', 'ACTIVA',    'PAGADO',    'EFECTIVO', 'REC-INS-2026-0027'],
            ['ALU-045', 'GRP-CAN-02', '2026-09-17', 'ACTIVA',    'PENDIENTE', null,       null],
            ['ALU-046', 'GRP-FLA-01', '2026-09-18', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0028'],
            ['ALU-047', 'GRP-FLA-01', '2026-09-19', 'ACTIVA',    'PAGADO',    'EFECTIVO', 'REC-INS-2026-0029'],
            ['ALU-048', 'GRP-PIA-01', '2026-09-20', 'ACTIVA',    'PAGADO',    'QR',       'REC-INS-2026-0030'],
        ];

        foreach ($inscripcionesExtra as [$codAlu, $codGrp, $fechaStr, $estado, $estPago, $metPago, $recibo]) {
            $alumno = Alumno::where('codigo', $codAlu)->first();
            $grupo  = Grupo::where('codigo_grupo', $codGrp)->first();

            if (!$alumno || !$grupo) {
                continue;
            }

            $fecha = Carbon::parse($fechaStr);

            Inscripcion::updateOrCreate(
                ['alumno_id' => $alumno->id, 'grupo_id' => $grupo->id],
                [
                    'fecha'               => $fecha,
                    'fecha_inicio_clases' => $fecha,
                    'fecha_pausa'         => $estado === 'PAUSADA' ? $fecha->copy()->addDays(25) : null,
                    'fecha_retiro'        => $estado === 'CANCELADA' ? $fecha->copy()->addDays(30) : null,
                    'estado'              => $estado,
                    'monto_inscripcion'   => 150.00,
                    'estado_pago'         => $estPago,
                    'metodo_pago'         => $metPago,
                    'fecha_pago'          => $estPago === 'PAGADO' ? $fecha->copy()->addHours(2) : null,
                    'numero_recibo'       => $recibo,
                ]
            );
        }

        // ─────────────────────────────────────────────────────────────────────
        // 8. SESIONES DE CLASE Y ASISTENCIAS (50+ Sesiones y 160+ Asistencias)
        // ─────────────────────────────────────────────────────────────────────
        $fechasSesiones = ['2026-10-05', '2026-10-12', '2026-10-19', '2026-10-26'];
        $todosLosGrupos = Grupo::with('inscripciones')->get();

        foreach ($todosLosGrupos as $grp) {
            foreach ($fechasSesiones as $idxFecha => $fStr) {
                $sesion = SesionClase::firstOrCreate([
                    'grupo_id' => $grp->id,
                    'fecha'    => Carbon::parse($fStr)->addDays($grp->id % 3),
                ]);

                foreach ($grp->inscripciones as $ins) {
                    // Variar estados de asistencia de manera realista según el ID de la inscripción
                    $seedVal = ($ins->id + $idxFecha) % 10;
                    if ($ins->estado === 'PAUSADA' && $idxFecha >= 2) {
                        $estAsist = 'FALTA';
                        $obsAsist = 'Inasistencia previa a solicitud de pausa académica';
                    } elseif ($seedVal === 0) {
                        $estAsist = 'FALTA';
                        $obsAsist = 'Falta sin aviso previo';
                    } elseif ($seedVal === 1 || $seedVal === 2) {
                        $estAsist = 'ATRASO';
                        $obsAsist = 'Ingreso con 10 minutos de retraso';
                    } elseif ($seedVal === 3) {
                        $estAsist = 'LICENCIA';
                        $obsAsist = 'Permiso justificado por actividad escolar';
                    } else {
                        $estAsist = 'PRESENTE';
                        $obsAsist = null;
                    }

                    Asistencia::firstOrCreate(
                        [
                            'sesion_clase_id' => $sesion->id,
                            'inscripcion_id'  => $ins->id,
                        ],
                        [
                            'estado'      => $estAsist,
                            'observacion' => $obsAsist,
                        ]
                    );
                }
            }
        }

        // ─────────────────────────────────────────────────────────────────────
        // 9. EVALUACIONES Y CALIFICACIONES (48+ Evaluaciones y 150+ Calificaciones)
        // ─────────────────────────────────────────────────────────────────────
        $plantillaEvaluaciones = [
            ['Control Práctico de Técnica Instrumental', 'PRACTICA', '2026-10-14', 35.00],
            ['Evaluación Teórica de Lectura y Solfeo',   'TEORIA',   '2026-10-24', 30.00],
            ['Audición de Repertorio de Mitad de Ciclo', 'AUDICION', '2026-11-04', 35.00],
        ];

        foreach ($todosLosGrupos as $grp) {
            // Si el grupo ya tenía evaluaciones del CicloAcademicoSeeder, solo completamos donde falte
            if (Evaluacion::where('grupo_id', $grp->id)->count() >= 2) {
                continue;
            }

            foreach ($plantillaEvaluaciones as $idxEval => [$nomEv, $tipoEv, $fechaEv, $pondEv]) {
                $evaluacion = Evaluacion::firstOrCreate(
                    [
                        'grupo_id' => $grp->id,
                        'nombre'   => "{$nomEv} ({$grp->codigo_grupo})",
                    ],
                    [
                        'tipo'        => $tipoEv,
                        'fecha'       => Carbon::parse($fechaEv),
                        'ponderacion' => $pondEv,
                        'descripcion' => "Evaluación formativa programada para el grupo {$grp->codigo_grupo}.",
                    ]
                );

                foreach ($grp->inscripciones as $ins) {
                    // Generar perfiles de notas variados (altas, medias y en riesgo < 70)
                    $perfil = $ins->id % 5;
                    if ($perfil === 0) {
                        // Alumno en riesgo académico (tendencia negativa)
                        $notaBase = 72 - ($idxEval * 7);
                        $obsNota  = 'Requiere refuerzo urgente en digitación y lectura rítmica.';
                    } elseif ($perfil === 1) {
                        // Alumno de riesgo medio
                        $notaBase = 68 + ($idxEval * 2);
                        $obsNota  = 'Avance moderado; debe incrementar práctica con metrónomo en casa.';
                    } else {
                        // Alumno destacado / regular
                        $notaBase = 84 + (($ins->id + $idxEval) % 15);
                        $obsNota  = 'Buen dominio técnico, afinación estable y cumplimiento del repertorio.';
                    }

                    Calificacion::firstOrCreate(
                        [
                            'evaluacion_id'  => $evaluacion->id,
                            'inscripcion_id' => $ins->id,
                        ],
                        [
                            'nota'          => max(45, min(100, $notaBase)),
                            'observaciones' => $obsNota,
                        ]
                    );
                }
            }
        }

        // ─────────────────────────────────────────────────────────────────────
        // 10. OBSERVACIONES ACADÉMICAS (50+ Observaciones Pedagógicas)
        // ─────────────────────────────────────────────────────────────────────
        $todasInscripciones = Inscripcion::with('grupo')->get();
        $PlantillasObs = [
            ['PROGRESO_TECNICO', 'Avance consistente en postura y ejecución', 'El estudiante mantiene una postura relajada y muestra fluidez creciente en los ejercicios técnicos del módulo.', true],
            ['RECOMENDACION',    'Rutina sugerida de estudio diario en casa', 'Se recomienda practicar 20 minutos diarios subdividiendo con metrónomo lento antes de subir a la velocidad final.', true],
            ['DISCIPLINA',       'Observación sobre puntualidad y constancia', 'Se conversó con el estudiante sobre la importancia de asistir con regularidad y traer sus partituras impresas.', true],
            ['FELICITACION',     'Destacada participación en práctica de ensamble', 'Excelente actitud colaborativa, oído armónico y precisión rítmica durante la sesión grupal.', true],
        ];

        foreach ($todasInscripciones as $ins) {
            if (!$ins->grupo) {
                continue;
            }
            $plantilla = $PlantillasObs[$ins->id % count($PlantillasObs)];

            ObservacionAcademica::firstOrCreate(
                [
                    'inscripcion_id' => $ins->id,
                    'titulo'         => $plantilla[1] . " (#{$ins->id})",
                ],
                [
                    'docente_id'          => $ins->grupo->docente_id,
                    'fecha'               => Carbon::parse('2026-10-20')->addDays($ins->id % 15),
                    'tipo'                => $plantilla[0],
                    'descripcion'         => $plantilla[2],
                    'visible_para_alumno' => $plantilla[3],
                ]
            );
        }

        // ─────────────────────────────────────────────────────────────────────
        // 11. RE-EJECUTAR MOTOR PREDICTIVO (Genera 50 Alertas) Y 42 INTERVENCIONES
        // ─────────────────────────────────────────────────────────────────────
        config(['services.ml.enabled' => false]);
        /** @var RiesgoPredictivoService $riesgoService */
        $riesgoService = app(RiesgoPredictivoService::class);
        $riesgoService->analizarAcademia(null, null, 'MOTOR_LOCAL');

        $alertas = AlertaSeguimiento::with('inscripcion.grupo.docente')->take(42)->get();
        $tiposIntervencion = [
            ['TUTORIA_INDIVIDUAL',   'Tutoría personalizada de nivelación técnica',  'Se trabajó individualmente la lectura de compases complejos y postura instrumental.', 'Practicar 15 minutos diarios con metrónomo a 70 bpm.', 'MEJORA_EVIDENTE'],
            ['REFUERZO_TECNICO',     'Sesión de refuerzo previo a evaluación',       'Se reforzaron ejercicios de digitación y escalas del repertorio asignado.', 'Presentar grabación de práctica en la siguiente sesión.', 'EN_PROCESO'],
            ['LLAMADA_SEGUIMIENTO',  'Comunicación de seguimiento por asistencia',   'Se contactó al estudiante/tutor para coordinar recuperación de clase y regularizar asistencia.', 'Confirmar asistencia puntual a las sesiones de la semana.', 'EN_PROCESO'],
            ['CITACION_PADRES',      'Reunión de coordinación pedagógica con tutor', 'Se revisó el avance del ciclo académico y se acordó horario fijo de práctica en casa.', 'Supervisar rutina de estudio tres veces por semana.', 'MEJORA_EVIDENTE'],
            ['REPROGRAMACION',       'Reprogramación de sesión práctica de apoyo',   'Se asignó horario complementario en sala libre para recuperar repertorio pendiente.', 'Asistir el viernes a las 15:00 para revisión de avance.', 'EN_PROCESO'],
        ];

        $adminUser = Usuario::first();
        foreach ($alertas as $idxAl => $alerta) {
            $tInt = $tiposIntervencion[$idxAl % count($tiposIntervencion)];
            $responsableId = $alerta->inscripcion?->grupo?->docente_id ?? $adminUser?->id;

            if (!$responsableId || !$alerta->inscripcion_id) {
                continue;
            }

            IntervencionSeguimiento::firstOrCreate(
                [
                    'alerta_id' => $alerta->id,
                    'titulo'    => $tInt[1],
                ],
                [
                    'inscripcion_id' => $alerta->inscripcion_id,
                    'usuario_id'     => $responsableId,
                    'fecha'          => Carbon::parse('2026-11-01')->addDays($idxAl % 12),
                    'tipo'           => $tInt[0],
                    'descripcion'    => $tInt[2],
                    'compromiso'     => $tInt[3],
                    'resultado'      => $tInt[4],
                ]
            );
        }

        // ─────────────────────────────────────────────────────────────────────
        // 12. BITÁCORA Y PÁGINAS VISITADAS (50+ Registros de Auditoría)
        // ─────────────────────────────────────────────────────────────────────
        $usuariosAll = Usuario::take(25)->get();
        $menusAll    = Menu::all();

        if ($usuariosAll->isNotEmpty() && Bitacora::count() < 45) {
            $accionesBitacora = [
                ['Inicio de sesión exitoso',                'login',          'POST', 'EXITO'],
                ['Consulta de listado de inscripciones',    'inscripciones',  'GET',  'EXITO'],
                ['Registro de asistencia de sesión grupal', 'asistencias',    'PUT',  'EXITO'],
                ['Actualización de calificaciones de grupo','evaluaciones',   'PUT',  'EXITO'],
                ['Ejecución de análisis predictivo XAI',    'riesgo/analizar','POST', 'EXITO'],
                ['Consulta de ficha integral de alumno',    'seguimiento',    'GET',  'EXITO'],
            ];

            for ($i = 0; $i < 50; $i++) {
                $usr = $usuariosAll[$i % $usuariosAll->count()];
                $acc = $accionesBitacora[$i % count($accionesBitacora)];

                Bitacora::create([
                    'usuario_id' => $usr->id,
                    'accion'     => $acc[0],
                    'recurso'    => $acc[1],
                    'metodo'     => $acc[2],
                    'estado'     => $acc[3],
                    'ip'         => '192.168.1.' . (10 + ($i % 80)),
                    'fecha'      => now()->subHours(50 - $i),
                ]);
            }
        }

        if ($usuariosAll->isNotEmpty() && $menusAll->isNotEmpty()) {
            foreach ($usuariosAll as $uIdx => $usr) {
                foreach ($menusAll->take(3) as $mIdx => $mnu) {
                    PaginaVisitada::updateOrCreate(
                        [
                            'menu_id'    => $mnu->id,
                            'usuario_id' => $usr->id,
                        ],
                        [
                            'contador'      => 3 + (($uIdx + $mIdx) % 18),
                            'ultima_visita' => now()->subHours(($uIdx + $mIdx) % 36),
                        ]
                    );
                }
            }
        }
    }
}
