<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('rol')) {
            Schema::create('rol', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 50)->unique();
                $table->string('descripcion', 255)->nullable();
                $table->boolean('eliminado')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('permiso')) {
            Schema::create('permiso', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100);
                $table->string('slug', 100)->unique();
                $table->boolean('eliminado')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tema')) {
            Schema::create('tema', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 50)->unique();
                $table->string('modo', 10);
                $table->string('color_primario', 20);
                $table->string('color_secundario', 20);
                $table->string('contraste', 20)->nullable();
                $table->string('font_size', 10);
                $table->string('descripcion', 255)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('menu')) {
            Schema::create('menu', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100);
                $table->string('ruta', 150)->unique();
                $table->string('icono', 50)->nullable();
                $table->integer('orden')->default(0);
                $table->foreignId('padre_id')->nullable()->constrained('menu')->onDelete('restrict');
                $table->boolean('eliminado')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('rol_menu')) {
            Schema::create('rol_menu', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rol_id')->constrained('rol')->onDelete('restrict');
                $table->foreignId('menu_id')->constrained('menu')->onDelete('restrict');
                $table->timestamps();
                $table->unique(['rol_id', 'menu_id']);
            });
        }

        if (!Schema::hasTable('rol_permiso')) {
            Schema::create('rol_permiso', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rol_id')->constrained('rol')->onDelete('restrict');
                $table->foreignId('permiso_id')->constrained('permiso')->onDelete('restrict');
                $table->timestamps();
                $table->unique(['rol_id', 'permiso_id']);
            });
        }

        if (!Schema::hasTable('usuario')) {
            Schema::create('usuario', function (Blueprint $table) {
                $table->id();
                $table->string('nombres', 100);
                $table->string('apellidos', 100);
                $table->string('ci', 20)->unique();
                $table->string('codigo', 20)->nullable()->unique();
                $table->string('email', 150)->unique();
                $table->string('password', 255);
                $table->foreignId('rol_id')->constrained('rol')->onDelete('restrict');
                $table->foreignId('tema_id')->nullable()->constrained('tema')->onDelete('restrict');
                $table->boolean('eliminado')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bitacora')) {
            Schema::create('bitacora', function (Blueprint $table) {
                $table->id();
                $table->foreignId('usuario_id')->nullable()->constrained('usuario')->onDelete('set null');
                $table->string('accion', 100);
                $table->string('recurso', 255)->nullable();
                $table->string('metodo', 10)->nullable();
                $table->string('estado', 20); // EXITO / FALLO
                $table->string('ip', 45)->nullable();
                $table->timestamp('fecha')->useCurrent();
            });
        }

        if (!Schema::hasTable('pagina_visitada')) {
            Schema::create('pagina_visitada', function (Blueprint $table) {
                $table->id();
                $table->foreignId('menu_id')->constrained('menu')->onDelete('restrict');
                $table->foreignId('usuario_id')->constrained('usuario')->onDelete('restrict');
                $table->integer('contador')->default(1);
                $table->timestamp('ultima_visita')->useCurrent();
                $table->unique(['menu_id', 'usuario_id']);
            });
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index()->constrained('usuario')->onDelete('cascade');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('pagina_visitada');
        Schema::dropIfExists('bitacora');
        Schema::dropIfExists('usuario');
        Schema::dropIfExists('rol_permiso');
        Schema::dropIfExists('rol_menu');
        Schema::dropIfExists('menu');
        Schema::dropIfExists('tema');
        Schema::dropIfExists('permiso');
        Schema::dropIfExists('rol');
    }
};
