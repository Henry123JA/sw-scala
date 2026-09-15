<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Drop FK on usuario.tema_id
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropForeign(['tema_id']);
        });

        // 2. Add dual color columns
        Schema::table('tema', function (Blueprint $table) {
            $table->string('color_primario_dia', 20)->nullable();
            $table->string('color_secundario_dia', 20)->nullable();
            $table->string('contraste_dia', 20)->nullable();
            $table->string('color_primario_noche', 20)->nullable();
            $table->string('color_secundario_noche', 20)->nullable();
            $table->string('contraste_noche', 20)->nullable();
        });

        // 3. Copy dia colors from existing rows into dual _dia columns
        DB::statement("
            UPDATE tema SET
                color_primario_dia = color_primario,
                color_secundario_dia = color_secundario,
                contraste_dia = contraste
            WHERE modo = 'DIA'
        ");

        // 4. Merge noche colors into dia rows (per category)
        DB::statement("
            UPDATE tema t1 SET
                color_primario_noche = t2.color_primario,
                color_secundario_noche = t2.color_secundario,
                contraste_noche = t2.contraste
            FROM tema t2
            WHERE t1.nombre = 'ninos-dia' AND t2.nombre = 'ninos-noche'
        ");

        DB::statement("
            UPDATE tema t1 SET
                color_primario_noche = t2.color_primario,
                color_secundario_noche = t2.color_secundario,
                contraste_noche = t2.contraste
            FROM tema t2
            WHERE t1.nombre = 'jovenes-dia' AND t2.nombre = 'jovenes-noche'
        ");

        DB::statement("
            UPDATE tema t1 SET
                color_primario_noche = t2.color_primario,
                color_secundario_noche = t2.color_secundario,
                contraste_noche = t2.contraste
            FROM tema t2
            WHERE t1.nombre = 'adultos-dia' AND t2.nombre = 'adultos-noche'
        ");

        // 5. Re-map usuario.tema_id from noche rows to dia rows
        DB::statement("
            UPDATE usuario SET tema_id = (SELECT id FROM tema WHERE nombre = 'ninos-dia')
            WHERE tema_id = (SELECT id FROM tema WHERE nombre = 'ninos-noche')
        ");

        DB::statement("
            UPDATE usuario SET tema_id = (SELECT id FROM tema WHERE nombre = 'jovenes-dia')
            WHERE tema_id = (SELECT id FROM tema WHERE nombre = 'jovenes-noche')
        ");

        DB::statement("
            UPDATE usuario SET tema_id = (SELECT id FROM tema WHERE nombre = 'adultos-dia')
            WHERE tema_id = (SELECT id FROM tema WHERE nombre = 'adultos-noche')
        ");

        // 6. Delete noche rows
        DB::table('tema')->whereIn('nombre', [
            'ninos-noche', 'jovenes-noche', 'adultos-noche'
        ])->delete();

        // 7. Rename remaining rows to bare categories
        DB::table('tema')->where('nombre', 'ninos-dia')->update(['nombre' => 'ninos']);
        DB::table('tema')->where('nombre', 'jovenes-dia')->update(['nombre' => 'jovenes']);
        DB::table('tema')->where('nombre', 'adultos-dia')->update(['nombre' => 'adultos']);

        // 8. Drop obsolete columns
        Schema::table('tema', function (Blueprint $table) {
            $table->dropColumn(['modo', 'color_primario', 'color_secundario', 'contraste']);
        });

        // 9. Re-add FK on usuario.tema_id
        Schema::table('usuario', function (Blueprint $table) {
            $table->foreign('tema_id')->references('id')->on('tema')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Drop FK on usuario.tema_id
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropForeign(['tema_id']);
        });

        // 2. Re-add old columns
        Schema::table('tema', function (Blueprint $table) {
            $table->string('modo', 10)->nullable();
            $table->string('color_primario', 20)->nullable();
            $table->string('color_secundario', 20)->nullable();
            $table->string('contraste', 20)->nullable();
        });

        // 3. Rename rows back to -dia suffix
        DB::table('tema')->where('nombre', 'ninos')->update(['nombre' => 'ninos-dia']);
        DB::table('tema')->where('nombre', 'jovenes')->update(['nombre' => 'jovenes-dia']);
        DB::table('tema')->where('nombre', 'adultos')->update(['nombre' => 'adultos-dia']);

        // 4. Split each dia row into dia+noche: re-insert noche rows
        $categories = [
            ['dia' => 'ninos-dia',   'noche' => 'ninos-noche'],
            ['dia' => 'jovenes-dia', 'noche' => 'jovenes-noche'],
            ['dia' => 'adultos-dia', 'noche' => 'adultos-noche'],
        ];

        foreach ($categories as $cat) {
            $diaRow = DB::table('tema')->where('nombre', $cat['dia'])->first();
            if (!$diaRow) continue;

            // Insert noche row
            $nocheId = DB::table('tema')->insertGetId([
                'nombre'                => $cat['noche'],
                'modo'                  => 'NOCHE',
                'color_primario'        => $diaRow->color_primario_noche,
                'color_secundario'      => $diaRow->color_secundario_noche,
                'contraste'             => $diaRow->contraste_noche,
                'font_size'             => $diaRow->font_size,
                'descripcion'           => str_replace('- Día', ' - Noche',
                                        str_replace('(Por defecto)', '', $diaRow->descripcion ?? '')),
                'color_primario_dia'    => null,
                'color_secundario_dia'  => null,
                'contraste_dia'         => null,
                'color_primario_noche'  => null,
                'color_secundario_noche'=> null,
                'contraste_noche'       => null,
                'created_at'            => $diaRow->created_at,
                'updated_at'            => $diaRow->updated_at,
            ]);

            // Populate dia row with its dia colors
            DB::table('tema')->where('id', $diaRow->id)->update([
                'modo'             => 'DIA',
                'color_primario'   => $diaRow->color_primario_dia,
                'color_secundario' => $diaRow->color_secundario_dia,
                'contraste'        => $diaRow->contraste_dia,
                'descripcion'      => $this->diaDescription($cat['dia']),
            ]);

            // Map usuario.tema_id back: noche users can't be recovered,
            // so all stay on dia row (best-effort)
        }

        // 5. Drop dual columns
        Schema::table('tema', function (Blueprint $table) {
            $table->dropColumn([
                'color_primario_dia',
                'color_secundario_dia',
                'contraste_dia',
                'color_primario_noche',
                'color_secundario_noche',
                'contraste_noche',
            ]);
        });

        // 6. Re-add FK on usuario.tema_id
        Schema::table('usuario', function (Blueprint $table) {
            $table->foreign('tema_id')->references('id')->on('tema')->onDelete('restrict');
        });
    }

    private function diaDescription(string $nombre): string
    {
        return match ($nombre) {
            'ninos-dia'   => 'Tema Infantil - Día',
            'jovenes-dia' => 'Tema Juvenil - Día',
            'adultos-dia' => 'Tema Adulto - Día (Por defecto)',
            default       => 'Tema - Día',
        };
    }
};
