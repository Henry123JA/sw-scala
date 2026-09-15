<?php

namespace App\Http\Controllers;

use App\Models\Mensualidad;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReciboController extends Controller
{
    /**
     * Display a printable receipt for a paid monthly payment.
     */
    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $rol = $user->rol?->nombre ?? '';
        $userId = (int) $user->id;

        $mensualidad = Mensualidad::with([
            'inscripcion.alumno.usuario',
            'inscripcion.grupo.curso',
            'metodoPago',
        ])->findOrFail($id);

        if ($mensualidad->estado !== 'PAGADO') {
            abort(404, 'El comprobante solo está disponible para pagos confirmados.');
        }

        // Seguridad: Alumno solo puede ver sus propios recibos
        if ($rol === 'Alumno' && (int) $mensualidad->inscripcion?->alumno?->id !== $userId) {
            abort(403, 'No autorizado.');
        }

        // Docente no debería tener pagos, pero por si acaso
        if ($rol === 'Docente') {
            abort(403, 'No autorizado.');
        }

        $alumno = $mensualidad->inscripcion?->alumno;
        $grupo = $mensualidad->inscripcion?->grupo;
        $curso = $grupo?->curso;

        $recibo = [
            'numero'           => $mensualidad->numero_recibo ?? 'S/N',
            'fecha_pago'       => $mensualidad->fecha_pago?->isoFormat('dddd, D [de] MMMM [de] YYYY'),
            'monto'            => number_format((float) $mensualidad->monto_base, 2),
            'meses_pagados'    => $mensualidad->meses_pagados ?? 1,
            'metodo'           => $mensualidad->tipo_comprobante ?? 'EFECTIVO',
            'alumno_nombre'    => $alumno?->usuario?->nombres . ' ' . $alumno?->usuario?->apellidos,
            'alumno_ci'        => $alumno?->usuario?->ci ?? '',
            'curso_nombre'     => $curso?->nombre ?? 'N/A',
            'grupo_nombre'     => $grupo?->nombre ?? '',
        ];

        return Inertia::render('Recibo/Index', [
            'recibo' => $recibo,
        ]);
    }
}
